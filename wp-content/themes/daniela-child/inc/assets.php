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
 * Evita que style.css del child se cargue dos veces.
 * Shoptimizer encola automáticamente el child style como 'shoptimizer-child-style'.
 * Aquí lo quitamos para que sólo quede 'daniela-child-style' (con filemtime).
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_dequeue_style( 'shoptimizer-child-style' );
	wp_deregister_style( 'shoptimizer-child-style' );
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
