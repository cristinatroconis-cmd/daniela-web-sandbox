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
    <div class="dm-topic-browser" data-columns="<?php echo esc_attr($columns); ?>">

        <?php if (! empty($topic_terms)) : ?>
            <div class="dm-topic-browser__filters" role="navigation" aria-label="<?php esc_attr_e('Filtros por tema', 'daniela-child'); ?>">
                <div class="dm-topic-browser__filter-group dm-topic-browser__filter-group--topic">
                    <span class="dm-topic-browser__filter-label"><?php esc_html_e('Tema:', 'daniela-child'); ?></span>
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

    wp_enqueue_script('dm-topic-browser-filters');

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

add_action('wp_enqueue_scripts', 'dm_recursos_enqueue_filter_assets');

/**
 * Register the JS used by the recursos filter UI.
 */
function dm_recursos_enqueue_filter_assets()
{
    $js_file = get_stylesheet_directory() . '/js/recursos-filters.js';
    wp_register_script(
        'dm-topic-browser-filters',
        get_stylesheet_directory_uri() . '/js/recursos-filters.js',
        [],
        file_exists($js_file) ? (string) filemtime($js_file) : '1.0.0',
        true
    );
}
