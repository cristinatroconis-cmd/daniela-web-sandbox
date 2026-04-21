<?php

/**
 * Temas Archive Products — products grouped by product_tag (topics).
 *
 * Powers the /temas/ archive (CPT dm_temas).
 * Renders a vertical-column layout: one section per product_tag, each with
 * a grid of topic-driven product cards.
 *
 * Performance: per-tag queries are individually small (capped at DM_TEMAS_PER_TAG)
 * and the full result set is cached in a transient for one hour.
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
	exit;
}

/** Maximum products to show per tag section. */
if (! defined('DM_TEMAS_PER_TAG')) {
	define('DM_TEMAS_PER_TAG', 12);
}

// =============================================================================
// DATA LAYER
// =============================================================================

/**
 * Return all product_tags (that have at least one published product) together
 * with the products for each tag, ordered alphabetically by tag name.
 *
 * Results are cached in a transient for one hour to avoid repeated queries.
 *
 * @return array[] Array of [ 'tag' => WP_Term, 'products' => WC_Product[] ].
 */
function dm_temas_get_sections()
{
	if (! function_exists('WC')) {
		return [];
	}

	$transient_key = 'dm_temas_sections';
	$cached        = get_transient($transient_key);

	if (false !== $cached) {
		return $cached;
	}

	$tags = get_terms(
		[
			'taxonomy'   => 'product_tag',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		]
	);

	if (is_wp_error($tags) || empty($tags)) {
		set_transient($transient_key, [], HOUR_IN_SECONDS);
		return [];
	}

	$sections = [];

	foreach ($tags as $tag) {
		$query = new WP_Query(
			[
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => DM_TEMAS_PER_TAG,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'tax_query'      => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					[
						'taxonomy' => 'product_tag',
						'field'    => 'slug',
						'terms'    => $tag->slug,
					],
				],
			]
		);

		if (! $query->have_posts()) {
			continue;
		}

		$products = [];
		while ($query->have_posts()) {
			$query->the_post();
			$product = wc_get_product(get_the_ID());
			if ($product) {
				$products[] = $product;
			}
		}
		wp_reset_postdata();

		if (! empty($products)) {
			$sections[] = [
				'tag'      => $tag,
				'products' => $products,
			];
		}
	}

	set_transient($transient_key, $sections, HOUR_IN_SECONDS);

	return $sections;
}

// =============================================================================
// RENDER
// =============================================================================

/**
 * Render the full /temas/ page content.
 *
 * Each product_tag with at least one product gets its own section with a heading
 * and a responsive grid of topic-driven product cards.
 *
 * @return string HTML.
 */
function dm_temas_render_all()
{
	if (! function_exists('WC')) {
		return '';
	}

	$sections = dm_temas_get_sections();

	if (empty($sections)) {
		return '<p class="dm-no-results">' .
			esc_html__('No hay temas disponibles.', 'daniela-child') .
			'</p>';
	}

	ob_start();

	foreach ($sections as $section) :
		$tag      = $section['tag'];
		$products = $section['products'];
?>
		<section class="dm-temas__section" id="tema-<?php echo esc_attr($tag->slug); ?>">

			<header class="dm-temas__section-header">
				<h2 class="dm-temas__section-title"><?php echo esc_html($tag->name); ?></h2>
				<?php if (! empty($tag->description)) : ?>
					<p class="dm-temas__section-desc"><?php echo esc_html($tag->description); ?></p>
				<?php endif; ?>
			</header>

			<ul class="dm-topic-products__grid" role="list">
				<?php foreach ($products as $product) : ?>
					<?php dm_render_topic_product_card($product, ['show_topic_tags' => false]); ?>
				<?php endforeach; ?>
			</ul>

		</section>
<?php
	endforeach;

	return ob_get_clean();
}

// =============================================================================
// CACHE INVALIDATION
// =============================================================================

/**
 * Clear the temas transient when any product is created/updated so that
 * tag groupings and product lists stay accurate.
 */
add_action(
	'save_post_product',
	function () {
		delete_transient('dm_temas_sections');
	}
);

add_action(
	'woocommerce_update_product',
	function () {
		delete_transient('dm_temas_sections');
	}
);
