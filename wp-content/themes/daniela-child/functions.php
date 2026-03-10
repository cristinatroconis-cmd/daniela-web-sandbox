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
        '0.2.0'
    );
}, 20);

/**
 * Ensure WooCommerce AJAX add-to-cart scripts are loaded on pages that
 * use our product-listing shortcodes.
 */
add_action('wp_enqueue_scripts', function () {
    global $post;
    if (!is_a($post, 'WP_Post') || !function_exists('WC')) {
        return;
    }

    $dm_shortcodes = [
        'dm_recursos_gratis',
        'dm_recursos_pagos',
        'dm_recursos_temas',
        'dm_escuela_cursos',
        'dm_escuela_talleres',
    ];

    foreach ($dm_shortcodes as $sc) {
        if (has_shortcode($post->post_content, $sc)) {
            wp_enqueue_script('woocommerce');
            wp_enqueue_script('wc-add-to-cart');
            break;
        }
    }
}, 25);

// =============================================================================
// HELPERS
// =============================================================================

/**
 * Query published products by one or more category slugs and an optional
 * product_tag slug.
 *
 * @param string|string[] $cat_slugs   WooCommerce product_cat slug(s).
 * @param string          $tag_slug    Optional product_tag slug to narrow results.
 * @param int             $per_page    -1 for all, positive integer for limit.
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

    $title    = $product->get_name();
    $excerpt  = $product->get_short_description();
    $price    = (float) $product->get_price();
    $is_free  = ( $price == 0 );

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
            <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
               data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
               data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
               class="button add_to_cart_button ajax_add_to_cart dm-btn dm-btn--primary">
                <?php esc_html_e( 'Agregar al carrito', 'daniela-child' ); ?>
            </a>
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
// SHORTCODES — RECURSOS
// =============================================================================

/**
 * [dm_recursos_gratis]
 * Lists all published products in the recursos-gratis category.
 * Place this shortcode on the /recursos/gratis/ page.
 */
add_shortcode( 'dm_recursos_gratis', function () {
    if ( ! function_exists( 'WC' ) ) {
        return '';
    }
    $back_url = home_url( '/recursos/gratis/' );
    $query    = dm_get_products( 'recursos-gratis' );
    return dm_render_product_grid( $query, $back_url );
} );

/**
 * [dm_recursos_pagos]
 * Lists all published products in the recursos-pagos category.
 * Place this shortcode on the /recursos/pagos/ page.
 */
add_shortcode( 'dm_recursos_pagos', function () {
    if ( ! function_exists( 'WC' ) ) {
        return '';
    }
    $back_url = home_url( '/recursos/pagos/' );
    $query    = dm_get_products( 'recursos-pagos' );
    return dm_render_product_grid( $query, $back_url );
} );

/**
 * [dm_recursos_temas]
 * Shows a chips (pill) navigation filtered by WooCommerce product_tag.
 *
 * Rules:
 * - Only shows tags that have ≥1 product in recursos-gratis OR recursos-pagos.
 * - Chips order: count of qualifying products desc, then alphabetical.
 * - Non-JS fallback: ?tema=<slug> (querystring).
 * - Active chip reflected in URL.
 *
 * Place this shortcode on the /recursos/temas/ page.
 */
add_shortcode( 'dm_recursos_temas', function () {
    if ( ! function_exists( 'WC' ) ) {
        return '';
    }

    $current_page = home_url( '/recursos/temas/' );
    $active_slug  = isset( $_GET['tema'] ) // phpcs:ignore WordPress.Security.NonceVerification
        ? sanitize_title( wp_unslash( $_GET['tema'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
        : '';

    // -------------------------------------------------------------------------
    // Build chips list: tags used by ≥1 product in our recursos categories.
    // Results are transient-cached (1 hour) and invalidated on product save.
    // -------------------------------------------------------------------------
    $transient_key = 'dm_recursos_temas_tags';
    $recursos_tags = get_transient( $transient_key );

    if ( false === $recursos_tags ) {
        $recursos_tags = [];

        $all_tags = get_terms( [
            'taxonomy'   => 'product_tag',
            'hide_empty' => true,
        ] );

        if ( ! is_wp_error( $all_tags ) ) {
            // Resolve category term IDs once (avoid repeated taxonomy look-ups).
            $cat_ids = get_terms( [
                'taxonomy'   => 'product_cat',
                'slug'       => [ 'recursos-gratis', 'recursos-pagos' ],
                'fields'     => 'ids',
                'hide_empty' => false,
            ] );

            if ( is_wp_error( $cat_ids ) ) {
                $cat_ids = [];
            }

            foreach ( $all_tags as $tag ) {
                $count_query = new WP_Query( [
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'no_found_rows'  => false,
                    'tax_query'      => [
                        'relation' => 'AND',
                        [
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => $cat_ids,
                            'operator' => 'IN',
                        ],
                        [
                            'taxonomy' => 'product_tag',
                            'field'    => 'term_id',
                            'terms'    => [ $tag->term_id ],
                        ],
                    ],
                ] );

                if ( $count_query->found_posts > 0 ) {
                    $tag->recursos_count = (int) $count_query->found_posts;
                    $recursos_tags[]     = $tag;
                }
            }

            // Sort: recursos_count desc, then name asc (alphabetical tie-break).
            usort( $recursos_tags, function ( $a, $b ) {
                if ( $b->recursos_count !== $a->recursos_count ) {
                    return $b->recursos_count - $a->recursos_count;
                }
                return strcmp( $a->name, $b->name );
            } );
        }

        set_transient( $transient_key, $recursos_tags, HOUR_IN_SECONDS );
    }

    // -------------------------------------------------------------------------
    // Render chips + product grid.
    // -------------------------------------------------------------------------
    $back_url = $active_slug
        ? add_query_arg( 'tema', $active_slug, $current_page )
        : $current_page;

    ob_start();

    if ( ! empty( $recursos_tags ) ) : ?>
        <nav class="dm-chips" aria-label="<?php esc_attr_e( 'Filtrar por tema', 'daniela-child' ); ?>">
            <a href="<?php echo esc_url( $current_page ); ?>"
               class="dm-chip<?php echo $active_slug === '' ? ' dm-chip--active' : ''; ?>">
                <?php esc_html_e( 'Todos', 'daniela-child' ); ?>
            </a>
            <?php foreach ( $recursos_tags as $tag ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'tema', $tag->slug, $current_page ) ); ?>"
                   class="dm-chip<?php echo $active_slug === $tag->slug ? ' dm-chip--active' : ''; ?>">
                    <?php echo esc_html( $tag->name ); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif;

    $query = dm_get_products( [ 'recursos-gratis', 'recursos-pagos' ], $active_slug );
    echo dm_render_product_grid( $query, $back_url ); // phpcs:ignore WordPress.Security.EscapeOutput

    return ob_get_clean();
} );

// =============================================================================
// SHORTCODES — ESCUELA
// =============================================================================

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

// =============================================================================
// SINGLE PRODUCT — "VOLVER" LINK
// =============================================================================

/**
 * Display a contextual "Volver" link above the single product content.
 *
 * Priority order:
 * 1. ?dm_back= query param set by our listing pages (validated to own host).
 * 2. wp_get_referer() if it points to our own domain.
 * 3. Fallback to /recursos/.
 */
add_action( 'woocommerce_before_single_product', function () {
    $back_url = '';

    // 1. Query param passed by listing shortcodes.
    if ( ! empty( $_GET['dm_back'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
        $candidate = esc_url_raw( urldecode( wp_unslash( $_GET['dm_back'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
        // wp_validate_redirect ensures the URL stays on the same host.
        $back_url = wp_validate_redirect( $candidate, '' );
    }

    // 2. Browser referer (same host only, already checked by wp_validate_redirect).
    if ( ! $back_url ) {
        $referer  = wp_get_referer();
        $back_url = $referer ? wp_validate_redirect( $referer, '' ) : '';
    }

    // 3. Fallback.
    if ( ! $back_url ) {
        $back_url = home_url( '/recursos/' );
    }

    echo '<a href="' . esc_url( $back_url ) . '" class="dm-back-link">&#8592; ' .
         esc_html__( 'Volver', 'daniela-child' ) . '</a>';
}, 5 );

// =============================================================================
// CART → CHECKOUT REDIRECT (free-only cart)
// =============================================================================

/**
 * If the cart is not empty and the total is 0 (all items free),
 * automatically redirect from the cart page to checkout.
 *
 * This removes friction for free resources: users never need to "review" a
 * $0 cart — they go straight to getting their download.
 */
add_action( 'template_redirect', function () {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $cart = WC()->cart;

    if ( is_cart() && ! $cart->is_empty() && (float) $cart->get_total( 'edit' ) == 0 ) {
        wp_safe_redirect( wc_get_checkout_url() );
        exit;
    }
} );

// =============================================================================
// CHECKOUT — NEWSLETTER OPT-IN
// =============================================================================

/**
 * Display an unchecked newsletter consent checkbox in checkout (after order
 * notes). Consent is not pre-selected (GDPR/best-practice).
 */
add_action( 'woocommerce_after_order_notes', function ( $checkout ) {
    if ( ! function_exists( 'WC' ) ) {
        return;
    }

    echo '<div id="dm-newsletter-optin" class="dm-newsletter-optin">';
    woocommerce_form_field( 'dm_newsletter_optin', [
        'type'     => 'checkbox',
        'class'    => [ 'form-row-wide' ],
        'label'    => __( 'Sí, quiero suscribirme al newsletter (puedes darte de baja cuando quieras).', 'daniela-child' ),
        'required' => false,
    ], $checkout->get_value( 'dm_newsletter_optin' ) );
    echo '</div>';
}, 20 );

/**
 * Persist the newsletter opt-in choice in the order meta for audit trails
 * and future MailerLite sync.
 */
add_action( 'woocommerce_checkout_create_order', function ( $order, $data ) {
    if ( ! function_exists( 'WC' ) ) {
        return;
    }
    $optin = ! empty( $_POST['dm_newsletter_optin'] ) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification
    $order->update_meta_data( '_dm_newsletter_optin', $optin );
}, 20, 2 );

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
