<?php
/**
 * Assets — Enqueue styles and scripts.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Encola el CSS del tema hijo.
 * (El CSS del tema padre Shoptimizer ya se carga por su cuenta.)
 * La versión usa filemtime() para cache-busting automático.
 */
add_action( 'wp_enqueue_scripts', function () {
	$style_file = get_stylesheet_directory() . '/style.css';
	wp_enqueue_style(
		'daniela-child-style',
		get_stylesheet_uri(),
		array(),
		file_exists( $style_file ) ? (string) filemtime( $style_file ) : '1.0.0'
	);
}, 20 );

/**
 * Garantiza que el CSS adicional del Customizer (Apariencia → Personalizar →
 * "CSS adicional") siempre se imprima en <head>, incluso si el tema padre
 * lo suprime o usa caching agresivo.
 *
 * WordPress imprime ese CSS vía wp_custom_css_cb() en wp_head con prioridad 101.
 * Este hook lo refuerza a prioridad 102 usando wp_add_inline_style() como
 * fallback, de modo que si ya fue impreso el bloque queda vacío y no duplica.
 */
add_action( 'wp_enqueue_scripts', function () {
	$custom_css = function_exists( 'wp_get_custom_css' ) ? wp_get_custom_css() : '';
	if ( $custom_css ) {
		wp_add_inline_style( 'daniela-child-style', $custom_css );
	}
}, 21 );

/**
 * Enqueue WooCommerce AJAX scripts and the recursos-filters JS on pages
 * that use our product-listing shortcodes (child pages and hub pages).
 */
add_action( 'wp_enqueue_scripts', function () {
	global $post;
	if ( ! is_a( $post, 'WP_Post' ) ) {
		return;
	}

	// Home sección "¿Qué necesitas?" carousel + estilos (no depende de WC).
	if (
		has_shortcode( $post->post_content, 'dm_home_necesitas' )
		|| has_shortcode( $post->post_content, 'dm_temas_hub' )
		|| is_front_page()
	) {
		$css_file = get_stylesheet_directory() . '/assets/css/home-necesitas.css';
		wp_enqueue_style(
			'dm-home-necesitas',
			get_stylesheet_directory_uri() . '/assets/css/home-necesitas.css',
			array(),
			file_exists( $css_file ) ? (string) filemtime( $css_file ) : '1.0.0'
		);

		$js_carousel = get_stylesheet_directory() . '/assets/js/home-necesitas-carousel.js';
		wp_enqueue_script(
			'dm-home-necesitas-carousel',
			get_stylesheet_directory_uri() . '/assets/js/home-necesitas-carousel.js',
			array(),
			file_exists( $js_carousel ) ? (string) filemtime( $js_carousel ) : '1.0.0',
			true
		);
	}

	// The rest requires WooCommerce.
	if ( ! function_exists( 'WC' ) ) {
		return;
	}

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

	foreach ( $dm_shortcodes as $sc ) {
		if ( has_shortcode( $post->post_content, $sc ) ) {
			wp_enqueue_script( 'woocommerce' );
			wp_enqueue_script( 'wc-add-to-cart' );
			break;
		}
	}

	// Note: dm-recursos-filters is registered in recursos-hub.php and
	// enqueued on-demand by the [dm_recursos] shortcode callback — no
	// duplicate enqueue needed here.

	// Lightweight scroll-into-view JS for [dm_recursos_temas] chips.
	if ( has_shortcode( $post->post_content, 'dm_recursos_temas' ) ) {
		$js_chips = get_stylesheet_directory() . '/js/temas-chips.js';
		wp_enqueue_script(
			'dm-temas-chips',
			get_stylesheet_directory_uri() . '/js/temas-chips.js',
			array(),
			file_exists( $js_chips ) ? (string) filemtime( $js_chips ) : '1.0.0',
			true
		);
	}
}, 25 );
