<?php

/**
 * Archive template — dm_temas (Temas page).
 *
 * URL: /temas/
 *
 * Displays WooCommerce products grouped by product_tag.
 * Card rendering delegates to the neutral topic-product card helper and
 * section data is provided by dm_temas_render_all().
 *
 * @package Daniela_Child
 */

get_header();
?>

<main id="main" class="site-main dm-archive dm-archive--temas">

	<header class="dm-archive__header">
		<div class="dm-archive__header-inner">
			<h1 class="dm-archive__title">
				<?php esc_html_e('Temas', 'daniela-child'); ?>
			</h1>
			<p class="dm-archive__description">
				<?php esc_html_e('Explora recursos, talleres y cursos organizados por tema.', 'daniela-child'); ?>
			</p>
		</div>
	</header>

	<div class="dm-archive__content dm-archive__content--temas">
		<?php echo dm_temas_render_all(); // phpcs:ignore WordPress.Security.EscapeOutput 
		?>
	</div>

</main>

<?php
// Enqueue WooCommerce add-to-cart JS so AJAX buttons work.
wp_enqueue_script('woocommerce');
wp_enqueue_script('wc-add-to-cart');
wp_enqueue_script('wc-cart-fragments');
wp_enqueue_script('dm-cart-drawer');

get_footer();
