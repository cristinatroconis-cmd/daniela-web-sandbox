<?php
/**
 * Single template — dm_recurso (Recursos CPT).
 *
 * Muestra: imagen destacada, título, contenido completo y CTA WooCommerce.
 *
 * @package Daniela_Child
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<main id="main" class="site-main dm-single dm-single--recurso">

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'dm-single__article' ); ?>>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="dm-single__thumbnail">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>

			<header class="dm-single__header">
				<h1 class="dm-single__title"><?php the_title(); ?></h1>

				<?php
				// Temas transversales (dm_tema).
				$temas = get_the_terms( get_the_ID(), 'dm_tema' );
				if ( $temas && ! is_wp_error( $temas ) ) :
					?>
					<div class="dm-single__terms">
						<?php foreach ( $temas as $tema ) : ?>
							<span class="dm-chip dm-chip--sm"><?php echo esc_html( $tema->name ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</header>

			<div class="dm-single__content entry-content">
				<?php the_content(); ?>
			</div>

			<?php
			// CTA WooCommerce (falla silenciosamente si WC no está activo).
			$cta = dm_cpt_render_cta();
			if ( $cta ) :
				?>
				<aside class="dm-single__cta">
					<?php echo $cta; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</aside>
			<?php endif; ?>

			<footer class="dm-single__footer">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'dm_recurso' ) ); ?>" class="dm-btn dm-btn--ghost">
					&larr; <?php esc_html_e( 'Volver a Recursos', 'daniela-child' ); ?>
				</a>
			</footer>

		</article>

	</main>

	<?php
endwhile;

get_footer();
