<?php

/**
 * Product helpers — query, card, grid, and cache invalidation.
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Return a robust add-to-cart URL for products.
 *
 * WooCommerce can return the single permalink for non-purchasable products,
 * which breaks the desired flow for free resources. For free items we force a
 * canonical ?add-to-cart=<id> URL.
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function dm_get_add_to_cart_url($product)
{
    if (! $product instanceof WC_Product) {
        return '';
    }

    if (function_exists('dm_product_uses_waitlist') && dm_product_uses_waitlist($product)) {
        return dm_get_booking_waitlist_url();
    }

    $url       = (string) $product->add_to_cart_url();
    $price_raw = $product->get_price();
    $is_free   = ($price_raw !== '' && (float) $price_raw <= 0.0); // phpcs:ignore WordPress.PHP.StrictComparisons

    if (! $is_free) {
        return $url;
    }

    $product_permalink = (string) get_permalink($product->get_id());
    if ($url && trailingslashit($url) !== trailingslashit($product_permalink)) {
        return $url;
    }

    // Force cart add endpoint for free resources even when purchasable=false.
    return add_query_arg('add-to-cart', $product->get_id(), wc_get_cart_url());
}

function dm_get_product_primary_cta($product, $args = [])
{
    if (! $product instanceof WC_Product) {
        return [
            'mode'       => 'none',
            'url'        => '',
            'label'      => '',
            'class'      => '',
            'attributes' => [],
        ];
    }

    $args = is_array($args) ? $args : [];

    if (function_exists('dm_product_uses_waitlist') && dm_product_uses_waitlist($product)) {
        return [
            'mode'       => 'waitlist',
            'url'        => dm_get_booking_waitlist_url(),
            'label'      => isset($args['waitlist_label']) && trim((string) $args['waitlist_label']) !== '' ? trim((string) $args['waitlist_label']) : __('Unirme a la lista de espera', 'daniela-child'),
            'class'      => isset($args['waitlist_class']) && trim((string) $args['waitlist_class']) !== '' ? trim((string) $args['waitlist_class']) : (isset($args['class']) ? trim((string) $args['class']) : 'dm-btn dm-btn--primary'),
            'attributes' => [],
        ];
    }

    return [
        'mode'       => 'cart',
        'url'        => dm_get_add_to_cart_url($product),
        'label'      => isset($args['label']) && trim((string) $args['label']) !== '' ? trim((string) $args['label']) : __('Agregar al carrito', 'daniela-child'),
        'class'      => isset($args['class']) && trim((string) $args['class']) !== '' ? trim((string) $args['class']) : 'dm-btn dm-btn--primary',
        'attributes' => [
            'data-product_id'   => (string) $product->get_id(),
            'data-product_sku'  => (string) $product->get_sku(),
            'data-quantity'     => '1',
            'data-product_name' => (string) $product->get_name(),
        ],
    ];
}

/**
 * Query published products by one or more category slugs and an optional
 * product_tag slug.
 *
 * @param string|string[] $cat_slugs  WooCommerce product_cat slug(s).
 * @param string          $tag_slug   Optional product_tag slug to narrow results.
 * @param int             $per_page   -1 for all, positive integer for limit.
 * @return WP_Query
 */
function dm_get_products($cat_slugs, $tag_slug = '', $per_page = -1)
{
    $tax_query = [
        'relation' => 'AND',
        [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => (array) $cat_slugs,
            'operator' => 'IN',
        ],
    ];

    if ($tag_slug) {
        $tax_query[] = [
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => [$tag_slug],
        ];
    }

    return new WP_Query([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'tax_query'      => $tax_query,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
}

/**
 * Return the catalog/archive excerpt coming from the WP admin excerpt metabox.
 *
 * @param WC_Product $product Product object.
 * @return string
 */
function dm_get_product_catalog_excerpt($product)
{
    if (! $product instanceof WC_Product) {
        return '';
    }

    $excerpt = trim(wp_strip_all_tags((string) get_post_field('post_excerpt', $product->get_id())));
    if ($excerpt !== '') {
        return $excerpt;
    }

    return trim(wp_strip_all_tags((string) $product->get_short_description()));
}

/**
 * Render a single product card HTML.
 *
 * @param WC_Product $product  The product to render.
 * @param string     $back_url URL to pass as ?dm_back= so single product can
 *                             show a contextual "Volver" link.
 * @return string
 */
function dm_render_product_card($product, $back_url = '')
{
    if (! $product) {
        return '';
    }

    $permalink = $back_url
        ? add_query_arg('dm_back', rawurlencode($back_url), $product->get_permalink())
        : $product->get_permalink();

    $title     = $product->get_name();
    $excerpt   = dm_get_product_catalog_excerpt($product);
    $price_raw = $product->get_price(); // '' = sin precio configurado, '0' = gratis explícito
    $is_free   = ($price_raw !== '' && (float) $price_raw <= 0.0); // phpcs:ignore WordPress.PHP.StrictComparisons

    ob_start();
?>
    <article class="dm-card">

        <?php if ($product->get_image_id()) : ?>
            <a href="<?php echo esc_url($permalink); ?>" class="dm-card__image-link" tabindex="-1" aria-hidden="true">
                <div class="dm-card__thumb">
                    <?php echo $product->get_image('woocommerce_thumbnail'); // phpcs:ignore WordPress.Security.EscapeOutput 
                    ?>
                </div>
            </a>
        <?php endif; ?>

        <div class="dm-card__body">
            <h3 class="dm-card__title">
                <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
            </h3>

            <?php if ($excerpt) : ?>
                <p class="dm-card__excerpt">
                    <?php echo wp_kses_post(wp_trim_words($excerpt, 20)); ?>
                </p>
            <?php endif; ?>

            <div class="dm-card__meta">
                <?php if ($is_free) : ?>
                    <span class="dm-badge dm-badge--free">
                        <?php esc_html_e('Gratis', 'daniela-child'); ?>
                    </span>
                <?php else : ?>
                    <span class="dm-badge dm-badge--paid">
                        <?php echo wp_kses_post($product->get_price_html()); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="dm-card__footer">
            <?php
            $btn_class = $is_free ? 'dm-btn dm-btn--secondary' : 'dm-btn dm-btn--primary';
            $cta       = dm_get_product_primary_cta($product, [
                'class'          => $btn_class,
                'waitlist_class' => 'dm-btn dm-btn--primary',
            ]);
            // Para productos de pago: respetar is_purchasable/in_stock.
            // Para productos gratis (precio = '0'): mostrar siempre el CTA.
            $show_cta  = ('waitlist' === $cta['mode']) || $is_free || ($product->is_purchasable() && $product->is_in_stock());
            if ($show_cta) :
            ?>
                <a href="<?php echo esc_url($cta['url']); ?>"
                    <?php if ('cart' === $cta['mode']) : ?>data-product_id="<?php echo esc_attr($cta['attributes']['data-product_id']); ?>"
                    data-product_sku="<?php echo esc_attr($cta['attributes']['data-product_sku']); ?>"
                    data-quantity="<?php echo esc_attr($cta['attributes']['data-quantity']); ?>"
                    data-product_name="<?php echo esc_attr($cta['attributes']['data-product_name']); ?>"
                    <?php endif; ?>class="button <?php echo 'cart' === $cta['mode'] ? 'add_to_cart_button ajax_add_to_cart ' : ''; ?><?php echo esc_attr($cta['class']); ?>">
                    <?php echo esc_html($cta['label']); ?>
                </a>
            <?php endif; ?>
        </div>

    </article>
<?php
    return ob_get_clean();
}

/**
 * Return product topic terms from WooCommerce product_tag.
 *
 * @param WC_Product|int $product Product object or product ID.
 * @return WP_Term[]
 */
function dm_get_product_topic_terms($product)
{
    $product_id = $product instanceof WC_Product ? $product->get_id() : absint($product);

    if ($product_id <= 0) {
        return [];
    }

    $topic_terms = get_the_terms($product_id, 'product_tag');

    if (is_wp_error($topic_terms) || empty($topic_terms)) {
        return [];
    }

    return $topic_terms;
}

/**
 * Render product topic tags as informational text chips.
 *
 * @param WC_Product|int $product Product object or product ID.
 * @return string
 */
function dm_get_product_topic_tags_html($product)
{
    $topic_terms = dm_get_product_topic_terms($product);

    if (empty($topic_terms)) {
        return '';
    }

    ob_start();
?>
    <ul class="dm-topic-tags" aria-label="<?php esc_attr_e('Temas', 'daniela-child'); ?>">
        <?php foreach ($topic_terms as $topic_term) : ?>
            <li>
                <span class="dm-topic-tag"><?php echo esc_html($topic_term->name); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php

    return ob_get_clean();
}

/**
 * Render a product card for topic-driven catalogs and archives.
 *
 * Used by resources and the /temas/ archive where the main navigational axis is
 * the marketing topic (`product_tag`) rather than the editorial CPT layer.
 *
 * @param WC_Product $product   Product object.
 * @param array      $args      Optional rendering args.
 */
function dm_render_topic_product_card(WC_Product $product, $args = [])
{
    $args = wp_parse_args($args, [
        'show_topic_tags' => true,
    ]);

    $product_id = $product->get_id();
    $price      = (float) $product->get_price();
    $is_free    = ($price <= 0.0); // phpcs:ignore WordPress.PHP.StrictComparisons

    $product_url = get_permalink($product_id);
    $cta         = dm_get_product_primary_cta($product, [
        'class'          => 'dm-btn dm-btn--comprar',
        'waitlist_class' => 'dm-btn dm-btn--primary',
    ]);
    $thumbnail_id  = $product->get_image_id();
    $thumbnail_url = $thumbnail_id
        ? wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail')
        : wc_placeholder_img_src('woocommerce_thumbnail');
    $price_html = $is_free ? '' : $product->get_price_html();
    $excerpt    = dm_get_product_catalog_excerpt($product);
    $topic_tags_html = $args['show_topic_tags'] ? dm_get_product_topic_tags_html($product) : '';
?>
    <li class="dm-topic-products__item">
        <article class="dm-topic-card<?php echo $is_free ? ' dm-topic-card--gratis' : ' dm-topic-card--pago'; ?>"
            aria-label="<?php echo esc_attr($product->get_name()); ?>">

            <?php if ($thumbnail_url) : ?>
                <a href="<?php echo esc_url($product_url); ?>" class="dm-topic-card__thumb-link" tabindex="-1" aria-hidden="true">
                    <img src="<?php echo esc_url($thumbnail_url); ?>"
                        alt="<?php echo esc_attr($product->get_name()); ?>"
                        class="dm-topic-card__thumb"
                        loading="lazy"
                        width="300"
                        height="300">
                </a>
            <?php endif; ?>

            <div class="dm-topic-card__body">
                <h3 class="dm-topic-card__title">
                    <a href="<?php echo esc_url($product_url); ?>">
                        <?php echo esc_html($product->get_name()); ?>
                    </a>
                </h3>

                <?php if (! empty($excerpt)) : ?>
                    <p class="dm-topic-card__excerpt">
                        <?php echo wp_kses_post($excerpt); ?>
                    </p>
                <?php endif; ?>

                <?php if ($topic_tags_html !== '') : ?>
                    <?php echo $topic_tags_html; // phpcs:ignore WordPress.Security.EscapeOutput 
                    ?>
                <?php endif; ?>

                <?php if (! $is_free && ! empty($price_html)) : ?>
                    <p class="dm-topic-card__price">
                        <?php echo wp_kses_post($price_html); ?>
                    </p>
                <?php endif; ?>

                <div class="dm-topic-card__cta">
                    <a href="<?php echo esc_url($product_url); ?>"
                        class="dm-btn dm-btn--ghost">
                        <?php esc_html_e('Ver detalles', 'daniela-child'); ?>
                    </a>
                    <?php if ('waitlist' === $cta['mode'] || $product->is_in_stock()) : ?>
                        <a href="<?php echo esc_url($cta['url']); ?>"
                            class="<?php echo esc_attr($cta['class']); ?><?php echo 'cart' === $cta['mode'] ? ' add_to_cart_button ajax_add_to_cart' : ''; ?>"
                            <?php if ('cart' === $cta['mode']) : ?>data-product_id="<?php echo esc_attr($cta['attributes']['data-product_id']); ?>"
                            data-product_sku="<?php echo esc_attr($cta['attributes']['data-product_sku']); ?>"
                            data-quantity="<?php echo esc_attr($cta['attributes']['data-quantity']); ?>"
                            data-product_name="<?php echo esc_attr($cta['attributes']['data-product_name']); ?>"
                            <?php endif; ?>>
                            <?php echo esc_html($cta['label']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div><!-- /.dm-topic-card__body -->

        </article>
    </li>
<?php
}

/**
 * Render a product grid from a WP_Query.
 *
 * @param WP_Query $query    Products query.
 * @param string   $back_url URL to pass as ?dm_back= on each card.
 * @return string
 */
function dm_render_product_grid($query, $back_url = '')
{
    if (! $query->have_posts()) {
        return '<p class="dm-no-results">' .
            esc_html__('No hay productos disponibles.', 'daniela-child') .
            '</p>';
    }

    $html = '<div class="dm-grid">';
    while ($query->have_posts()) {
        $query->the_post();
        $product = wc_get_product(get_the_ID());
        if ($product) {
            $html .= dm_render_product_card($product, $back_url);
        }
    }
    wp_reset_postdata();
    $html .= '</div>';

    return $html;
}

// =============================================================================
// CACHE INVALIDATION — topic navigation transients
// =============================================================================

/**
 * Clear the cached topic navigation whenever a product is created or updated,
 * so resources-by-topic and the Home topic hub stay accurate.
 */
add_action('save_post_product', function () {
    delete_transient('dm_temas_hub_topics');
});

add_action('woocommerce_update_product', function () {
    delete_transient('dm_temas_hub_topics');
});
