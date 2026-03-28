<?php

/**
 * Archive template — dm_servicio (Servicios CPT).
 *
 * URL: /servicios/
 * Chips: categorías WooCommerce (servicios/*): sesiones | paquetes | membresias | supervisiones — Ruta A (estricto).
 *
 * @package Daniela_Child
 */

get_header();
?>

<main id="main" class="site-main dm-archive dm-archive--servicios">

	<header class="dm-archive__header">
		<div class="dm-archive__header-inner">
			<h1 class="dm-archive__title">
				<?php esc_html_e('Servicios', 'daniela-child'); ?>
			</h1>
			<p class="dm-archive__description">
				<?php esc_html_e('Sesiones, paquetes, membresías y supervisiones.', 'daniela-child'); ?>
			</p>

			<?php
			$archive_url = get_post_type_archive_link('dm_servicio');
			echo dm_servicios_render_woo_chips('tipo', $archive_url); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>
	</header>

	<div class="dm-archive__content">
		<?php
		$args  = dm_servicios_query_args_by_woo_cat_strict('tipo');
		$query = new WP_Query($args);

		echo dm_cpt_render_grid($query); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</div>

</main>

<?php
get_footer();
