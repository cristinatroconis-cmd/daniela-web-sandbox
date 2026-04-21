<?php

/**
 * Assets — Enqueue styles and scripts.
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Encola el CSS del tema hijo.
 * Nota: Shoptimizer también intenta encolarlo; abajo lo desactivamos para evitar duplicación.
 * La versión usa filemtime() para cache-busting automático.
 */
add_action('wp_enqueue_scripts', function () {
	$style_file = get_stylesheet_directory() . '/style.css';
	wp_enqueue_style(
		'daniela-child-style',
		get_stylesheet_uri(),
		array(),
		file_exists($style_file) ? (string) filemtime($style_file) : '1.0.0'
	);
}, 20);

/**
 * Evita que style.css del child se cargue dos veces.
 * Shoptimizer encola automáticamente el child style como 'shoptimizer-child-style'.
 * Aquí lo quitamos para que sólo quede 'daniela-child-style' (con filemtime).
 */
add_action('wp_enqueue_scripts', function () {
	wp_dequeue_style('shoptimizer-child-style');
	wp_deregister_style('shoptimizer-child-style');
}, 21);

/**
 * Enqueue scripts/estilos específicos por shortcode/página.
 */
add_action('wp_enqueue_scripts', function () {
	global $post;
	$post_obj = is_a($post, 'WP_Post') ? $post : null;

	// Home sección "¿Qué necesitas?" (single source of truth: assets/css/home-necesitas.css).
	if (
		is_front_page() ||
		($post_obj && has_shortcode($post_obj->post_content, 'dm_home_necesitas')) ||
		($post_obj && has_shortcode($post_obj->post_content, 'dm_temas_hub'))
	) {
		$css_file = get_stylesheet_directory() . '/assets/css/home-necesitas.css';
		wp_enqueue_style(
			'dm-home-necesitas',
			get_stylesheet_directory_uri() . '/assets/css/home-necesitas.css',
			array(),
			file_exists($css_file) ? (string) filemtime($css_file) : '1.0.0'
		);

		$js_carousel = get_stylesheet_directory() . '/assets/js/home-necesitas-carousel.js';
		wp_enqueue_script(
			'dm-home-necesitas-carousel',
			get_stylesheet_directory_uri() . '/assets/js/home-necesitas-carousel.js',
			array(),
			file_exists($js_carousel) ? (string) filemtime($js_carousel) : '1.0.0',
			true
		);
	}

	// El resto requiere WooCommerce.
	if (! function_exists('WC')) {
		return;
	}

	// WooCommerce styles are now centralized in style.css.

	// Registrar y encolar globalmente el drawer lateral de "Agregar al carrito".
	// Se carga en todas las páginas del frontend cuando WooCommerce está activo,
	// para que el listener added_to_cart exista siempre que haya un CTA.
	$drawer_js = get_stylesheet_directory() . '/js/cart-drawer.js';
	wp_register_script(
		'dm-cart-drawer',
		get_stylesheet_directory_uri() . '/js/cart-drawer.js',
		array('jquery', 'wc-add-to-cart', 'wc-cart-fragments'),
		file_exists($drawer_js) ? (string) filemtime($drawer_js) : '1.0.0',
		true
	);

	$in_cart_ids = array();
	if (WC()->cart && ! WC()->cart->is_empty()) {
		foreach (WC()->cart->get_cart() as $cart_item) {
			if (! empty($cart_item['product_id'])) {
				$in_cart_ids[] = (int) $cart_item['product_id'];
			}
		}
	}

	wp_localize_script(
		'dm-cart-drawer',
		'dmCartDrawer',
		array(
			'inCartIds'            => array_values(array_unique($in_cart_ids)),
			'alreadyInCartMessage' => __('Ya está en tu carrito', 'daniela-child'),
		)
	);

	wp_enqueue_script('woocommerce');
	wp_enqueue_script('wc-add-to-cart');
	wp_enqueue_script('wc-cart-fragments');
	wp_enqueue_script('dm-cart-drawer');

	// Enqueue scripts for pages using DM shortcodes that need the filter JS.
	if ($post_obj) {
		// Note: dm-recursos-filters is registered in shortcodes-recursos.php and enqueued on-demand.

		// Lightweight scroll-into-view JS for [dm_recursos_temas] chips.
		if (has_shortcode($post_obj->post_content, 'dm_recursos_temas')) {
			$js_chips = get_stylesheet_directory() . '/js/temas-chips.js';
			wp_enqueue_script(
				'dm-temas-chips',
				get_stylesheet_directory_uri() . '/js/temas-chips.js',
				array(),
				file_exists($js_chips) ? (string) filemtime($js_chips) : '1.0.0',
				true
			);
		}
	}
}, 25);
