<?php

/**
 * Products Listing Shortcode — [dm_products]
 *
 * Lists WooCommerce products filtered by a product_cat slug.
 * Useful for "Cursos", "Talleres", "Sesiones", etc. pages.
 *
 * Usage:
 *   [dm_products category="cursos"]
 *   [dm_products category="talleres" per_page="6" columns="2"]
 *
 * Supported attributes:
 *   category  – product_cat slug (required; falls back to showing nothing)
 *   per_page  – number of products to show (default 12)
 *   columns   – grid columns hint for CSS (default 3)
 *   orderby   – WP_Query orderby (default 'menu_order')
 *   order     – ASC or DESC (default 'ASC')
 *
 * @package daniela-child
 */

if (! defined('ABSPATH')) {
	exit;
}

add_shortcode('dm_products', 'dm_products_shortcode');

/**
 * Render a grid of products from a given product_cat.
 *
 * @param array $atts Shortcode attributes.
 * @return string     HTML output.
 */
function dm_products_shortcode($atts)
{
	$atts = shortcode_atts(
		array(
			'category' => '',
			'per_page' => 12,
			'columns'  => 3,
			'orderby'  => 'menu_order',
			'order'    => 'ASC',
		),
		$atts,
		'dm_products'
	);

	$category = sanitize_title($atts['category']);
	$per_page = absint($atts['per_page']);
	$columns  = absint($atts['columns']);
	$orderby  = sanitize_key($atts['orderby']);
	$order    = strtoupper(sanitize_key($atts['order']));

	if (! in_array($order, array('ASC', 'DESC'), true)) {
		$order = 'ASC';
	}

	if ($columns < 1 || $columns > 6) {
		$columns = 3;
	}

	if (empty($category)) {
		return '<p class="dm-products__error">' . esc_html__('Shortcode dm_products: falta el atributo "category".', 'daniela-child') . '</p>';
	}

	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'orderby'        => $orderby,
		'order'          => $order,
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => array($category),
			),
		),
	);

	$products = new WP_Query($query_args);

	ob_start();

	if (! $products->have_posts()) {
		echo '<p class="dm-products__empty">' . esc_html__('No hay productos disponibles en esta categoría.', 'daniela-child') . '</p>';
		wp_reset_postdata();
		return ob_get_clean();
	}
?>
	<ul class="dm-products-grid dm-products-grid--cols-<?php echo esc_attr($columns); ?>" role="list">
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
			dm_products_render_card($product);
		endwhile;
		wp_reset_postdata();
		?>
	</ul>
<?php

	return ob_get_clean();
}

// ---------------------------------------------------------------------------
// Card renderer
// ---------------------------------------------------------------------------

/**
 * Render a single product card for the [dm_products] grid.
 *
 * @param WC_Product $product WooCommerce product object.
 */
function dm_products_render_card(WC_Product $product)
{
	$product_id    = $product->get_id();
	$add_to_cart   = function_exists('dm_get_add_to_cart_url')
		? dm_get_add_to_cart_url($product)
		: (string) $product->add_to_cart_url();
	$product_url   = get_permalink($product->get_id());
	$thumbnail_id  = $product->get_image_id();
	$thumbnail_url = $thumbnail_id
		? wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail')
		: wc_placeholder_img_src('woocommerce_thumbnail');

	$excerpt = function_exists('dm_get_product_catalog_excerpt')
		? dm_get_product_catalog_excerpt($product)
		: trim(wp_strip_all_tags((string) get_post_field('post_excerpt', $product_id)));

	$price_html = $product->get_price_html();
?>
	<li class="dm-products-grid__item">
		<article class="dm-product-card" aria-label="<?php echo esc_attr($product->get_name()); ?>">

			<?php if ($thumbnail_url) : ?>
				<a href="<?php echo esc_url($product_url); ?>" class="dm-product-card__thumb-link" tabindex="-1" aria-hidden="true">
					<img src="<?php echo esc_url($thumbnail_url); ?>"
						alt="<?php echo esc_attr($product->get_name()); ?>"
						class="dm-product-card__thumb"
						loading="lazy"
						width="300"
						height="300">
				</a>
			<?php endif; ?>

			<div class="dm-product-card__body">
				<h3 class="dm-product-card__title">
					<a href="<?php echo esc_url($product_url); ?>">
						<?php echo esc_html($product->get_name()); ?>
					</a>
				</h3>

				<?php if (! empty($excerpt)) : ?>
					<p class="dm-product-card__excerpt">
						<?php echo wp_kses_post($excerpt); ?>
					</p>
				<?php endif; ?>

				<?php if (! empty($price_html)) : ?>
					<p class="dm-product-card__price">
						<?php echo wp_kses_post($price_html); ?>
					</p>
				<?php endif; ?>

				<a href="<?php echo esc_url($product_url); ?>"
					class="dm-btn dm-btn--ver-mas">
					<?php esc_html_e('Ver más', 'daniela-child'); ?>
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
			</div><!-- /.dm-product-card__body -->

		</article>
	</li>
<?php
}
