<?php

/**
 * Home section — ¿Qué necesitas?
 *
 * Layout 2 columnas: izquierda (título + lead + imagen opcional), derecha (carousel).
 * Carousel JS ligero en assets/js/home-necesitas-carousel.js (sin Bootstrap).
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
	exit;
}

/* --- Contenido fijo (ya no depende del Customizer) ---------------------- */
$kicker    = '';
$title     = '¿Dónde estás parada hoy?';
$lead      = 'Elige el camino que mejor encaja con lo que estás viviendo ahora mismo.';
$image_url = '';      // pon aquí una URL si quieres fijar imagen desde código
$autoplay  = 4000;    // ms

/* --- Slides (hardcode) -------------------------------------------------- */
$slides = [
	[
		'kicker' => '',
		'title'  => 'Quiero herramientas prácticas',
		'text'   => 'PDFs, guías y registros para trabajar hoy mismo, a tu ritmo.',
		'url'    => '/recursos/',
		'bg'     => '#ead2ac',
		'image'  => 'http://dani-backup.local/wp-content/uploads/2023/08/dani_consultoria6.png', // pon aquí una URL si quieres fijar imagen desde código
		'image_alt' => 'Icono de recursos descargables',
	],
	[
		'kicker' => '',
		'title'  => 'Quiero aprender de forma guiada',
		'text'   => 'Formación online a tu ritmo o en vivo, en comunidad.',
		'url'    => '/escuela/',
		'bg'     => '#ad8fb7',
		'image'  => 'http://dani-backup.local/wp-content/uploads/2023/08/dani_consultoria5.png', // pon aquí una URL si quieres fijar imagen desde código
		'image_alt' => 'Icono de formación online',
	],
	[
		'kicker' => '',
		'title'  => 'Quiero acompañamiento profesional',
		'text'   => 'Te ofrezco mis servicios de terapia.',
		'url'    => '/servicios/',
		'bg'     => '#eaefbd',
		'image'  => 'http://dani-backup.local/wp-content/uploads/2023/08/dani_consultoria4.png', // pon aquí una URL si quieres fijar imagen desde código
		'image_alt' => 'Icono de acompañamiento profesional',
	],
	[
		'kicker' => '',
		'title'  => 'No sé bien qué necesito',
		'text'   => 'Cuéntame que estás sintiendo y encuentra lo que mejor encaja.',
		'url'    => '/temas/',
		'bg'     => '#c97f72',
		'image'  => 'http://dani-backup.local/wp-content/uploads/2023/08/dani_consultoria3.png', // pon aquí una URL si quieres fijar imagen desde código
		'image_alt' => 'Icono de orientación',
	],
];
?>
<section
	class="dm-necesitas"
	aria-labelledby="dm-necesitas-title">
	<div class="dm-necesitas__inner">

		<!-- ── Columna izquierda ───────────────────────────────────────── -->
		<div class="dm-necesitas__left">

			<?php if ($kicker) : ?>
				<p class="dm-necesitas__kicker"><?php echo esc_html($kicker); ?></p>
			<?php endif; ?>

			<h2 id="dm-necesitas-title" class="dm-necesitas__title">
				<?php echo esc_html($title); ?>
			</h2>

			<?php if ($lead) : ?>
				<p class="dm-necesitas__lead"><?php echo esc_html($lead); ?></p>
			<?php endif; ?>

			<?php if ($image_url) : ?>
				<img
					class="dm-necesitas__image"
					src="<?php echo esc_url($image_url); ?>"
					alt=""
					loading="lazy">
			<?php endif; ?>

		</div><!-- /.dm-necesitas__left -->

		<!-- ── Columna derecha — Carousel ─────────────────────────────── -->
		<div class="dm-necesitas__right">

			<div
				class="dm-carousel"
				role="region"
				aria-label="<?php esc_attr_e('Opciones disponibles', 'daniela-child'); ?>"
				data-autoplay="<?php echo esc_attr($autoplay); ?>">
				<div class="dm-carousel__track">

					<?php foreach ($slides as $i => $slide) :
						$slide_url    = ! empty($slide['url']) ? esc_url(home_url($slide['url'])) : '#';
						$slide_bg     = ! empty($slide['bg']) ? esc_attr($slide['bg']) : '#f4f0eb';
						$slide_image  = ! empty($slide['image']) ? esc_url($slide['image']) : '';
						$slide_image_alt = isset($slide['image_alt']) ? esc_attr($slide['image_alt']) : '';
						$slide_kicker = ! empty($slide['kicker']) ? esc_html($slide['kicker']) : '';
						$slide_title  = ! empty($slide['title']) ? esc_html($slide['title']) : '';
						$slide_text   = ! empty($slide['text']) ? esc_html($slide['text']) : '';
					?>
						<a
							class="dm-carousel__slide"
							href="<?php echo $slide_url; ?>"
							style="--dm-slide-bg:<?php echo $slide_bg; ?>;"
							aria-label="<?php echo $slide_title; ?>"
							tabindex="<?php echo $i === 0 ? '0' : '-1'; ?>">

							<?php if ($slide_image) : ?>
								<div class="dm-carousel__slide-hero">
									<img
										class="dm-carousel__slide-hero-img"
										src="<?php echo $slide_image; ?>"
										alt="<?php echo $slide_image_alt; ?>"
										loading="lazy"
										decoding="async" />
								</div>
							<?php endif; ?>

							<div class="dm-carousel__slide-body">
								<?php if ($slide_kicker) : ?>
									<span class="dm-carousel__slide-kicker"><?php echo $slide_kicker; ?></span>
								<?php endif; ?>

								<?php if ($slide_title) : ?>
									<p class="dm-carousel__slide-title"><?php echo $slide_title; ?></p>
								<?php endif; ?>

								<?php if ($slide_text) : ?>
									<p class="dm-carousel__slide-text"><?php echo $slide_text; ?></p>
								<?php endif; ?>

								<span class="dm-carousel__slide-cta dm-btn dm-btn--ghost" aria-hidden="true">
									<?php esc_html_e('Ver detalles', 'daniela-child'); ?> →
								</span>
							</div>
						</a>
					<?php endforeach; ?>

				</div><!-- /.dm-carousel__track -->

				<!-- Controles prev / next -->
				<div class="dm-carousel__controls" aria-hidden="true">
					<button
						class="dm-carousel__btn dm-carousel__btn--prev"
						type="button"
						aria-label="<?php esc_attr_e('Diapositiva anterior', 'daniela-child'); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true">
							<polyline points="15 18 9 12 15 6"></polyline>
						</svg>
					</button>
					<button
						class="dm-carousel__btn dm-carousel__btn--next"
						type="button"
						aria-label="<?php esc_attr_e('Siguiente diapositiva', 'daniela-child'); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true">
							<polyline points="9 18 15 12 9 6"></polyline>
						</svg>
					</button>
				</div>

				<!-- Dots -->
				<div class="dm-carousel__dots" role="tablist" aria-label="<?php esc_attr_e('Diapositivas', 'daniela-child'); ?>"></div>

			</div><!-- /.dm-carousel -->

		</div><!-- /.dm-necesitas__right -->

	</div><!-- /.dm-necesitas__inner -->
</section><!-- /.dm-necesitas -->