<?php

/**
 * Front Page (Home) — Daniela Child
 *
 * Strategy:
 * - Keep Elementor sections from the Home page content.
 * - Inject a PHP template-part section ("¿Qué necesitas?") via placeholder shortcode.
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $content = get_the_content();

    $placeholder = '[dm_home_necesitas]';

    ob_start();
    get_template_part('template-parts/home/section', 'necesitas');
    $necesitas_html = ob_get_clean();

    $content = str_replace($placeholder, $necesitas_html, $content);

    echo apply_filters('the_content', $content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

endwhile;

get_footer();
