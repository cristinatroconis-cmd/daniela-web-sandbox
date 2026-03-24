<?php
/**
 * Recursos Hub — Shortcode [dm_recursos]
 *
 * Renders a filterable grid of WooCommerce products (recursos).
 *
 * Supported attributes:
 *   per_page  – products per page (default 12)
 *   columns   – grid columns hint (default 3)
 *
 * Filters:
 *   - type: gratis | pagos  (maps to product_cat slugs or _price meta)
 *   - topic: product_cat slug(s)
 *
 * Progressive enhancement: filters work via querystring (?dm_type=pagos&dm_topic=ansiedad)
 * AND via JS (no full page reload) when JS is available.
 *
 * @package daniela-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Shortcode registration
// ---------------------------------------------------------------------------

add_shortcode( 'dm_recursos', 'dm_recursos_shortcode' );

/**
 * Main shortcode callback.
 *
 * @param array $atts Shortcode attributes.
 * @return string     HTML output.
 */
function dm_recursos_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'per_page' => 12,
			'columns'  => 3,
		),
		$atts,
		'dm_recursos'
	);

	$per_page = absint( $atts['per_page'] );
	$columns  = absint( $atts['columns'] );
	if ( $columns < 1 || $columns > 6 ) {
		$columns = 3;
	}

	// --- Read active filters from querystring (sanitized) ---
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$active_type  = isset( $_GET['dm_type'] ) ? sanitize_key( $_GET['dm_type'] ) : '';
	$active_topic = isset( $_GET['dm_topic'] ) ? sanitize_key( $_GET['dm_topic'] ) : '';
	// phpcs:enable

	// Validate allowed values
	if ( ! in_array( $active_type, array( '', 'gratis', 'pagos' ), true ) ) {
		$active_type = '';
	}

	// --- Build WP_Query args ---
	$tax_query = array( 'relation' => 'AND' );

	// Base: only products in recursos-gratis or recursos-pagos categories,
	// OR any product if no category filter is applied.
	$base_cats = array( 'recursos-gratis', 'recursos-pagos' );

	// Type filter
	if ( 'gratis' === $active_type ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array( 'recursos-gratis' ),
		);
	} elseif ( 'pagos' === $active_type ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array( 'recursos-pagos' ),
		);
	} else {
		// Show all productos in recursos-gratis OR recursos-pagos
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => $base_cats,
			'operator' => 'IN',
		);
	}

	// Topic filter (additional product_cat)
	if ( $active_topic !== '' ) {
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => array( $active_topic ),
		);
	}

	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	);

	$products = new WP_Query( $query_args );

	// --- Fetch available topic categories for filter pills ---
	$topic_terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'exclude'    => dm_recursos_get_excluded_term_ids( array( 'recursos-gratis', 'recursos-pagos', 'uncategorized' ) ),
		)
	);
	if ( is_wp_error( $topic_terms ) ) {
		$topic_terms = array();
	}

	// --- Build current page URL for filter links ---
	$current_url = dm_recursos_current_url_without_filters();

	// --- Render ---
	ob_start();
	?>
	<div class="dm-recursos" data-columns="<?php echo esc_attr( $columns ); ?>">

		<?php // ---- Filter bar ---- ?>
		<div class="dm-recursos__filters" role="navigation" aria-label="<?php esc_attr_e( 'Filtros de recursos', 'daniela-child' ); ?>">

			<?php // Type filters ?>
			<div class="dm-recursos__filter-group dm-recursos__filter-group--type">
				<span class="dm-recursos__filter-label"><?php esc_html_e( 'Tipo:', 'daniela-child' ); ?></span>
				<?php
				$type_options = array(
					''       => __( 'Todos', 'daniela-child' ),
					'gratis' => __( 'Gratis', 'daniela-child' ),
					'pagos'  => __( 'Pagos', 'daniela-child' ),
				);
				foreach ( $type_options as $slug => $label ) :
					$url     = add_query_arg(
						array(
							'dm_type'  => $slug !== '' ? $slug : false,
							'dm_topic' => $active_topic !== '' ? $active_topic : false,
						),
						$current_url
					);
					$is_active = ( $slug === $active_type );
					?>
					<a href="<?php echo esc_url( $url ); ?>"
					   class="dm-filter-pill dm-filter-pill--type<?php echo $is_active ? ' is-active' : ''; ?>"
					   data-filter-type="type"
					   data-filter-value="<?php echo esc_attr( $slug ); ?>"
					   <?php echo $is_active ? 'aria-current="true"' : ''; ?>>
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<?php // Topic filters ?>
			<?php if ( ! empty( $topic_terms ) ) : ?>
			<div class="dm-recursos__filter-group dm-recursos__filter-group--topic">
				<span class="dm-recursos__filter-label"><?php esc_html_e( 'Tema:', 'daniela-child' ); ?></span>

				<?php
				// "Todos los temas"
				$all_topics_url = add_query_arg(
					array(
						'dm_type'  => $active_type !== '' ? $active_type : false,
						'dm_topic' => false,
					),
					$current_url
				);
				?>
				<a href="<?php echo esc_url( $all_topics_url ); ?>"
				   class="dm-filter-pill dm-filter-pill--topic<?php echo $active_topic === '' ? ' is-active' : ''; ?>"
				   data-filter-type="topic"
				   data-filter-value=""
				   <?php echo $active_topic === '' ? 'aria-current="true"' : ''; ?>>
					<?php esc_html_e( 'Todos', 'daniela-child' ); ?>
				</a>

				<?php foreach ( $topic_terms as $term ) : ?>
					<?php
					$term_url = add_query_arg(
						array(
							'dm_type'  => $active_type !== '' ? $active_type : false,
							'dm_topic' => $term->slug,
						),
						$current_url
					);
					$is_active_topic = ( $term->slug === $active_topic );
					?>
					<a href="<?php echo esc_url( $term_url ); ?>"
					   class="dm-filter-pill dm-filter-pill--topic<?php echo $is_active_topic ? ' is-active' : ''; ?>"
					   data-filter-type="topic"
					   data-filter-value="<?php echo esc_attr( $term->slug ); ?>"
					   <?php echo $is_active_topic ? 'aria-current="true"' : ''; ?>>
						<?php echo esc_html( $term->name ); ?>
					</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

		</div><!-- /.dm-recursos__filters -->

		<?php // ---- Product grid ---- ?>
		<?php if ( $products->have_posts() ) : ?>
		<ul class="dm-recursos__grid" role="list">
			<?php
			while ( $products->have_posts() ) :
				$products->the_post();
				global $product;
				if ( ! $product instanceof WC_Product ) {
					$product = wc_get_product( get_the_ID() );
				}
				if ( ! $product ) {
					continue;
				}
				dm_recursos_render_card( $product, $active_type );
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
		<?php else : ?>
		<p class="dm-recursos__empty">
			<?php esc_html_e( 'No hay recursos disponibles para estos filtros.', 'daniela-child' ); ?>
		</p>
		<?php endif; ?>

	</div><!-- /.dm-recursos -->
	<?php

	// Enqueue the lightweight JS enhancer (progressive enhancement)
	wp_enqueue_script( 'dm-recursos-filters' );

	return ob_get_clean();
}

// ---------------------------------------------------------------------------
// Card renderer
// ---------------------------------------------------------------------------

/**
 * Render a single product card.
 *
 * @param WC_Product $product     WooCommerce product object.
 * @param string     $active_type Current active type filter ('gratis'|'pagos'|'').
 */
function dm_recursos_render_card( WC_Product $product, $active_type ) {
	$product_id = $product->get_id();

	// Determine if the product is gratis or pagos based on its categories.
	$is_gratis = dm_recursos_is_product_gratis( $product_id );

	// Per-product custom email landing page URL (optional meta).
	$email_url = get_post_meta( $product_id, '_dm_email_landing_url', true );
	if ( empty( $email_url ) ) {
		// Fallback: product permalink.
		$email_url = get_permalink( $product_id );
	}

	$product_url = get_permalink( $product_id );
	$add_to_cart = esc_url( $product->add_to_cart_url() );

	// Thumbnail
	$thumbnail_id  = $product->get_image_id();
	$thumbnail_url = $thumbnail_id
		? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' )
		: wc_placeholder_img_src( 'woocommerce_thumbnail' );

	// Price (only for pagos)
	$price_html = $is_gratis ? '' : $product->get_price_html();

	// Excerpt
	$excerpt = $product->get_short_description();
	if ( empty( $excerpt ) ) {
		$excerpt = wp_trim_words( $product->get_description(), 15 );
	}
	?>
	<li class="dm-recursos__item">
		<article class="dm-recurso-card<?php echo $is_gratis ? ' dm-recurso-card--gratis' : ' dm-recurso-card--pago'; ?>"
		         aria-label="<?php echo esc_attr( $product->get_name() ); ?>">

			<?php if ( $thumbnail_url ) : ?>
			<a href="<?php echo esc_url( $product_url ); ?>" class="dm-recurso-card__thumb-link" tabindex="-1" aria-hidden="true">
				<img src="<?php echo esc_url( $thumbnail_url ); ?>"
				     alt="<?php echo esc_attr( $product->get_name() ); ?>"
				     class="dm-recurso-card__thumb"
				     loading="lazy"
				     width="300"
				     height="300">
			</a>
			<?php endif; ?>

			<div class="dm-recurso-card__body">
				<h3 class="dm-recurso-card__title">
					<a href="<?php echo esc_url( $product_url ); ?>">
						<?php echo esc_html( $product->get_name() ); ?>
					</a>
				</h3>

				<?php if ( ! empty( $excerpt ) ) : ?>
				<p class="dm-recurso-card__excerpt">
					<?php echo wp_kses_post( $excerpt ); ?>
				</p>
				<?php endif; ?>

				<?php if ( ! $is_gratis && ! empty( $price_html ) ) : ?>
				<p class="dm-recurso-card__price">
					<?php echo wp_kses_post( $price_html ); ?>
				</p>
				<?php endif; ?>

				<div class="dm-recurso-card__cta">
					<?php if ( $is_gratis ) : ?>
						<a href="<?php echo esc_url( $email_url ); ?>"
						   class="dm-btn dm-btn--gratis">
							<?php esc_html_e( 'Recíbelo por email', 'daniela-child' ); ?>
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $add_to_cart ); ?>"
						   class="dm-btn dm-btn--comprar">
							<?php esc_html_e( 'Comprar y descargar', 'daniela-child' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div><!-- /.dm-recurso-card__body -->

		</article>
	</li>
	<?php
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Determine if a product belongs to the "recursos-gratis" category.
 *
 * @param int $product_id Product post ID.
 * @return bool
 */
function dm_recursos_is_product_gratis( $product_id ) {
	return has_term( 'recursos-gratis', 'product_cat', $product_id );
}

/**
 * Return the current page URL without DM filter querystring params.
 *
 * @return string URL without dm_type / dm_topic params.
 */
function dm_recursos_current_url_without_filters() {
	global $wp;
	$url = home_url( add_query_arg( array(), $wp->request ) );

	// Remove our own filter params to build clean base URL.
	$url = remove_query_arg( array( 'dm_type', 'dm_topic' ), $url );

	return $url;
}

/**
 * Given an array of term slugs, return their IDs (for exclusion in get_terms).
 *
 * @param string[] $slugs Array of taxonomy term slugs.
 * @return int[]          Array of term IDs.
 */
function dm_recursos_get_excluded_term_ids( array $slugs ) {
	$ids = array();
	foreach ( $slugs as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$ids[] = $term->term_id;
		}
	}
	return $ids;
}

// ---------------------------------------------------------------------------
// Enqueue resources hub JS (progressive enhancement)
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'dm_recursos_enqueue_assets' );

/**
 * Register (but do not enqueue) the filter JS.
 * The shortcode callback enqueues it when actually used.
 */
function dm_recursos_enqueue_assets() {
	wp_register_script(
		'dm-recursos-filters',
		get_stylesheet_directory_uri() . '/js/recursos-filters.js',
		array(),
		'1.0.0',
		true
	);
}
