<?php
/**
 * Archive template — dm_recurso (Recursos CPT).
 *
 * URL: /recursos/
 * Chips: dm_tipo_recurso (gratis | pagos) y dm_tema (temas transversales).
 *
 * @package Daniela_Child
 */

get_header();
?>

<main id="main" class="site-main dm-archive dm-archive--recurso">

	<header class="dm-archive__header">
		<div class="dm-archive__header-inner">
			<h1 class="dm-archive__title">
				<?php esc_html_e( 'Recursos', 'daniela-child' ); ?>
			</h1>
			<p class="dm-archive__description">
				<?php esc_html_e( 'Materiales psicológicos para tu bienestar: guías, ejercicios y recursos descargables.', 'daniela-child' ); ?>
			</p>

			<?php
			$archive_url = get_post_type_archive_link( 'dm_recurso' );
			// Chips de tipo (gratis / pagos)
			echo dm_cpt_render_taxonomy_chips( 'dm_tipo_recurso', 'tipo', $archive_url ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>
	</header>

	<div class="dm-archive__content">
		<?php
		$args  = dm_cpt_archive_query_args( 'dm_recurso', 'dm_tipo_recurso', 'tipo' );
		$query = new WP_Query( $args );
		echo dm_cpt_render_grid( $query ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</div>

</main>

<?php
get_footer();
