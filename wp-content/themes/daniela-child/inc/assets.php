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
		( $post_obj && has_shortcode($post_obj->post_content, 'dm_home_necesitas') ) ||
		( $post_obj && has_shortcode($post_obj->post_content, 'dm_temas_hub') )
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

	// Registrar el popup de confirmación de "Agregar al carrito".
	$popup_js = get_stylesheet_directory() . '/js/add-to-cart-popup.js';
	wp_register_script(
		'dm-add-to-cart-popup',
		get_stylesheet_directory_uri() . '/js/add-to-cart-popup.js',
		array( 'jquery', 'wc-add-to-cart' ),
		file_exists( $popup_js ) ? (string) filemtime( $popup_js ) : '1.0.0',
		true
	);
	wp_localize_script(
		'dm-add-to-cart-popup',
		'dmCartPopup',
		[
			'checkout_url' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
		]
	);

	// Enqueue WooCommerce add-to-cart scripts on CPT archive and single pages.
	$is_cpt_page = (
		is_post_type_archive( [ 'dm_recurso', 'dm_escuela', 'dm_servicio' ] ) ||
		is_singular( [ 'dm_recurso', 'dm_escuela', 'dm_servicio' ] )
	);
	if ( $is_cpt_page ) {
		wp_enqueue_script( 'woocommerce' );
		wp_enqueue_script( 'wc-add-to-cart' );
		wp_enqueue_script( 'wc-cart-fragments' );
		wp_enqueue_script( 'dm-add-to-cart-popup' );
	}

	// Enqueue scripts for pages using DM shortcodes (requires $post to be a WP_Post).
	if ( $post_obj ) {
		$dm_shortcodes = [
			'dm_recursos_gratis',
			'dm_recursos_pagos',
			'dm_recursos_temas',
			'dm_escuela_cursos',
			'dm_escuela_talleres',
			'dm_escuela_home',
			'dm_recursos_home',
			'dm_recursos',
			'dm_products',
		];

		foreach ($dm_shortcodes as $sc) {
			if (has_shortcode($post_obj->post_content, $sc)) {
				wp_enqueue_script('woocommerce');
				wp_enqueue_script('wc-add-to-cart');
				wp_enqueue_script('wc-cart-fragments');
				wp_enqueue_script('dm-add-to-cart-popup');
				break;
			}
		}

		// Note: dm-recursos-filters is registered in recursos-hub.php and enqueued on-demand.

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
