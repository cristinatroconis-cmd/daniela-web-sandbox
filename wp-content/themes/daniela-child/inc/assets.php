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
        '0.2.0'
    );
}, 20 );

/**
 * Ensure WooCommerce AJAX add-to-cart scripts are loaded on pages that
 * use our product-listing shortcodes (child pages and hub pages).
 */
add_action( 'wp_enqueue_scripts', function () {
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! function_exists( 'WC' ) ) {
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
    ];

    foreach ( $dm_shortcodes as $sc ) {
        if ( has_shortcode( $post->post_content, $sc ) ) {
            wp_enqueue_script( 'woocommerce' );
            wp_enqueue_script( 'wc-add-to-cart' );
            break;
        }
    }
}, 25 );
