<?php
/**
 * WooCommerce checkout — single-product back-link and free-cart redirect.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// SINGLE PRODUCT — "VOLVER" LINK
// =============================================================================

/**
 * Display a contextual "Volver" link above the single product content.
 *
 * Priority order:
 * 1. ?dm_back= query param set by our listing pages (validated to own host).
 * 2. wp_get_referer() if it points to our own domain.
 * 3. Fallback to /recursos/.
 */
add_action( 'woocommerce_before_single_product', function () {
    $back_url = '';

    // 1. Query param passed by listing shortcodes.
    if ( ! empty( $_GET['dm_back'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
        $candidate = esc_url_raw( urldecode( wp_unslash( $_GET['dm_back'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
        $back_url  = wp_validate_redirect( $candidate, '' );
    }

    // 2. Browser referer (same host only).
    if ( ! $back_url ) {
        $referer  = wp_get_referer();
        $back_url = $referer ? wp_validate_redirect( $referer, '' ) : '';
    }

    // 3. Fallback.
    if ( ! $back_url ) {
        $back_url = home_url( '/recursos/' );
    }

    echo '<a href="' . esc_url( $back_url ) . '" class="dm-back-link">&#8592; ' .
         esc_html__( 'Volver', 'daniela-child' ) . '</a>';
}, 5 );

// =============================================================================
// CART → CHECKOUT REDIRECT (free-only cart)
// =============================================================================

/**
 * If the cart is not empty and the total is 0 (all items free),
 * automatically redirect from the cart page to checkout.
 */
add_action( 'template_redirect', function () {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $cart = WC()->cart;

    if ( is_cart() && ! $cart->is_empty() && (float) $cart->get_total( 'edit' ) == 0 ) {
        wp_safe_redirect( wc_get_checkout_url() );
        exit;
    }
} );
