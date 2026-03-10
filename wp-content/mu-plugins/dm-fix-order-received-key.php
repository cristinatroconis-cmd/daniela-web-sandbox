<?php
/**
 * Plugin Name: DM – Fix order-received undefined key (WCS PayPal workaround)
 * Description: Ensures $_GET['order-received'] exists when WooCommerce is on the
 *              order-received endpoint, preventing a PHP 8+ "Undefined array key"
 *              warning from WooCommerce Subscriptions PayPal gateway code.
 * Version:     1.0.0
 * Author:      Daniela Child / Custom
 *
 * WHY THIS EXISTS
 * ---------------
 * WooCommerce Subscriptions (via its bundled subscriptions-core) reads
 * $_GET['order-received'] without first checking isset() in:
 *   .../gateways/paypal/class-wcs-paypal.php line ~519
 *
 * In PHP 8+ this triggers:
 *   Warning: Undefined array key "order-received"
 *
 * DO NOT edit the third-party plugin file directly — it will be overwritten
 * on every plugin update.
 *
 * HOW TO REMOVE THIS WORKAROUND
 * ------------------------------
 * Once WooCommerce Subscriptions ships a version that adds isset() guards around
 * the 'order-received' key access (check the plugin changelog), you can safely
 * delete this file. No other code depends on it.
 *
 * SAFETY NOTES
 * ------------
 * - Only runs on the order-received endpoint (is_wc_endpoint_url check).
 * - Only sets the key when it is missing.
 * - Uses WC query vars / get_query_var as the authoritative source; falls back
 *   to the 'key' (order key) GET parameter if available.
 * - Does not modify any other part of the request.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Backfill $_GET['order-received'] on the WooCommerce order-received endpoint.
 *
 * Hooked late on 'wp' so that the WC query vars are fully resolved before we
 * check is_wc_endpoint_url(). This is earlier than the gateway init calls that
 * trigger the warning.
 */
add_action( 'wp', function () {
    // Only act on requests that are not already providing the key.
    if ( isset( $_GET['order-received'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
        return;
    }

    // Confirm we are on the WooCommerce order-received endpoint.
    if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
        return;
    }

    // Primary source: WC query var (set by WC rewrite rules from the URL slug).
    $order_id = (int) get_query_var( 'order-received', 0 );

    // Fallback: resolve from the order key passed as ?key=wc_order_xxx.
    if ( ! $order_id && ! empty( $_GET['key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
        $order_key = sanitize_text_field( wp_unslash( $_GET['key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
        $order_id  = (int) wc_get_order_id_by_order_key( $order_key );
    }

    if ( $order_id ) {
        // phpcs:ignore WordPress.Security.NonceVerification
        $_GET['order-received'] = $order_id;
    }
}, 10 );
