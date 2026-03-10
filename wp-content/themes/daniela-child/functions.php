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

require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/assets.php';
require_once __DIR__ . '/inc/helpers-products.php';
require_once __DIR__ . '/inc/shortcodes-escuela.php';
require_once __DIR__ . '/inc/shortcodes-recursos.php';
require_once __DIR__ . '/inc/woocommerce-checkout.php';
