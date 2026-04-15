<?php

/**
 * Single template — dm_recurso (Recursos CPT).
 *
 * Muestra: imagen destacada, título, contenido completo y CTA WooCommerce.
 *
 * @package Daniela_Child
 */

get_header();

while (have_posts()) :
	the_post();
?>

	<main id="main" class="site-main dm-single dm-single--recurso">

		<article id="post-<?php the_ID(); ?>" <?php post_class('dm-single__article'); ?>>
			<?php
			$post_id               = get_the_ID();
			$hero_image_url        = trim((string) get_post_meta($post_id, '_dm_single_hero_image_url', true));
			$has_hero_image        = ($hero_image_url !== '') || has_post_thumbnail();
			$hero_kicker           = trim((string) get_post_meta($post_id, '_dm_editorial_hero_kicker', true));
			$hero_intro            = trim((string) get_post_meta($post_id, '_dm_editorial_hero_intro', true));
			$hero_button_label     = trim((string) get_post_meta($post_id, '_dm_editorial_hero_button_label', true));
			$has_editorial_content = function_exists('dm_cpt_has_editorial_sections') ? dm_cpt_has_editorial_sections($post_id) : false;
			$editorial_sections    = function_exists('dm_cpt_render_editorial_sections') ? dm_cpt_render_editorial_sections($post_id) : '';
			$hero_cta              = $hero_button_label !== '' ? dm_cpt_render_cta($post_id, ['label' => $hero_button_label]) : '';
			$fallback_cta          = ! $has_editorial_content && ! $hero_cta ? dm_cpt_render_cta($post_id) : '';
			?>

			<div class="dm-single__layout<?php echo $has_hero_image ? '' : ' dm-single__layout--no-image'; ?>">

				<?php if ($has_hero_image) : ?>
					<div class="dm-single__media">
						<div class="dm-single__thumbnail">
							<?php if ($hero_image_url !== '') : ?>
								<img src="<?php echo esc_url($hero_image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy" />
							<?php else : ?>
								<?php the_post_thumbnail('large'); ?>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="dm-single__body">

					<header class="dm-single__header">
						<?php if ($hero_kicker) : ?>
							<p class="dm-single__kicker"><?php echo esc_html($hero_kicker); ?></p>
						<?php endif; ?>

						<h1 class="dm-single__title"><?php the_title(); ?></h1>

						<?php if ($hero_intro) : ?>
							<p class="dm-single__intro"><?php echo nl2br(esc_html($hero_intro)); ?></p>
						<?php endif; ?>

						<?php
						// Temas transversales (dm_tema).
						$temas = get_the_terms(get_the_ID(), 'dm_tema');
						if ($temas && ! is_wp_error($temas)) :
						?>
							<div class="dm-single__terms">
								<?php foreach ($temas as $tema) : ?>
									<span class="dm-chip dm-chip--sm"><?php echo esc_html($tema->name); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</header>

					<div class="dm-single__content entry-content">
						<?php if (trim((string) get_the_content()) !== '') : ?>
							<?php the_content(); ?>
						<?php endif; ?>
						<?php if ($editorial_sections) : ?>
							<?php echo $editorial_sections; // phpcs:ignore WordPress.Security.EscapeOutput 
							?>
						<?php endif; ?>
					</div>

					<?php if ($hero_cta || $fallback_cta) : ?>
						<div class="dm-single__actions">
							<aside class="dm-single__cta">
								<?php echo $hero_cta ? $hero_cta : $fallback_cta; // phpcs:ignore WordPress.Security.EscapeOutput 
								?>
							</aside>
						</div>
					<?php endif; ?>

					<footer class="dm-single__footer">
						<a href="<?php echo esc_url(get_post_type_archive_link('dm_recurso')); ?>" class="dm-btn dm-btn--ghost">
							&larr; <?php esc_html_e('Volver a Recursos', 'daniela-child'); ?>
						</a>
					</footer>

				</div><!-- .dm-single__body -->

			</div><!-- .dm-single__layout -->

		</article>

	</main>

<?php
endwhile;

get_footer();
