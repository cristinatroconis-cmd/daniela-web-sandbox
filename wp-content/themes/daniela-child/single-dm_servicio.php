<?php
/**
 * Single template — dm_servicio (Servicios CPT).
 *
 * Muestra: imagen destacada, tipo de servicio, título, contenido y CTA WooCommerce.
 *
 * @package Daniela_Child
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<main id="main" class="site-main dm-single dm-single--servicio">

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'dm-single__article' ); ?>>

			<div class="dm-single__layout<?php echo has_post_thumbnail() ? '' : ' dm-single__layout--no-image'; ?>">

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="dm-single__media">
						<div class="dm-single__thumbnail">
							<?php the_post_thumbnail( 'large' ); ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="dm-single__body">

					<header class="dm-single__header">
						<?php
						// Tipo (sesiones / membresias).
						$tipos = get_the_terms( get_the_ID(), 'dm_tipo_servicio' );
						if ( $tipos && ! is_wp_error( $tipos ) ) :
							?>
							<div class="dm-single__type">
								<?php foreach ( $tipos as $tipo ) : ?>
									<span class="dm-chip dm-chip--sm"><?php echo esc_html( $tipo->name ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<h1 class="dm-single__title"><?php the_title(); ?></h1>
					</header>

					<div class="dm-single__content entry-content">
						<?php the_content(); ?>
					</div>

					<?php
					$cta = dm_cpt_render_cta();
					if ( $cta ) :
					?>
					<div class="dm-single__actions">
						<aside class="dm-single__cta">
							<?php echo $cta; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</aside>
					</div>
					<?php endif; ?>

					<footer class="dm-single__footer">
						<a href="<?php echo esc_url( get_post_type_archive_link( 'dm_servicio' ) ); ?>" class="dm-btn dm-btn--ghost">
							&larr; <?php esc_html_e( 'Volver a Servicios', 'daniela-child' ); ?>
						</a>
					</footer>

				</div><!-- .dm-single__body -->

			</div><!-- .dm-single__layout -->

		</article>

	</main>

	<?php
endwhile;

get_footer();
