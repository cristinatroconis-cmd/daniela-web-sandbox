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
 *   - topic: product_tag slug(s)   (?dm_topic=ansiedad)
 *
 * No gratis/pagos type filter — navigation is by topic only (per UX requirement).
 * CTA gratis/pagos: "Agregar al carrito" para continuar por checkout WooCommerce.
 *
 * Progressive enhancement: filters work via querystring (?dm_topic=ansiedad)
 * AND via JS (no full page reload) when JS is available.
 *
 * @package daniela-child
 */

if (! defined('ABSPATH')) {
	exit;
}

// ---------------------------------------------------------------------------
// Shortcode registration
// ---------------------------------------------------------------------------

add_shortcode('dm_recursos', 'dm_recursos_shortcode');

/**
 * Main shortcode callback.
 *
 * @param array $atts Shortcode attributes.
 * @return string     HTML output.
 */
function dm_recursos_shortcode($atts)
{
	$atts = shortcode_atts(
		array(
			'per_page' => 12,
			'columns'  => 3,
		),
		$atts,
		'dm_recursos'
	);

	$per_page = absint($atts['per_page']);
	$columns  = absint($atts['columns']);
	if ($columns < 1 || $columns > 6) {
		$columns = 3;
	}

	// --- Read active topic filter from querystring (sanitized) ---
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_topic = isset($_GET['dm_topic']) ? sanitize_key($_GET['dm_topic']) : '';

	// --- Build WP_Query args ---
	$tax_query = array('relation' => 'AND');

	// Topic filter: uses product_tag taxonomy.
	if ($active_topic !== '') {
		$tax_query[] = array(
			'taxonomy' => 'product_tag',
			'field'    => 'slug',
			'terms'    => array($active_topic),
		);
	}

	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
	);

	if (count($tax_query) > 1) {
		$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$products = new WP_Query($query_args);

	// --- Fetch available topic tags for filter pills ---
	$topic_terms = get_terms(
		array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => true,
		)
	);
	if (is_wp_error($topic_terms)) {
		$topic_terms = array();
	}

	// --- Build current page URL for filter links ---
	$current_url = dm_recursos_current_url_without_filters();

	// --- Render ---
	ob_start();
?>
	<div class="dm-recursos" data-columns="<?php echo esc_attr($columns); ?>">

		<?php // ---- Filter bar ---- 
		?>
		<?php if (! empty($topic_terms)) : ?>
			<div class="dm-recursos__filters" role="navigation" aria-label="<?php esc_attr_e('Filtros de recursos', 'daniela-child'); ?>">

				<?php // Topic filters 
				?>
				<div class="dm-recursos__filter-group dm-recursos__filter-group--topic">
					<span class="dm-recursos__filter-label"><?php esc_html_e('Tema:', 'daniela-child'); ?></span>

					<?php
					// "Todos los temas"
					$all_topics_url = remove_query_arg('dm_topic', $current_url);
					?>
					<a href="<?php echo esc_url($all_topics_url); ?>"
						class="dm-filter-pill dm-filter-pill--topic<?php echo $active_topic === '' ? ' is-active' : ''; ?>"
						data-filter-type="topic"
						data-filter-value=""
						<?php echo $active_topic === '' ? 'aria-current="true"' : ''; ?>>
						<?php esc_html_e('Todos', 'daniela-child'); ?>
					</a>

					<?php foreach ($topic_terms as $term) : ?>
						<?php
						$term_url = add_query_arg('dm_topic', $term->slug, $current_url);
						$is_active_topic = ($term->slug === $active_topic);
						?>
						<a href="<?php echo esc_url($term_url); ?>"
							class="dm-filter-pill dm-filter-pill--topic<?php echo $is_active_topic ? ' is-active' : ''; ?>"
							data-filter-type="topic"
							data-filter-value="<?php echo esc_attr($term->slug); ?>"
							<?php echo $is_active_topic ? 'aria-current="true"' : ''; ?>>
							<?php echo esc_html($term->name); ?>
						</a>
					<?php endforeach; ?>
				</div>

			</div><!-- /.dm-recursos__filters -->
		<?php endif; ?>

		<?php // ---- Product grid ---- 
		?>
		<?php if ($products->have_posts()) : ?>
			<ul class="dm-recursos__grid" role="list">
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
					dm_recursos_render_card($product);
				endwhile;
				wp_reset_postdata();
				?>
			</ul>
		<?php else : ?>
			<p class="dm-recursos__empty">
				<?php esc_html_e('No hay recursos disponibles para estos filtros.', 'daniela-child'); ?>
			</p>
		<?php endif; ?>

	</div><!-- /.dm-recursos -->
<?php

	// Enqueue the lightweight JS enhancer (progressive enhancement)
	wp_enqueue_script('dm-recursos-filters');

	return ob_get_clean();
}

// ---------------------------------------------------------------------------
// Card renderer
// ---------------------------------------------------------------------------

/**
 * Render a single product card.
 *
 * CTA logic:
 *  - Price 0 (gratis): "Agregar al carrito" → checkout (sin pago si el carrito es solo gratis).
 *  - Price > 0 (pago): "Agregar al carrito" → checkout normal.
 *
 * @param WC_Product $product WooCommerce product object.
 */
function dm_recursos_render_card(WC_Product $product)
{
	$product_id = $product->get_id();

	// Determine gratis/pago by price.
	$price     = (float) $product->get_price();
	$is_gratis = ($price <= 0.0); // phpcs:ignore WordPress.PHP.StrictComparisons

	$product_url = get_permalink($product_id);
	$add_to_cart = function_exists('dm_get_add_to_cart_url')
		? dm_get_add_to_cart_url($product)
		: (string) $product->add_to_cart_url();
	$thumbnail_id  = $product->get_image_id();
	$thumbnail_url = $thumbnail_id
		? wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail')
		: wc_placeholder_img_src('woocommerce_thumbnail');

	// Price (only for pagos)
	$price_html = $is_gratis ? '' : $product->get_price_html();

	// Excerpt del metabox de resumen en WP Admin.
	$excerpt = function_exists('dm_get_product_catalog_excerpt')
		? dm_get_product_catalog_excerpt($product)
		: trim(wp_strip_all_tags((string) get_post_field('post_excerpt', $product_id)));

	// Topic tags (product_tag terms) for display
	$topic_tags = get_the_terms($product_id, 'product_tag');
	if (is_wp_error($topic_tags)) {
		$topic_tags = [];
	}
?>
	<li class="dm-recursos__item">
		<article class="dm-recurso-card<?php echo $is_gratis ? ' dm-recurso-card--gratis' : ' dm-recurso-card--pago'; ?>"
			aria-label="<?php echo esc_attr($product->get_name()); ?>">

			<?php if ($thumbnail_url) : ?>
				<a href="<?php echo esc_url($product_url); ?>" class="dm-recurso-card__thumb-link" tabindex="-1" aria-hidden="true">
					<img src="<?php echo esc_url($thumbnail_url); ?>"
						alt="<?php echo esc_attr($product->get_name()); ?>"
						class="dm-recurso-card__thumb"
						loading="lazy"
						width="300"
						height="300">
				</a>
			<?php endif; ?>

			<div class="dm-recurso-card__body">
				<h3 class="dm-recurso-card__title">
					<a href="<?php echo esc_url($product_url); ?>">
						<?php echo esc_html($product->get_name()); ?>
					</a>
				</h3>

				<?php if (! empty($excerpt)) : ?>
					<p class="dm-recurso-card__excerpt">
						<?php echo wp_kses_post($excerpt); ?>
					</p>
				<?php endif; ?>

				<?php if (! empty($topic_tags)) : ?>
					<ul class="dm-recurso-card__tags" aria-label="<?php esc_attr_e('Temas', 'daniela-child'); ?>">
						<?php foreach ($topic_tags as $tag) : ?>
							<li>
								<a
									class="dm-recurso-card__tag"
									href="<?php echo esc_url(add_query_arg('dm_topic', $tag->slug, home_url('/recursos/'))); ?>">
									<?php echo esc_html($tag->name); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if (! $is_gratis && ! empty($price_html)) : ?>
					<p class="dm-recurso-card__price">
						<?php echo wp_kses_post($price_html); ?>
					</p>
				<?php endif; ?>

				<div class="dm-recurso-card__cta">
					<a href="<?php echo esc_url($product_url); ?>"
						class="dm-btn dm-btn--ghost">
						<?php esc_html_e('Ver detalles', 'daniela-child'); ?>
					</a>
					<?php if ($product->is_in_stock()) : ?>
						<a href="<?php echo esc_url($add_to_cart); ?>"
							class="dm-btn dm-btn--comprar add_to_cart_button ajax_add_to_cart"
							data-product_id="<?php echo esc_attr($product_id); ?>"
							data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
							data-quantity="1"
							data-product_name="<?php echo esc_attr($product->get_name()); ?>">
							<?php esc_html_e('Agregar al carrito', 'daniela-child'); ?>
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
 * Return the current page URL without DM filter querystring params.
 *
 * @return string URL without dm_topic param.
 */
function dm_recursos_current_url_without_filters()
{
	global $wp;
	$url = home_url(add_query_arg(array(), $wp->request));

	// Remove our own filter params to build clean base URL.
	$url = remove_query_arg(array('dm_topic'), $url);

	return $url;
}

// ---------------------------------------------------------------------------
// Enqueue resources hub JS (progressive enhancement)
// ---------------------------------------------------------------------------

add_action('wp_enqueue_scripts', 'dm_recursos_enqueue_assets');

/**
 * Register (but do not enqueue) the filter JS.
 * The shortcode callback enqueues it when actually used.
 */
function dm_recursos_enqueue_assets()
{
	$js_file = get_stylesheet_directory() . '/js/recursos-filters.js';
	wp_register_script(
		'dm-recursos-filters',
		get_stylesheet_directory_uri() . '/js/recursos-filters.js',
		array(),
		file_exists($js_file) ? (string) filemtime($js_file) : '1.0.0',
		true
	);
}
