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

// =============================================================================
// CART REDIRECT — Evitar redirección a /tienda/ y al carrito tras agregar.
// =============================================================================

/**
 * Hard override: forzar que WooCommerce nunca redirija al carrito tras agregar
 * un producto, independientemente de la configuración en el panel de WP.
 * El cart drawer off-canvas se encarga de mostrar el carrito al usuario.
 */
add_filter( 'option_woocommerce_cart_redirect_after_add', function () {
    return 'no';
}, 20 );

/**
 * Después de agregar un producto al carrito, evitar cualquier redirección a
 * /tienda/ o /carrito/. Para peticiones AJAX devolvemos false para que
 * WooCommerce omita el redirect en la respuesta JSON y dispare added_to_cart
 * normalmente. Para peticiones no-AJAX (fallback sin JS) volvemos a la página
 * de origen en lugar de ir al carrito.
 *
 * @param string $url URL de redirección propuesta por WooCommerce.
 * @return string|false URL corregida, o false para suprimir el redirect en AJAX.
 */
add_filter( 'woocommerce_add_to_cart_redirect', function ( $url ) {
    // En peticiones AJAX: suprimir el redirect para que el drawer pueda abrirse.
    if ( wp_doing_ajax() ) {
        return false;
    }

    // En peticiones no-AJAX: no redirigir ni a /tienda/ ni a /carrito/.
    $shop_url = function_exists( 'wc_get_page_id' ) ? get_permalink( wc_get_page_id( 'shop' ) ) : '';
    $cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '';

    if (
        ( $shop_url && trailingslashit( $url ) === trailingslashit( $shop_url ) ) ||
        ( $cart_url && trailingslashit( $url ) === trailingslashit( $cart_url ) )
    ) {
        $referer = wp_get_referer();
        return $referer
            ? wp_validate_redirect( $referer, home_url( '/recursos/' ) )
            : home_url( '/recursos/' );
    }

    return $url;
}, 20 );

/**
 * Filtrar el enlace "Seguir comprando" en el carrito para que no apunte a /tienda/.
 *
 * @param string $url URL de "seguir comprando".
 * @return string     URL corregida.
 */
add_filter( 'woocommerce_continue_shopping_redirect', function ( $url ) {
    $shop_url = get_permalink( wc_get_page_id( 'shop' ) );

    if ( $shop_url && trailingslashit( $url ) === trailingslashit( $shop_url ) ) {
        return home_url( '/' );
    }

    return $url;
} );
