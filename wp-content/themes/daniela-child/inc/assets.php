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
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'daniela-child-style',
		get_stylesheet_uri(),
		array(),
		'1.0.0'
	);
}, 20 );

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
		wp_enqueue_style(
			'dm-home-necesitas',
			get_stylesheet_directory_uri() . '/assets/css/home-necesitas.css',
			array(),
			'1.0.0'
		);
		wp_enqueue_script(
			'dm-home-necesitas-carousel',
			get_stylesheet_directory_uri() . '/assets/js/home-necesitas-carousel.js',
			array(),
			'1.0.0',
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
		wp_enqueue_script(
			'dm-temas-chips',
			get_stylesheet_directory_uri() . '/js/temas-chips.js',
			array(),
			'1.0.0',
			true
		);
	}
}, 25 );
