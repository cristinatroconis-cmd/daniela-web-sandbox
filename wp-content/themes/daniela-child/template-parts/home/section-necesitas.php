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

/* --- Contenido editable desde admin con fallback seguro ----------------- */
$left_defaults = [
	'kicker' => '',
	'title'  => '¿Dónde estás parada hoy?',
	'lead'   => 'Elige el camino que mejor encaja con lo que estás viviendo ahora mismo.',
	'note'   => '',
];

$left_content = function_exists('dm_home_necesitas_get_front_content')
	? dm_home_necesitas_get_front_content()
	: $left_defaults;

$kicker      = (string) ($left_content['kicker'] ?? '');
$title       = (string) ($left_content['title'] ?? $left_defaults['title']);
$title_image = (string) ($left_content['title_image'] ?? '');
$lead        = (string) ($left_content['lead'] ?? $left_defaults['lead']);
$note        = (string) ($left_content['note'] ?? '');
$image_url   = (string) ($left_content['image'] ?? '');
$autoplay    = 4000;
$uploads_base_url = untrailingslashit(home_url('/wp-content/uploads/2023/08'));

/* --- Slides (editable por página destino + fallback hardcode) ---------- */
$slides = [
	function_exists('dm_home_necesitas_get_card_content')
		? dm_home_necesitas_get_card_content('recursos', [
			'kicker' => '',
			'title'  => 'Quiero herramientas prácticas',
			'text'   => 'PDFs, guías y registros para trabajar hoy mismo, a tu ritmo.',
			'url'    => '/recursos/',
			'bg'     => '#ead2ac',
			'image'  => $uploads_base_url . '/dani_consultoria6.png',
			'image_alt' => 'Icono de recursos descargables',
		])
		: [
			'kicker' => '',
			'title'  => 'Quiero herramientas prácticas',
			'text'   => 'PDFs, guías y registros para trabajar hoy mismo, a tu ritmo.',
			'url'    => '/recursos/',
			'bg'     => '#ead2ac',
			'image'  => $uploads_base_url . '/dani_consultoria6.png',
			'image_alt' => 'Icono de recursos descargables',
		],
	function_exists('dm_home_necesitas_get_card_content')
		? dm_home_necesitas_get_card_content('escuela', [
			'kicker' => '',
			'title'  => 'Quiero aprender de forma guiada',
			'text'   => 'Formación online a tu ritmo o en vivo, en comunidad.',
			'url'    => '/escuela/',
			'bg'     => '#ad8fb7',
			'image'  => $uploads_base_url . '/dani_consultoria5.png',
			'image_alt' => 'Icono de formación online',
		])
		: [
			'kicker' => '',
			'title'  => 'Quiero aprender de forma guiada',
			'text'   => 'Formación online a tu ritmo o en vivo, en comunidad.',
			'url'    => '/escuela/',
			'bg'     => '#ad8fb7',
			'image'  => $uploads_base_url . '/dani_consultoria5.png',
			'image_alt' => 'Icono de formación online',
		],
	function_exists('dm_home_necesitas_get_card_content')
		? dm_home_necesitas_get_card_content('servicios', [
			'kicker' => '',
			'title'  => 'Quiero acompañamiento profesional',
			'text'   => 'Te ofrezco mis servicios de terapia.',
			'url'    => '/servicios/',
			'bg'     => '#eaefbd',
			'image'  => $uploads_base_url . '/dani_consultoria4.png',
			'image_alt' => 'Icono de acompañamiento profesional',
		])
		: [
			'kicker' => '',
			'title'  => 'Quiero acompañamiento profesional',
			'text'   => 'Te ofrezco mis servicios de terapia.',
			'url'    => '/servicios/',
			'bg'     => '#eaefbd',
			'image'  => $uploads_base_url . '/dani_consultoria4.png',
			'image_alt' => 'Icono de acompañamiento profesional',
		],
	function_exists('dm_home_necesitas_get_card_content')
		? dm_home_necesitas_get_card_content('temas', [
			'kicker' => '',
			'title'  => 'No sé bien qué necesito',
			'text'   => 'Cuéntame qué estás sintiendo y encuentra lo que mejor encaja.',
			'url'    => '/temas/',
			'bg'     => '#c97f72',
			'image'  => $uploads_base_url . '/dani_consultoria3.png',
			'image_alt' => 'Icono de orientación',
		])
		: [
			'kicker' => '',
			'title'  => 'No sé bien qué necesito',
			'text'   => 'Cuéntame qué estás sintiendo y encuentra lo que mejor encaja.',
			'url'    => '/temas/',
			'bg'     => '#c97f72',
			'image'  => $uploads_base_url . '/dani_consultoria3.png',
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

			<div class="dm-necesitas__copy">
				<?php if ($kicker) : ?>
					<p class="dm-necesitas__kicker"><?php echo esc_html($kicker); ?></p>
				<?php endif; ?>

				<?php if ($title_image !== '' && function_exists('dm_cpt_render_editorial_heading')) : ?>
					<div class="dm-necesitas__title-wrap dm-necesitas__title-wrap--media">
						<?php echo dm_cpt_render_editorial_heading($title, $title_image, 'section'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
						?>
						<h2 id="dm-necesitas-title" class="screen-reader-text"><?php echo esc_html($title); ?></h2>
					</div>
				<?php elseif ($title_image !== '') : ?>
					<div class="dm-necesitas__title-wrap dm-necesitas__title-wrap--media">
						<img class="dm-necesitas__title-image" src="<?php echo esc_url($title_image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
						<h2 id="dm-necesitas-title" class="screen-reader-text"><?php echo esc_html($title); ?></h2>
					</div>
				<?php else : ?>
					<h2 id="dm-necesitas-title" class="dm-necesitas__title">
						<?php echo esc_html($title); ?>
					</h2>
				<?php endif; ?>

				<?php if ($lead) : ?>
					<div class="dm-necesitas__lead"><?php echo wpautop(esc_html($lead)); ?></div>
				<?php endif; ?>

				<?php if ($note) : ?>
					<div class="dm-necesitas__note"><?php echo wpautop(esc_html($note)); ?></div>
				<?php endif; ?>
			</div>

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
						$raw_slide_url = isset($slide['url']) ? (string) $slide['url'] : '';
						$slide_url     = $raw_slide_url !== ''
							? esc_url(preg_match('#^https?://#', $raw_slide_url) ? $raw_slide_url : home_url($raw_slide_url))
							: '#';
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
									<h3 class="dm-carousel__slide-title"><?php echo $slide_title; ?></h3>
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