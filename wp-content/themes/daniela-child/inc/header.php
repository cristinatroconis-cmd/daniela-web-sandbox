<?php
/**
 * Header — Remove desktop search.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Remove the Shoptimizer header search widget on desktop.
 *
 * Shoptimizer registers: add_action('shoptimizer_header', 'shoptimizer_product_search', 40)
 * Despite the Customizer setting "Display the search? → Disable", the widget
 * can still be rendered on desktop (Header 4 layout). This hook removes it
 * explicitly for non-mobile requests, leaving mobile behaviour untouched.
 *
 * Priority 20 ensures this runs after the parent theme's own after_setup_theme
 * callbacks (typically priority 10), so the action exists before we remove it.
 *
 * To revert: delete or comment out this add_action block.
 */
add_action( 'after_setup_theme', function () {
    if ( ! wp_is_mobile() ) {
        remove_action( 'shoptimizer_header', 'shoptimizer_product_search', 40 );
    }
}, 20 );
