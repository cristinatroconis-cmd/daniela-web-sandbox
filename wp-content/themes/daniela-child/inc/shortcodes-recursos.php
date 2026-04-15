<?php

/**
 * Shortcodes — Recursos (child pages and hub).
 *
 * Requires: inc/helpers-products.php (dm_get_products, dm_render_product_grid)
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * [dm_recursos_gratis]
 * Legacy alias: lists all published products in the recursos category.
 * Place this shortcode on the /recursos/ page.
 */
add_shortcode('dm_recursos_gratis', function () {
    if (! function_exists('WC')) {
        return '';
    }
    $back_url = home_url('/recursos/');
    $query    = dm_get_products('recursos');
    return dm_render_product_grid($query, $back_url);
});

/**
 * [dm_recursos_pagos]
 * Legacy alias: lists all published products in the recursos category.
 * Place this shortcode on the /recursos/ page.
 */
add_shortcode('dm_recursos_pagos', function () {
    if (! function_exists('WC')) {
        return '';
    }
    $back_url = home_url('/recursos/');
    $query    = dm_get_products('recursos');
    return dm_render_product_grid($query, $back_url);
});

/**
 * [dm_recursos_temas]
 * Shows a chips (pill) navigation filtered by WooCommerce product_tag.
 *
 * Rules:
 * - Only shows tags that have ≥1 published product in recursos.
 * - Chips order: count of qualifying products desc, then alphabetical.
 * - Non-JS fallback: ?tema=<slug> (querystring).
 * - Active chip reflected in URL.
 *
 * Place this shortcode on the /recursos/temas/ page.
 */
add_shortcode('dm_recursos_temas', function () {
    if (! function_exists('WC')) {
        return '';
    }

    $current_page = home_url('/recursos/temas/');
    $active_slug  = isset($_GET['tema']) // phpcs:ignore WordPress.Security.NonceVerification
        ? sanitize_title(wp_unslash($_GET['tema'])) // phpcs:ignore WordPress.Security.NonceVerification
        : '';

    // Build chips list via a single SQL query cached in a transient (1 hour).
    $transient_key = 'dm_recursos_temas_tags';
    $recursos_tags = get_transient($transient_key);

    if (false === $recursos_tags) {
        global $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
        $sql = $wpdb->prepare(
            "SELECT t.term_id, t.name, t.slug,
                    COUNT(DISTINCT p.ID) AS recursos_count
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr_cat
                     ON p.ID = tr_cat.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt_cat
                     ON tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id
                    AND tt_cat.taxonomy = 'product_cat'
             INNER JOIN {$wpdb->terms} t_cat
                     ON tt_cat.term_id = t_cat.term_id
                    AND t_cat.slug = %s
             INNER JOIN {$wpdb->term_relationships} tr_tag
                     ON p.ID = tr_tag.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt_tag
                     ON tr_tag.term_taxonomy_id = tt_tag.term_taxonomy_id
                    AND tt_tag.taxonomy = 'product_tag'
             INNER JOIN {$wpdb->terms} t
                     ON tt_tag.term_id = t.term_id
             WHERE p.post_type   = 'product'
               AND p.post_status = 'publish'
             GROUP BY t.term_id, t.name, t.slug
             ORDER BY recursos_count DESC, t.name ASC",
            'recursos'
        );
        $recursos_tags = $wpdb->get_results($sql);
        // phpcs:enable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared

        if (! is_array($recursos_tags)) {
            $recursos_tags = [];
        }

        set_transient($transient_key, $recursos_tags, HOUR_IN_SECONDS);
    }

    $back_url = $active_slug
        ? add_query_arg('tema', $active_slug, $current_page)
        : $current_page;

    ob_start();

    if (! empty($recursos_tags)) : ?>
        <nav class="dm-chips" aria-label="<?php esc_attr_e('Filtrar por tema', 'daniela-child'); ?>">
            <a href="<?php echo esc_url($current_page); ?>"
                class="dm-chip<?php echo $active_slug === '' ? ' dm-chip--active' : ''; ?>"
                <?php echo $active_slug === '' ? 'aria-current="true"' : ''; ?>>
                <?php esc_html_e('Todos', 'daniela-child'); ?>
            </a>
            <?php foreach ($recursos_tags as $tag) : ?>
                <a href="<?php echo esc_url(add_query_arg('tema', $tag->slug, $current_page)); ?>"
                    class="dm-chip<?php echo $active_slug === $tag->slug ? ' dm-chip--active' : ''; ?>"
                    <?php echo $active_slug === $tag->slug ? 'aria-current="true"' : ''; ?>>
                    <?php echo esc_html($tag->name); ?>
                    <span class="dm-chip__count">(<?php echo (int) $tag->recursos_count; ?>)</span>
                </a>
            <?php endforeach; ?>
        </nav>
<?php endif;

    $query = dm_get_products(['recursos'], $active_slug);
    echo dm_render_product_grid($query, $back_url); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
});

/**
 * [dm_recursos_home]
 * Hub shortcode for /recursos/ — shows the resources catalog block.
 *
 * Cards link back to /recursos/ so the "Volver" button returns to the hub.
 *
 * Place this shortcode on the /recursos/ page.
 */
add_shortcode('dm_recursos_home', function () {
    if (! function_exists('WC')) {
        return '';
    }

    $back_url = home_url('/recursos/');

    ob_start();

    echo '<section class="dm-hub-section">';
    echo '<h2 class="dm-hub-section__title">' . esc_html__('Recursos', 'daniela-child') . '</h2>';
    $recursos_query = dm_get_products('recursos');
    echo dm_render_product_grid($recursos_query, $back_url); // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</section>';

    return ob_get_clean();
});
