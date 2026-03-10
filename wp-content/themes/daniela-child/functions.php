<?php
/**
 * Daniela Child (Shoptimizer) - Functions
 *
 * Reglas:
 * - No romper lo existente.
 * - Mejoras progresivas y modulares.
 * - Evitar dependencia nueva de Elementor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encola el CSS del tema hijo.
 * (El CSS del tema padre Shoptimizer ya se carga por su cuenta.)
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'daniela-child-style',
		get_stylesheet_uri(),
		array(),
		'1.0.0'
	);
}, 20 );

// ---------------------------------------------------------------------------
// Módulos del tema hijo
// Cada módulo encapsula una feature; se carga solo si WooCommerce está activo.
// ---------------------------------------------------------------------------

/**
 * Carga los módulos cuando WooCommerce (y sus clases) ya están disponibles.
 * Usar after_setup_theme para shortcodes (necesario antes de query vars).
 */
add_action( 'after_setup_theme', 'dm_load_modules' );

function dm_load_modules() {
	$inc = get_stylesheet_directory() . '/inc/';

	// A) Recursos Hub — shortcode [dm_recursos]
	require_once $inc . 'recursos-hub.php';

	// B) Checkout Newsletter Opt-In (requiere WooCommerce)
	if ( class_exists( 'WooCommerce' ) ) {
		require_once $inc . 'newsletter-optin.php';
	}

	// C) Products listing shortcode [dm_products]
	require_once $inc . 'dm-products.php';
}

/**
 * Carga la página de ajustes de administración una vez que WooCommerce
 * ha registrado sus clases de admin (WC_Settings_Page está disponible).
 * Se usa el filtro woocommerce_get_settings_pages que WooCommerce dispara
 * dentro de su ciclo de init de admin.
 */
add_filter( 'woocommerce_get_settings_pages', 'dm_load_admin_settings_module' );

function dm_load_admin_settings_module( $settings ) {
	if ( ! is_admin() ) {
		return $settings;
	}
	$inc = get_stylesheet_directory() . '/inc/';
	require_once $inc . 'admin-settings.php';
	$settings[] = new DM_Settings_Page();
	return $settings;
}
