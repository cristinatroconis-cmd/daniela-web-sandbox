<?php
/**
 * Daniela Child (Shoptimizer) - Functions
 *
 * Reglas:
 * - No romper lo existente.
 * - Mejoras progresivas y modulares.
 * - Evitar dependencia nueva de Elementor.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encola el CSS del tema hijo.
 * (El CSS del tema padre Shoptimizer ya se carga por su cuenta.)
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'daniela-child-style',
        get_stylesheet_uri(),
        array(),
        '0.1.0'
    );
}, 20);
