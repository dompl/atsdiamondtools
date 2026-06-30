<?php
/**
 * rename-product-images.php
 *
 * One-off WP-CLI migration command: rename WooCommerce product image files so the
 * filenames are the SEO slug of the product (and variation attributes), update all
 * attachment metadata, and fix hardcoded references across the DB.
 *
 * Usage:
 *   wp --require=rename-product-images.php rfs rename-product-images [flags]
 *
 * Flags:
 *   --dry-run            Preview only, change nothing. DEFAULT ON. Use --no-dry-run to execute.
 *   --product=<id>       Limit to a single product (and its variations/gallery).
 *   --limit=<n>          Limit number of products processed.
 *   --include-gallery    Include gallery images. Default true (use --no-include-gallery to skip).
 *   --include-variations Include variation images. Default true (use --no-include-variations to skip).
 *   --set-alt            Also set _wp_attachment_image_alt to the product name. Default false.
 *   --scan-refs          In dry-run, actually scan the DB for reference counts (slow). Default false.
 *   --log=<file>         CSV log path. Defaults to ./rename-product-images-<stamp>.csv
 *
 * Recommended order on staging:
 *   1) wp --require=rename-product-images.php rfs rename-product-images              (full dry-run)
 *   2) wp --require=rename-product-images.php rfs rename-product-images --product=<id> --no-dry-run   (subset live)
 *   3) wp --require=rename-product-images.php rfs rename-product-images --no-dry-run (full live)
 * Leave --set-alt OFF (Agent F writes richer alt text).
 *
 * @package RFS_SEO
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class RFS_Rename_Product_Images_Command {

	private $dry_run            = true;
	private $include_gallery    = true;
	private $include_variations = true;
	private $set_alt            = false;
	private $scan_refs          = false;

	private $log_path;
	private $map_path;
	private $htaccess_path;

	private $uploads_basedir;
	private $uploads_baseurl;

	/** attachment_id => array of distinct product IDs using it (for shared detection). */
	private $usage = array();
	/** basename(_wp_attached_file) => attachment_id (for collision / ownership checks). */
	private $basename_owner = array();

	private $stats = array(
		'products'       => 0,
		'attachments'    => 0,
		'renamed'        => 0,
		'skipped_named'  => 0,
		'collisions'     => 0,
		'shared_skipped' => 0,
		'errors'         => 0,
	);

	/** Per-attachment CSV rows. */
	private $log_rows = array();
	/** old_url => new_url for every renamed file (for the map + redirects). */
	private $url_map = array();
	/** old_rel => new_rel for every renamed file (search-replace keys, extension-qualified = unique). */
	private $replacements = array();

	/** Allowed image extensions when globbing derivative/optimiser files. */
	private $img_ext_re = '(?:jpe?g|png|gif|webp|avif)';

	public function __invoke( $args, $assoc ) {
		$this->dry_run            = (bool) \WP_CLI\Utils\get_flag_value( $assoc, 'dry-run', true );
		$this->include_gallery    = (bool) \WP_CLI\Utils\get_flag_value( $assoc, 'include-gallery', true );
		$this->include_variations = (bool) \WP_CLI\Utils\get_flag_value( $assoc, 'include-variations', true );
		$this->set_alt            = (bool) \WP_CLI\Utils\get_flag_value( $assoc, 'set-alt', false );
		$this->scan_refs          = (bool) \WP_CLI\Utils\get_flag_value( $assoc, 'scan-refs', false );

		if ( ! function_exists( 'wc_get_product' ) ) {
			WP_CLI::error( 'WooCommerce is not active.' );
		}

		$ud = wp_upload_dir();
		if ( ! empty( $ud['error'] ) ) {
			WP_CLI::error( 'Uploads dir error: ' . $ud['error'] );
		}
		$this->uploads_basedir = untrailingslashit( $ud['basedir'] );
		$this->uploads_baseurl = untrailingslashit( $ud['baseurl'] );

		$stamp               = gmdate( 'Ymd-His' );
		$log                 = isset( $assoc['log'] ) ? $assoc['log'] : getcwd() . "/rename-product-images-{$stamp}.csv";
		$this->log_path      = $log;
		$stem                = preg_replace( '/\.csv$/i', '', $log );
		$this->map_path      = $stem . '-url-map.csv';
		$this->htaccess_path = $stem . '-redirects.conf';

		WP_CLI::log( ( $this->dry_run ? '[DRY-RUN] ' : '[LIVE] ' ) . 'rfs rename-product-images starting.' );

		$this->build_indexes();

		$product_ids = $this->target_products( $assoc );
		$this->stats['products'] = count( $product_ids );
		WP_CLI::log( 'Products in scope: ' . count( $product_ids ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Processing products', count( $product_ids ) );
		foreach ( $product_ids as $pid ) {
			$this->process_product( $pid );
			$progress->tick();
		}
		$progress->finish();

		if ( ! empty( $this->replacements ) ) {
			$this->fix_references();
		}

		$this->write_outputs();

		if ( ! $this->dry_run ) {
			wp_cache_flush();
		}

		$this->summary();
	}

	/* --------------------------------------------------------------------- */

	private function build_indexes() {
		global $wpdb;

		// basename -> owner attachment id.
		$rows = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'" );
		foreach ( $rows as $r ) {
			$this->basename_owner[ basename( $r->meta_value ) ] = (int) $r->post_id;
		}

		// usage map across ALL published products (regardless of --product scope).
		$all = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $all as $pid ) {
			foreach ( $this->all_image_ids_for_product( $pid ) as $aid ) {
				if ( ! isset( $this->usage[ $aid ] ) ) {
					$this->usage[ $aid ] = array();
				}
				$this->usage[ $aid ][ $pid ] = true;
			}
		}
	}

	/** Every image id (featured + gallery + variation) for a product, deduped. */
	private function all_image_ids_for_product( $pid ) {
		$ids     = array();
		$product = wc_get_product( $pid );
		if ( ! $product ) {
			return $ids;
		}
		if ( $product->get_image_id() ) {
			$ids[] = (int) $product->get_image_id();
		}
		foreach ( (array) $product->get_gallery_image_ids() as $g ) {
			$ids[] = (int) $g;
		}
		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $vid ) {
				$v = wc_get_product( $vid );
				if ( $v && $v->get_image_id() ) {
					$ids[] = (int) $v->get_image_id();
				}
			}
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	private function target_products( $assoc ) {
		if ( ! empty( $assoc['product'] ) ) {
			return array( (int) $assoc['product'] );
		}
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => isset( $assoc['limit'] ) ? (int) $assoc['limit'] : -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		);
		return get_posts( $args );
	}

	/* --------------------------------------------------------------------- */

	private function process_product( $pid ) {
		$product = wc_get_product( $pid );
		if ( ! $product ) {
			return;
		}
		$name      = $product->get_name();
		$slug_base = sanitize_title( $name );
		if ( '' === $slug_base ) {
			return;
		}

		// Ordered targets: featured, gallery (-2,-3...), variations.
		$targets = array();
		if ( $product->get_image_id() ) {
			$targets[] = array( (int) $product->get_image_id(), $slug_base );
		}
		if ( $this->include_gallery ) {
			$i = 2;
			foreach ( (array) $product->get_gallery_image_ids() as $g ) {
				$targets[] = array( (int) $g, $slug_base . '-' . $i );
				$i++;
			}
		}
		if ( $this->include_variations && $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $vid ) {
				$v = wc_get_product( $vid );
				if ( ! $v || ! $v->get_image_id() ) {
					continue;
				}
				$vals = array_filter( array_values( (array) $v->get_variation_attributes() ) );
				$vb   = sanitize_title( $name . ' ' . implode( ' ', $vals ) );
				$targets[] = array( (int) $v->get_image_id(), $vb ?: $slug_base );
			}
		}

		$seen = array();
		foreach ( $targets as $t ) {
			list( $aid, $target_base ) = $t;
			if ( $aid <= 0 || isset( $seen[ $aid ] ) ) {
				continue;
			}
			$seen[ $aid ] = true;
			$this->stats['attachments']++;

			if ( isset( $this->usage[ $aid ] ) && count( $this->usage[ $aid ] ) > 1 ) {
				$this->stats['shared_skipped']++;
				$this->add_log( $aid, $name, $pid, '', '', 'shared-skipped (used by ' . count( $this->usage[ $aid ] ) . ' products)' );
				continue;
			}

			$this->process_attachment( $aid, sanitize_title( $target_base ), $name, $pid );
		}
	}

	private function process_attachment( $aid, $target_slug, $product_name, $pid ) {
		$file = get_post_meta( $aid, '_wp_attached_file', true );
		if ( ! $file ) {
			$this->stats['errors']++;
			$this->add_log( $aid, $product_name, $pid, '', '', 'error: no _wp_attached_file' );
			return;
		}
		$meta    = wp_get_attachment_metadata( $aid );
		$reldir  = dirname( $file );
		$reldir  = ( '.' === $reldir ) ? '' : $reldir;
		$absdir  = $this->uploads_basedir . ( $reldir ? '/' . $reldir : '' );

		// Canonical OLD stem (the original image base, no -scaled, no -WxH).
		if ( ! empty( $meta['original_image'] ) ) {
			$oldstem = pathinfo( $meta['original_image'], PATHINFO_FILENAME );
		} else {
			$oldstem = pathinfo( basename( $file ), PATHINFO_FILENAME );
			$oldstem = preg_replace( '/-scaled$/', '', $oldstem );
		}

		// Idempotent: already named.
		if ( $oldstem === $target_slug ) {
			$this->stats['skipped_named']++;
			$old_rel = $reldir ? $reldir . '/' . basename( $file ) : basename( $file );
			$this->add_log( $aid, $product_name, $pid, $old_rel, $old_rel, 'skipped (already named)' );
			return;
		}

		// Gather OLD basenames from metadata + glob derivatives.
		$old_basenames = array();
		$old_basenames[] = basename( $file );
		if ( ! empty( $meta['original_image'] ) ) {
			$old_basenames[] = $meta['original_image'];
		}
		if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $sz ) {
				if ( ! empty( $sz['file'] ) ) {
					$old_basenames[] = $sz['file'];
				}
			}
		}
		// Optimiser / retina derivatives: glob OLDSTEM* with a tightly-bounded regex.
		$boundary = '/^' . preg_quote( $oldstem, '/' ) . '(?:-scaled|-\d+x\d+|@2x|-\d+x\d+@2x)?\.' . $this->img_ext_re . '(?:\.(?:webp|avif))?$/i';
		foreach ( (array) glob( $absdir . '/' . $this->glob_escape( $oldstem ) . '*' ) as $abs ) {
			$bn = basename( $abs );
			if ( ! preg_match( $boundary, $bn ) ) {
				continue;
			}
			// Ownership guard: do not touch a file that is another attachment's primary file.
			if ( isset( $this->basename_owner[ $bn ] ) && (int) $this->basename_owner[ $bn ] !== (int) $aid ) {
				continue;
			}
			$old_basenames[] = $bn;
		}
		$old_basenames = array_values( array_unique( $old_basenames ) );

		// Resolve a NEW stem with no collisions on disk or in the owner index.
		$newstem = $this->resolve_newstem( $target_slug, $oldstem, $old_basenames, $absdir, $aid );
		if ( false === $newstem ) {
			$this->stats['collisions']++;
			$this->add_log( $aid, $product_name, $pid, $reldir, '', 'collision: no free target slug' );
			return;
		}

		// Build OLD->NEW (relative paths + URLs) for every file.
		$pairs = array(); // old_rel => new_rel
		foreach ( $old_basenames as $bn ) {
			$new_bn = $this->map_basename( $bn, $oldstem, $newstem );
			if ( null === $new_bn || $new_bn === $bn ) {
				continue;
			}
			$old_rel       = $reldir ? $reldir . '/' . $bn : $bn;
			$new_rel       = $reldir ? $reldir . '/' . $new_bn : $new_bn;
			$pairs[ $old_rel ] = $new_rel;
		}
		if ( empty( $pairs ) ) {
			$this->stats['skipped_named']++;
			$this->add_log( $aid, $product_name, $pid, $reldir, $reldir, 'skipped (nothing to rename)' );
			return;
		}

		// New attached file (relative) = mapped basename of the current _wp_attached_file.
		$new_attached_bn  = $this->map_basename( basename( $file ), $oldstem, $newstem );
		$new_file_rel     = $reldir ? $reldir . '/' . $new_attached_bn : $new_attached_bn;
		$old_attached_rel = $file;
		$old_url          = $this->uploads_baseurl . '/' . $old_attached_rel;
		$new_url          = $this->uploads_baseurl . '/' . $new_file_rel;

		// Record URL map for every file (for redirects / Dom review).
		foreach ( $pairs as $orel => $nrel ) {
			$this->url_map[ $this->uploads_baseurl . '/' . $orel ] = $this->uploads_baseurl . '/' . $nrel;
		}

		if ( $this->dry_run ) {
			$this->stats['renamed']++; // would-rename
			$this->add_log( $aid, $product_name, $pid, $old_attached_rel, $new_file_rel, 'dry-run (would rename ' . count( $pairs ) . ' files)' );
			// Stage replacements so a --scan-refs dry-run can preview counts.
			$this->replacements = array_merge( $this->replacements, $pairs );
			return;
		}

		// LIVE: rename files on disk with per-attachment rollback.
		$done = array(); // new_abs => old_abs
		foreach ( $pairs as $orel => $nrel ) {
			$oabs = $this->uploads_basedir . '/' . $orel;
			$nabs = $this->uploads_basedir . '/' . $nrel;
			if ( ! file_exists( $oabs ) ) {
				continue; // tolerate missing derivatives.
			}
			if ( file_exists( $nabs ) || ! @rename( $oabs, $nabs ) ) {
				// Roll back everything done for this attachment.
				foreach ( $done as $n => $o ) {
					@rename( $n, $o );
				}
				$this->stats['errors']++;
				$this->add_log( $aid, $product_name, $pid, $old_attached_rel, $new_file_rel, 'error: rename failed, rolled back' );
				return;
			}
			$done[ $nabs ] = $oabs;
		}

		// Update attachment metadata.
		update_post_meta( $aid, '_wp_attached_file', $new_file_rel );
		if ( is_array( $meta ) ) {
			if ( ! empty( $meta['file'] ) ) {
				$meta['file'] = $new_file_rel;
			}
			if ( ! empty( $meta['original_image'] ) ) {
				$meta['original_image'] = $this->map_basename( $meta['original_image'], $oldstem, $newstem );
			}
			if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
				foreach ( $meta['sizes'] as $k => $sz ) {
					if ( ! empty( $sz['file'] ) ) {
						$meta['sizes'][ $k ]['file'] = $this->map_basename( $sz['file'], $oldstem, $newstem );
					}
				}
			}
			wp_update_attachment_metadata( $aid, $meta );
		}

		// Update the attachment post: guid, title, slug.
		global $wpdb;
		$wpdb->update(
			$wpdb->posts,
			array(
				'guid'      => $new_url,
				'post_title' => $product_name,
				'post_name' => $newstem,
			),
			array( 'ID' => $aid )
		);
		clean_post_cache( $aid );

		if ( $this->set_alt ) {
			update_post_meta( $aid, '_wp_attachment_image_alt', $product_name );
		}

		$this->replacements = array_merge( $this->replacements, $pairs );
		$this->stats['renamed']++;
		$this->add_log( $aid, $product_name, $pid, $old_attached_rel, $new_file_rel, 'renamed (' . count( $pairs ) . ' files)' );
	}

	/* --------------------------------------------------------------------- */

	/** Map a single OLD basename to its NEW basename by swapping the stem prefix. */
	private function map_basename( $bn, $oldstem, $newstem ) {
		$ext      = pathinfo( $bn, PATHINFO_EXTENSION );      // e.g. jpg, or webp for x.jpg.webp
		$filename = pathinfo( $bn, PATHINFO_FILENAME );       // e.g. oldstem-300x200, or oldstem-300x200.jpg
		if ( 0 !== strpos( $filename, $oldstem ) ) {
			return null; // not safely transformable.
		}
		$suffix = substr( $filename, strlen( $oldstem ) );    // e.g. "", "-scaled", "-300x200", "-300x200.jpg"
		return $newstem . $suffix . ( '' !== $ext ? '.' . $ext : '' );
	}

	/** Find a NEW stem with all target paths free and primary basename not owned elsewhere. */
	private function resolve_newstem( $slug, $oldstem, $old_basenames, $absdir, $aid ) {
		for ( $n = 0; $n <= 50; $n++ ) {
			$cand = $n ? $slug . '-' . $n : $slug;
			if ( $cand === $oldstem ) {
				return $cand; // already (idempotent edge for bumped names).
			}
			$clash = false;
			foreach ( $old_basenames as $bn ) {
				$new_bn = $this->map_basename( $bn, $oldstem, $cand );
				if ( null === $new_bn ) {
					continue;
				}
				$nabs = $absdir . '/' . $new_bn;
				if ( file_exists( $nabs ) ) {
					$clash = true;
					break;
				}
				if ( isset( $this->basename_owner[ $new_bn ] ) && (int) $this->basename_owner[ $new_bn ] !== (int) $aid ) {
					$clash = true;
					break;
				}
			}
			if ( ! $clash ) {
				return $cand;
			}
		}
		return false;
	}

	private function glob_escape( $str ) {
		return preg_replace( '/([*?\[\]])/', '\\\\$1', $str );
	}

	/* --------------------------------------------------------------------- */

	private function fix_references() {
		$this->replacements = array_unique( $this->replacements );
		$count = count( $this->replacements );

		if ( $this->dry_run && ! $this->scan_refs ) {
			WP_CLI::log( "[DRY-RUN] Reference replacement staged for {$count} file paths (skipped; pass --scan-refs to preview counts)." );
			return;
		}

		global $wpdb;
		// Restrict to tables that realistically hold image URLs. Scanning all ~50 tables
		// per file is far too slow; product image URLs live in post content, postmeta
		// (galleries / ACF / page-builder), options (widgets / theme mods) and term
		// descriptions. Per-file keys are extension-qualified and unique, so this is safe.
		$tables = $wpdb->posts . ' ' . $wpdb->postmeta . ' ' . $wpdb->options . ' ' . $wpdb->term_taxonomy;

		WP_CLI::log( ( $this->dry_run ? '[DRY-RUN] scanning' : 'Updating' ) . " DB references for {$count} file paths." );
		$total = 0;
		$i     = 0;
		foreach ( $this->replacements as $old_rel => $new_rel ) {
			$i++;
			$cmd = sprintf(
				'search-replace %s %s %s --precise --skip-columns=guid --report-changed-only --format=count%s',
				escapeshellarg( $old_rel ),
				escapeshellarg( $new_rel ),
				$tables,
				$this->dry_run ? ' --dry-run' : ''
			);
			$res = WP_CLI::runcommand( $cmd, array( 'return' => 'stdout', 'exit_error' => false, 'launch' => false ) );
			$total += (int) trim( (string) $res );
			if ( 0 === $i % 25 ) {
				WP_CLI::log( "  ...{$i}/{$count}" );
			}
		}
		WP_CLI::log( ( $this->dry_run ? '[DRY-RUN] ' : '' ) . "Reference rows changed: {$total}" );
	}

	private function add_log( $aid, $product, $pid, $old_path, $new_path, $status ) {
		$this->log_rows[] = array( $aid, $product . ' (#' . $pid . ')', $old_path, $new_path, $old_path ? $this->uploads_baseurl . '/' . $old_path : '', $new_path ? $this->uploads_baseurl . '/' . $new_path : '', $status );
	}

	private function write_outputs() {
		// Per-attachment log.
		$fh = fopen( $this->log_path, 'w' );
		if ( $fh ) {
			fputcsv( $fh, array( 'attachment_id', 'product', 'old_path', 'new_path', 'old_url', 'new_url', 'status' ) );
			foreach ( $this->log_rows as $row ) {
				fputcsv( $fh, $row );
			}
			fclose( $fh );
			WP_CLI::log( 'Log written: ' . $this->log_path );
		}

		// old->new URL map.
		if ( ! empty( $this->url_map ) ) {
			$fh = fopen( $this->map_path, 'w' );
			if ( $fh ) {
				fputcsv( $fh, array( 'old_url', 'new_url' ) );
				foreach ( $this->url_map as $o => $n ) {
					fputcsv( $fh, array( $o, $n ) );
				}
				fclose( $fh );
				WP_CLI::log( 'URL map written: ' . $this->map_path );
			}

			// Optional .htaccess 301 snippet (old image path -> new full URL).
			$lines = array( '# Image rename 301s generated ' . gmdate( 'c' ) );
			foreach ( $this->url_map as $o => $n ) {
				$path = wp_parse_url( $o, PHP_URL_PATH );
				if ( $path ) {
					$lines[] = 'Redirect 301 ' . $path . ' ' . $n;
				}
			}
			file_put_contents( $this->htaccess_path, implode( "\n", $lines ) . "\n" );
			WP_CLI::log( 'Redirect snippet written: ' . $this->htaccess_path );
		}
	}

	private function summary() {
		WP_CLI::log( '----------------------------------------' );
		WP_CLI::log( ( $this->dry_run ? '[DRY-RUN] ' : '[LIVE] ' ) . 'Summary' );
		foreach ( $this->stats as $k => $v ) {
			WP_CLI::log( sprintf( '  %-15s %d', $k, $v ) );
		}
		if ( $this->dry_run ) {
			WP_CLI::log( 'Dry-run only. Re-run with --no-dry-run to execute.' );
		} else {
			WP_CLI::warning( 'Now: purge WP Rocket + CDN cache, and regenerate the image sitemap.' );
		}
	}
}

WP_CLI::add_command( 'rfs rename-product-images', 'RFS_Rename_Product_Images_Command' );
