<?php
/**
 * Google for WooCommerce consent-mode default.
 *
 * Google for WooCommerce (GLA) prints gtag('consent','default',{... denied ...})
 * for UK/EEA visitors, expecting a cookie banner to grant consent afterwards.
 * This site has no consent banner, so from 23 Sep 2026 GA4 and Google Ads ran
 * cookieless for every UK visitor (no sessions, no conversion cookies). Returning
 * an empty string removes the snippet. Remove this file once a consent banner
 * that sends gtag consent updates is installed.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_gla_gtag_consent', '__return_empty_string' );
