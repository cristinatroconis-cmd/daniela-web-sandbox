<?php
/**
 * Archive template — dm_escuela (Escuela CPT).
 *
 * URL: /escuela/
 * Chips: dm_tipo_escuela (cursos | talleres | programas) y dm_tema.
 *
 * @package Daniela_Child
 */

get_header();
?>

<main id="main" class="site-main dm-archive dm-archive--escuela">

	<header class="dm-archive__header">
		<div class="dm-archive__header-inner">
			<h1 class="dm-archive__title">
				<?php esc_html_e( 'Escuela', 'daniela-child' ); ?>
			</h1>
			<p class="dm-archive__description">
				<?php esc_html_e( 'Cursos, talleres y programas para tu crecimiento personal y profesional.', 'daniela-child' ); ?>
			</p>

			<?php
			$archive_url = get_post_type_archive_link( 'dm_escuela' );
			echo dm_cpt_render_taxonomy_chips( 'dm_tipo_escuela', 'tipo', $archive_url ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>
	</header>

	<div class="dm-archive__content">
		<?php
		$args  = dm_cpt_archive_query_args( 'dm_escuela', 'dm_tipo_escuela', 'tipo' );
		$query = new WP_Query( $args );
		echo dm_cpt_render_grid( $query ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</div>

</main>

<?php
get_footer();
