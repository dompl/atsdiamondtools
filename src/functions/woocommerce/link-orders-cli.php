<?php
/**
 * WP-CLI command to link orphaned WooCommerce orders to user accounts.
 *
 * Orders imported with _customer_user = 0 are treated as guest orders.
 * This command matches orders to users by billing email address.
 *
 * Usage:
 *   wp ats link-orders --dry-run   (preview changes)
 *   wp ats link-orders             (execute migration)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

class ATS_Link_Orders_Command {

    /**
     * Link orphaned orders to user accounts by matching billing email.
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : Preview changes without modifying data.
     *
     * [--batch-size=<number>]
     * : Number of orders to process per batch. Default 1000.
     *
     * ## EXAMPLES
     *
     *     wp ats link-orders --dry-run
     *     wp ats link-orders
     *     wp ats link-orders --batch-size=500
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function link_orders( $args, $assoc_args ) {
        global $wpdb;

        $dry_run    = WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
        $batch_size = (int) WP_CLI\Utils\get_flag_value( $assoc_args, 'batch-size', 1000 );

        if ( $dry_run ) {
            WP_CLI::log( '*** DRY RUN — no changes will be made ***' );
        }

        WP_CLI::log( 'Fetching orphaned orders (customer_user = 0) with billing emails...' );

        // Check if HPOS is active
        $hpos_active = $this->is_hpos_active();

        if ( $hpos_active ) {
            $email_rows = $this->get_orphaned_emails_hpos( $wpdb );
        } else {
            $email_rows = $this->get_orphaned_emails_postmeta( $wpdb );
        }

        if ( empty( $email_rows ) ) {
            WP_CLI::success( 'No orphaned orders with billing emails found. Nothing to do.' );
            return;
        }

        // Build email => user_id mapping
        $unique_emails = array_unique( array_column( $email_rows, 'billing_email' ) );
        WP_CLI::log( sprintf( 'Found %d unique billing emails across orphaned orders.', count( $unique_emails ) ) );

        $email_to_user = [];
        foreach ( $unique_emails as $email ) {
            $email = strtolower( trim( $email ) );
            if ( empty( $email ) ) {
                continue;
            }
            $user = get_user_by( 'email', $email );
            if ( $user ) {
                $email_to_user[ $email ] = $user->ID;
            }
        }

        $matched_emails   = count( $email_to_user );
        $unmatched_emails = count( $unique_emails ) - $matched_emails;

        WP_CLI::log( sprintf( 'Matched %d emails to user accounts. %d emails have no matching user.', $matched_emails, $unmatched_emails ) );

        if ( $matched_emails === 0 ) {
            WP_CLI::warning( 'No billing emails matched any user accounts. Nothing to update.' );
            return;
        }

        // Group order IDs by user_id for batch updating
        $user_order_map = [];
        $total_linkable = 0;

        foreach ( $email_rows as $row ) {
            $email = strtolower( trim( $row['billing_email'] ) );
            if ( isset( $email_to_user[ $email ] ) ) {
                $user_id = $email_to_user[ $email ];
                $user_order_map[ $user_id ][] = (int) $row['order_id'];
                $total_linkable++;
            }
        }

        WP_CLI::log( sprintf( 'Will link %d orders to %d user accounts.', $total_linkable, count( $user_order_map ) ) );

        // Log each email match
        foreach ( $email_to_user as $email => $user_id ) {
            $count = count( $user_order_map[ $user_id ] ?? [] );
            WP_CLI::log( sprintf( '  %s => User #%d (%d orders)', $email, $user_id, $count ) );
        }

        if ( $dry_run ) {
            WP_CLI::success( sprintf( 'DRY RUN complete. Would link %d orders to %d users.', $total_linkable, count( $user_order_map ) ) );
            return;
        }

        // Execute updates in batches
        $updated = 0;
        $progress = WP_CLI\Utils\make_progress_bar( 'Linking orders', $total_linkable );

        foreach ( $user_order_map as $user_id => $order_ids ) {
            $chunks = array_chunk( $order_ids, $batch_size );

            foreach ( $chunks as $chunk ) {
                $placeholders = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );

                if ( $hpos_active ) {
                    // Update HPOS orders table
                    $wpdb->query( $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}wc_orders SET customer_id = %d WHERE id IN ($placeholders)",
                        array_merge( [ $user_id ], $chunk )
                    ) );
                }

                // Update postmeta (always, for backwards compatibility)
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$wpdb->postmeta} SET meta_value = %d WHERE meta_key = '_customer_user' AND post_id IN ($placeholders)",
                    array_merge( [ $user_id ], $chunk )
                ) );

                $updated += count( $chunk );

                foreach ( $chunk as $_ ) {
                    $progress->tick();
                }
            }
        }

        $progress->finish();

        // Clear WooCommerce order caches
        if ( function_exists( 'wc_get_order' ) ) {
            foreach ( $user_order_map as $user_id => $order_ids ) {
                foreach ( $order_ids as $order_id ) {
                    wp_cache_delete( $order_id, 'posts' );
                    wp_cache_delete( $order_id . '-postmeta', 'post_meta' );
                }
            }
        }

        WP_CLI::success( sprintf(
            'Done! Linked %d orders to %d user accounts. %d emails had no matching user.',
            $updated,
            count( $user_order_map ),
            $unmatched_emails
        ) );
    }

    /**
     * Check if WooCommerce HPOS (High-Performance Order Storage) is active.
     */
    private function is_hpos_active() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }

    /**
     * Get orphaned order emails from postmeta (traditional storage).
     */
    private function get_orphaned_emails_postmeta( $wpdb ) {
        return $wpdb->get_results(
            "SELECT p.ID AS order_id, email_meta.meta_value AS billing_email
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} cu ON p.ID = cu.post_id AND cu.meta_key = '_customer_user' AND cu.meta_value = '0'
             INNER JOIN {$wpdb->postmeta} email_meta ON p.ID = email_meta.post_id AND email_meta.meta_key = '_billing_email' AND email_meta.meta_value != ''
             WHERE p.post_type IN ('shop_order', 'shop_order_placehold')
             ORDER BY p.ID",
            ARRAY_A
        );
    }

    /**
     * Get orphaned order emails from HPOS orders table.
     */
    private function get_orphaned_emails_hpos( $wpdb ) {
        return $wpdb->get_results(
            "SELECT o.id AS order_id, o.billing_email
             FROM {$wpdb->prefix}wc_orders o
             WHERE o.customer_id = 0
             AND o.billing_email IS NOT NULL
             AND o.billing_email != ''
             ORDER BY o.id",
            ARRAY_A
        );
    }
}

WP_CLI::add_command( 'ats link-orders', [ new ATS_Link_Orders_Command(), 'link_orders' ] );
