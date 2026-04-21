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
 * [dm_recursos]
 * Filterable product catalog for recursos using `tema` as the public filter.
 */
add_shortcode('dm_recursos', 'dm_recursos_shortcode');

/**
 * Main shortcode callback for the recursos product catalog.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function dm_recursos_shortcode($atts)
{
    $atts = shortcode_atts(
        [
            'per_page' => 12,
            'columns'  => 3,
        ],
        $atts,
        'dm_recursos'
    );

    $per_page    = absint($atts['per_page']);
    $columns     = absint($atts['columns']);
    $active_tema = isset($_GET['tema']) ? sanitize_key(wp_unslash($_GET['tema'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ($columns < 1 || $columns > 6) {
        $columns = 3;
    }

    $query_args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'tax_query'      => [
            'relation' => 'AND',
            [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => ['recursos'],
            ],
        ],
    ];

    if ($active_tema !== '') {
        $query_args['tax_query'][] = [
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => [$active_tema],
        ];
    }

    $products    = new WP_Query($query_args); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
    $topic_terms = get_terms([
        'taxonomy'   => 'product_tag',
        'hide_empty' => true,
    ]);
    if (is_wp_error($topic_terms)) {
        $topic_terms = [];
    }

    $current_url = dm_recursos_current_url_without_filters();

    ob_start();
    ?>
    <div class="dm-recursos" data-columns="<?php echo esc_attr($columns); ?>">

        <?php if (! empty($topic_terms)) : ?>
            <div class="dm-recursos__filters" role="navigation" aria-label="<?php esc_attr_e('Filtros de recursos', 'daniela-child'); ?>">
                <div class="dm-recursos__filter-group dm-recursos__filter-group--topic">
                    <span class="dm-recursos__filter-label"><?php esc_html_e('Tema:', 'daniela-child'); ?></span>
                    <?php $all_topics_url = remove_query_arg('tema', $current_url); ?>
                    <a href="<?php echo esc_url($all_topics_url); ?>"
                        class="dm-filter-pill dm-filter-pill--topic<?php echo $active_tema === '' ? ' is-active' : ''; ?>"
                        data-filter-type="tema"
                        data-filter-value=""
                        <?php echo $active_tema === '' ? 'aria-current="true"' : ''; ?>>
                        <?php esc_html_e('Todos', 'daniela-child'); ?>
                    </a>
                    <?php foreach ($topic_terms as $term) : ?>
                        <?php $term_url = add_query_arg('tema', $term->slug, $current_url); ?>
                        <a href="<?php echo esc_url($term_url); ?>"
                            class="dm-filter-pill dm-filter-pill--topic<?php echo $term->slug === $active_tema ? ' is-active' : ''; ?>"
                            data-filter-type="tema"
                            data-filter-value="<?php echo esc_attr($term->slug); ?>"
                            <?php echo $term->slug === $active_tema ? 'aria-current="true"' : ''; ?>>
                            <?php echo esc_html($term->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($products->have_posts()) : ?>
            <ul class="dm-topic-products__grid" role="list">
                <?php
                while ($products->have_posts()) :
                    $products->the_post();
                    global $product;
                    if (! $product instanceof WC_Product) {
                        $product = wc_get_product(get_the_ID());
                    }
                    if (! $product) {
                        continue;
                    }
                    dm_render_topic_product_card($product);
                endwhile;
                wp_reset_postdata();
                ?>
            </ul>
        <?php else : ?>
            <p class="dm-topic-products__empty">
                <?php esc_html_e('No hay recursos disponibles para estos filtros.', 'daniela-child'); ?>
            </p>
        <?php endif; ?>

    </div>
<?php

    wp_enqueue_script('dm-recursos-filters');

    return ob_get_clean();
}

/**
 * Return the current page URL without recursos filter query params.
 *
 * @return string
 */
function dm_recursos_current_url_without_filters()
{
    global $wp;
    $url = home_url(add_query_arg([], $wp->request));

    return remove_query_arg(['tema'], $url);
}

add_action('wp_enqueue_scripts', 'dm_recursos_enqueue_assets');

/**
 * Register the JS used by the filterable recursos catalog.
 */
function dm_recursos_enqueue_assets()
{
    $js_file = get_stylesheet_directory() . '/js/recursos-filters.js';
    wp_register_script(
        'dm-recursos-filters',
        get_stylesheet_directory_uri() . '/js/recursos-filters.js',
        [],
        file_exists($js_file) ? (string) filemtime($js_file) : '1.0.0',
        true
    );
}

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
