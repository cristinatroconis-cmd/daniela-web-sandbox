<?php
/**
 * Shortcodes — Escuela (child pages and hub).
 *
 * Requires: inc/helpers-products.php (dm_get_products, dm_render_product_grid)
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * [dm_escuela_cursos]
 * Lists all published products in the cursos category.
 * Place this shortcode on the /escuela/cursos/ page.
 */
add_shortcode( 'dm_escuela_cursos', function () {
    if ( ! function_exists( 'WC' ) ) {
        return '';
    }
    $back_url = home_url( '/escuela/cursos/' );
    $query    = dm_get_products( 'cursos' );
    return dm_render_product_grid( $query, $back_url );
} );

/**
 * [dm_escuela_talleres]
 * Lists all published products in the talleres category.
 * Place this shortcode on the /escuela/talleres/ page.
 */
add_shortcode( 'dm_escuela_talleres', function () {
    if ( ! function_exists( 'WC' ) ) {
        return '';
    }
    $back_url = home_url( '/escuela/talleres/' );
    $query    = dm_get_products( 'talleres' );
    return dm_render_product_grid( $query, $back_url );
} );

/**
 * [dm_escuela_programas]
 * Lists all published products in the programas category.
 * Place this shortcode on the /escuela/programas/ page.
 */
add_shortcode( 'dm_escuela_programas', function () {
    if ( ! function_exists( 'WC' ) ) {
        return '';
    }
    $back_url = home_url( '/escuela/programas/' );
    $query    = dm_get_products( 'programas' );
    return dm_render_product_grid( $query, $back_url );
} );

/**
 * [dm_escuela_home]
 * Hub shortcode for /escuela/ — shows three blocks:
 *   1. Cursos    (products in the "cursos" category)
 *   2. Talleres  (products in the "talleres" category)
 *   3. Programas (products in the "programas" category)
 *
 * Cards link back to /escuela/ so the "Volver" button returns to the hub.
 *
 * Place this shortcode on the /escuela/ page.
 */
add_shortcode( 'dm_escuela_home', function () {
    if ( ! function_exists( 'WC' ) ) {
        return '';
    }

    $back_url = home_url( '/escuela/' );

    ob_start();

    echo '<section class="dm-hub-section">';
    echo '<h2 class="dm-hub-section__title">' . esc_html__( 'Cursos', 'daniela-child' ) . '</h2>';
    $cursos_query = dm_get_products( 'cursos' );
    echo dm_render_product_grid( $cursos_query, $back_url ); // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</section>';

    echo '<section class="dm-hub-section">';
    echo '<h2 class="dm-hub-section__title">' . esc_html__( 'Talleres', 'daniela-child' ) . '</h2>';
    $talleres_query = dm_get_products( 'talleres' );
    echo dm_render_product_grid( $talleres_query, $back_url ); // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</section>';

    echo '<section class="dm-hub-section">';
    echo '<h2 class="dm-hub-section__title">' . esc_html__( 'Programas', 'daniela-child' ) . '</h2>';
    $programas_query = dm_get_products( 'programas' );
    echo dm_render_product_grid( $programas_query, $back_url ); // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</section>';

    return ob_get_clean();
} );
