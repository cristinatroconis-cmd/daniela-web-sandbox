<?php
/**
 * Daniela Child (Shoptimizer) - Functions
 *
 * Bootstrap loader: loads all theme modules from inc/.
 * Keep this file minimal — add logic to the appropriate inc/ module instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// HEADER — Remove desktop search
// =============================================================================

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

require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/helpers-products.php';
require_once __DIR__ . '/inc/shortcodes-escuela.php';
require_once __DIR__ . '/inc/shortcodes-recursos.php';
require_once __DIR__ . '/inc/woocommerce-checkout.php';
