<?php
/**
 * Shortcodes — Servicios (child pages and hub).
 *
 * Requires: inc/helpers-products.php (dm_get_products, dm_render_product_grid)
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * [dm_servicios_sesiones]
 * Lists all published products in the sesiones category.
 * Place this shortcode on the /servicios/sesiones/ page.
 */
add_shortcode( 'dm_servicios_sesiones', function () {
	if ( ! function_exists( 'WC' ) ) {
		return '';
	}
	$back_url = home_url( '/servicios/sesiones/' );
	$query    = dm_get_products( 'sesiones' );
	return dm_render_product_grid( $query, $back_url );
} );

/**
 * [dm_servicios_membresias]
 * Lists all published products in the membresias category.
 * Place this shortcode on the /servicios/membresias/ page.
 */
add_shortcode( 'dm_servicios_membresias', function () {
	if ( ! function_exists( 'WC' ) ) {
		return '';
	}
	$back_url = home_url( '/servicios/membresias/' );
	$query    = dm_get_products( 'membresias' );
	return dm_render_product_grid( $query, $back_url );
} );

/**
 * [dm_servicios_home]
 * Hub shortcode for /servicios/ — shows two blocks:
 *   1. Sesiones   (products in the "sesiones" category)
 *   2. Membresías (products in the "membresias" category)
 *
 * Cards link back to /servicios/ so the "Volver" button returns to the hub.
 *
 * Place this shortcode on the /servicios/ page.
 */
add_shortcode( 'dm_servicios_home', function () {
	if ( ! function_exists( 'WC' ) ) {
		return '';
	}

	$back_url = home_url( '/servicios/' );

	ob_start();

	echo '<section class="dm-hub-section">';
	echo '<h2 class="dm-hub-section__title">' . esc_html__( 'Sesiones', 'daniela-child' ) . '</h2>';
	$sesiones_query = dm_get_products( 'sesiones' );
	echo dm_render_product_grid( $sesiones_query, $back_url ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</section>';

	echo '<section class="dm-hub-section">';
	echo '<h2 class="dm-hub-section__title">' . esc_html__( 'Membresías', 'daniela-child' ) . '</h2>';
	$membresias_query = dm_get_products( 'membresias' );
	echo dm_render_product_grid( $membresias_query, $back_url ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</section>';

	return ob_get_clean();
} );
