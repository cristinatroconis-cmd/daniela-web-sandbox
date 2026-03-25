<?php
/**
 * Archive template — dm_servicio (Servicios CPT).
 *
 * URL: /servicios/
 * Chips: dm_tipo_servicio (sesiones | membresias).
 *
 * @package Daniela_Child
 */

get_header();
?>

<main id="main" class="site-main dm-archive dm-archive--servicio">

	<header class="dm-archive__header">
		<div class="dm-archive__header-inner">
			<h1 class="dm-archive__title">
				<?php esc_html_e( 'Servicios', 'daniela-child' ); ?>
			</h1>
			<p class="dm-archive__description">
				<?php esc_html_e( 'Sesiones individuales y membresías para acompañamiento continuo.', 'daniela-child' ); ?>
			</p>

			<?php
			$archive_url = get_post_type_archive_link( 'dm_servicio' );
			echo dm_cpt_render_taxonomy_chips( 'dm_tipo_servicio', 'tipo', $archive_url ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>
	</header>

	<div class="dm-archive__content">
		<?php
		$args  = dm_cpt_archive_query_args( 'dm_servicio', 'dm_tipo_servicio', 'tipo' );
		$query = new WP_Query( $args );
		echo dm_cpt_render_grid( $query ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</div>

</main>

<?php
get_footer();
