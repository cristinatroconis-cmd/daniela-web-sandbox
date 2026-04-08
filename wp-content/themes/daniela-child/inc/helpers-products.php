<?php
/**
 * Product helpers — query, card, grid, and cache invalidation.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
function dm_get_products( $cat_slugs, $tag_slug = '', $per_page = -1 ) {
    $tax_query = [
        'relation' => 'AND',
        [
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => (array) $cat_slugs,
            'operator' => 'IN',
        ],
    ];

    if ( $tag_slug ) {
        $tax_query[] = [
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => [ $tag_slug ],
        ];
    }

    return new WP_Query( [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'tax_query'      => $tax_query,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );
}

/**
 * Render a single product card HTML.
 *
 * @param WC_Product $product  The product to render.
 * @param string     $back_url URL to pass as ?dm_back= so single product can
 *                             show a contextual "Volver" link.
 * @return string
 */
function dm_render_product_card( $product, $back_url = '' ) {
    if ( ! $product ) {
        return '';
    }

    $permalink = $back_url
        ? add_query_arg( 'dm_back', rawurlencode( $back_url ), $product->get_permalink() )
        : $product->get_permalink();

    $title     = $product->get_name();
    $excerpt   = $product->get_short_description();
    $price_raw = $product->get_price(); // '' = sin precio configurado, '0' = gratis explícito
    $is_free   = ( $price_raw !== '' && (float) $price_raw <= 0.0 ); // phpcs:ignore WordPress.PHP.StrictComparisons

    ob_start();
    ?>
    <article class="dm-card">

        <?php if ( $product->get_image_id() ) : ?>
        <a href="<?php echo esc_url( $permalink ); ?>" class="dm-card__image-link" tabindex="-1" aria-hidden="true">
            <div class="dm-card__thumb">
                <?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </div>
        </a>
        <?php endif; ?>

        <div class="dm-card__body">
            <h3 class="dm-card__title">
                <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
            </h3>

            <?php if ( $excerpt ) : ?>
                <p class="dm-card__excerpt">
                    <?php echo wp_kses_post( wp_trim_words( $excerpt, 20 ) ); ?>
                </p>
            <?php endif; ?>

            <div class="dm-card__meta">
                <?php if ( $is_free ) : ?>
                    <span class="dm-badge dm-badge--free">
                        <?php esc_html_e( 'Gratis', 'daniela-child' ); ?>
                    </span>
                <?php else : ?>
                    <span class="dm-badge dm-badge--paid">
                        <?php echo wp_kses_post( $product->get_price_html() ); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="dm-card__footer">
            <?php
            $btn_class = $is_free ? 'dm-btn dm-btn--secondary' : 'dm-btn dm-btn--primary';
            // Para productos de pago: respetar is_purchasable/in_stock.
            // Para productos gratis (precio = '0'): mostrar siempre el CTA.
            $show_cta  = $is_free || ( $product->is_purchasable() && $product->is_in_stock() );
            if ( $show_cta ) :
            ?>
            <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
               data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
               data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
               data-quantity="1"
               class="button add_to_cart_button ajax_add_to_cart <?php echo esc_attr( $btn_class ); ?>">
                <?php esc_html_e( 'Agregar al carrito', 'daniela-child' ); ?>
            </a>
            <?php endif; ?>
        </div>

    </article>
    <?php
    return ob_get_clean();
}

/**
 * Render a product grid from a WP_Query.
 *
 * @param WP_Query $query    Products query.
 * @param string   $back_url URL to pass as ?dm_back= on each card.
 * @return string
 */
function dm_render_product_grid( $query, $back_url = '' ) {
    if ( ! $query->have_posts() ) {
        return '<p class="dm-no-results">' .
               esc_html__( 'No hay productos disponibles.', 'daniela-child' ) .
               '</p>';
    }

    $html = '<div class="dm-grid">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $product = wc_get_product( get_the_ID() );
        if ( $product ) {
            $html .= dm_render_product_card( $product, $back_url );
        }
    }
    wp_reset_postdata();
    $html .= '</div>';

    return $html;
}

// =============================================================================
// CACHE INVALIDATION — temas tags transient
// =============================================================================

/**
 * Clear the cached temas chips whenever a product is created or updated,
 * so tag counts and visibility stay accurate.
 */
add_action( 'save_post_product', function () {
    delete_transient( 'dm_recursos_temas_tags' );
} );

add_action( 'woocommerce_update_product', function () {
    delete_transient( 'dm_recursos_temas_tags' );
} );
