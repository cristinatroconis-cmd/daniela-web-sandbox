<?php

/**
 * Daniela Child (Shoptimizer) - Functions
 *
 * Bootstrap loader: loads all theme modules from inc/.
 * Keep this file minimal — add logic to the appropriate inc/ module instead.
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
	exit;
}

// Core modules — always loaded.
require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/helpers-products.php';
require_once __DIR__ . '/inc/shortcodes-escuela.php';
require_once __DIR__ . '/inc/shortcodes-recursos.php';
require_once __DIR__ . '/inc/shortcodes-servicios.php';
require_once __DIR__ . '/inc/woocommerce-checkout.php';

// CPT — Custom Post Types, taxonomías y helpers de catálogo editorial.
require_once __DIR__ . '/inc/cpt.php';
require_once __DIR__ . '/inc/helpers-cpt.php';
require_once __DIR__ . '/inc/sync-tags.php'; // Sincronización Woo product_tag → dm_tema.

/**
 * Customizer modules.
 *
 * Desactivado a propósito: la sección Home "¿Qué necesitas?" ya NO se gestiona
 * desde el Customizer/Kirki. El contenido se define en el template y el diseño
 * se controla en: assets/css/home-necesitas.css
 */
// require_once __DIR__ . '/inc/customizer-home-necesitas.php';

// Feature modules.
require_once __DIR__ . '/inc/dm-products.php';
require_once __DIR__ . '/inc/recursos-hub.php';
require_once __DIR__ . '/inc/newsletter-optin.php';
require_once __DIR__ . '/inc/freebie-delivery.php';
require_once __DIR__ . '/inc/freebie-download.php';

// WP-CLI + admin importer (loaded after WooCommerce is ready).
add_action('plugins_loaded', function () {
	require_once __DIR__ . '/inc/cli-import-recursos.php';
});

// Admin settings (loaded via WooCommerce filter so WC_Settings_Page is available).
add_filter('woocommerce_get_settings_pages', function ($settings) {
	if (is_admin()) {
		require_once __DIR__ . '/inc/admin-settings.php';
		$settings[] = new DM_Settings_Page();
	}
	return $settings;
});

// Home: sección "¿Qué necesitas?"
add_shortcode('dm_home_necesitas', function () {
	ob_start();
	get_template_part('template-parts/home/section', 'necesitas');
	return ob_get_clean();
});

// Hub de temas (destino del slide 4).
add_shortcode('dm_temas_hub', function () {
	ob_start();
	get_template_part('template-parts/home/section', 'temas-hub');
	return ob_get_clean();
});
