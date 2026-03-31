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
					// CTA: para recursos gratuitos se prefiere el formulario de entrega
					// por email con link tokenizado. Para recursos de pago, CTA WooCommerce.
					$linked_product = dm_cpt_get_linked_product();
					if ( $linked_product ) {
						$is_free_product = ( (float) $linked_product->get_price() <= 0.0 ); // phpcs:ignore WordPress.PHP.StrictComparisons
						if ( $is_free_product ) {
							// Entrega por email con token (preferida).
							$freebie_html = do_shortcode(
								'[dm_freebie_form product_id="' . esc_attr( $linked_product->get_id() ) . '"]'
							);
							echo '<div class="dm-single__actions"><aside class="dm-single__cta dm-single__cta--freebie">' . $freebie_html . '</aside></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
						} else {
							$cta = dm_cpt_render_cta();
							if ( $cta ) {
								echo '<div class="dm-single__actions"><aside class="dm-single__cta">' . $cta . '</aside></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
							}
						}
					}
					?>

					<footer class="dm-single__footer">
						<a href="<?php echo esc_url( get_post_type_archive_link( 'dm_recurso' ) ); ?>" class="dm-btn dm-btn--ghost">
							&larr; <?php esc_html_e( 'Volver a Recursos', 'daniela-child' ); ?>
						</a>
					</footer>

				</div><!-- .dm-single__body -->

			</div><!-- .dm-single__layout -->

		</article>

	</main>

	<?php
endwhile;

get_footer();
