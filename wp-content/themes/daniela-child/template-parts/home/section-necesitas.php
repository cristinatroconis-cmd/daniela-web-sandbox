<?php
/**
 * Home section — ¿Qué necesitas?
 *
 * Layout 2 columnas: izquierda (título + lead + imagen opcional), derecha (carousel).
 * El contenido es editable desde WP Admin → Personalizar → "Home: ¿Qué necesitas?".
 * Carousel JS ligero en assets/js/home-necesitas-carousel.js (sin Bootstrap).
 * Autoplay se activa al hacer hover en la sección completa.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* --- Customizer values ------------------------------------------------- */
$kicker    = get_theme_mod( 'dm_necesitas_kicker', '¿Dónde estás parada hoy?' );
$title     = get_theme_mod( 'dm_necesitas_title',  '¿Qué necesitas?' );
$lead      = get_theme_mod( 'dm_necesitas_lead',   'Elige el camino que mejor encaja con lo que estás viviendo ahora mismo.' );
$image_url = get_theme_mod( 'dm_necesitas_image',  '' );
$autoplay  = absint( get_theme_mod( 'dm_necesitas_autoplay', 4000 ) );

/* --- Slides ------------------------------------------------------------ */
$slides = function_exists( 'dm_necesitas_get_slides' )
	? dm_necesitas_get_slides()
	: json_decode( wp_json_encode( [
		[
			'kicker' => 'Recursos descargables',
			'title'  => 'Quiero herramientas prácticas',
			'text'   => 'PDFs, guías y registros para trabajar hoy mismo, a tu ritmo.',
			'url'    => '/recursos/',
			'bg'     => '#c9b8a8',
		],
		[
			'kicker' => 'Cursos y talleres',
			'title'  => 'Quiero aprender de forma guiada',
			'text'   => 'Formación online a tu ritmo o en vivo, en comunidad.',
			'url'    => '/escuela/',
			'bg'     => '#a8b8c9',
		],
		[
			'kicker' => 'Servicios',
			'title'  => 'Quiero acompañamiento profesional',
			'text'   => 'Sesiones, programas y membresías con apoyo personalizado.',
			'url'    => '/servicios/',
			'bg'     => '#b8c9a8',
		],
		[
			'kicker' => 'Explorar por tema',
			'title'  => 'No sé bien qué necesito',
			'text'   => 'Cuéntame dónde estás parada y encuentra lo que mejor encaja.',
			'url'    => '/temas/',
			'bg'     => '#c9a8b8',
		],
	] ), true );
?>
<section
	class="dm-necesitas"
	aria-labelledby="dm-necesitas-title"
>
	<div class="dm-necesitas__inner">

		<!-- ── Columna izquierda ───────────────────────────────────────── -->
		<div class="dm-necesitas__left">

			<?php if ( $kicker ) : ?>
				<p class="dm-necesitas__kicker"><?php echo esc_html( $kicker ); ?></p>
			<?php endif; ?>

			<h2 id="dm-necesitas-title" class="dm-necesitas__title">
				<?php echo esc_html( $title ); ?>
			</h2>

			<?php if ( $lead ) : ?>
				<p class="dm-necesitas__lead"><?php echo esc_html( $lead ); ?></p>
			<?php endif; ?>

			<?php if ( $image_url ) : ?>
				<img
					class="dm-necesitas__image"
					src="<?php echo esc_url( $image_url ); ?>"
					alt=""
					loading="lazy"
				>
			<?php endif; ?>

		</div><!-- /.dm-necesitas__left -->

		<!-- ── Columna derecha — Carousel ─────────────────────────────── -->
		<div class="dm-necesitas__right">

			<div
				class="dm-carousel"
				role="region"
				aria-label="<?php esc_attr_e( 'Opciones disponibles', 'daniela-child' ); ?>"
				data-autoplay="<?php echo esc_attr( $autoplay ); ?>"
			>
				<div class="dm-carousel__track">

					<?php foreach ( $slides as $i => $slide ) :
						$slide_url    = ! empty( $slide['url'] ) ? esc_url( home_url( $slide['url'] ) ) : '#';
						$slide_bg     = ! empty( $slide['bg']  ) ? esc_attr( $slide['bg'] ) : '#f4f0eb';
						$slide_kicker = ! empty( $slide['kicker'] ) ? esc_html( $slide['kicker'] ) : '';
						$slide_title  = ! empty( $slide['title']  ) ? esc_html( $slide['title']  ) : '';
						$slide_text   = ! empty( $slide['text']   ) ? esc_html( $slide['text']   ) : '';
					?>
					<a
						class="dm-carousel__slide"
						href="<?php echo $slide_url; ?>"
						style="--dm-slide-bg:<?php echo $slide_bg; ?>;"
						aria-label="<?php echo $slide_title; ?>"
						tabindex="<?php echo $i === 0 ? '0' : '-1'; ?>"
					>
						<div class="dm-carousel__slide-body">
							<?php if ( $slide_kicker ) : ?>
								<span class="dm-carousel__slide-kicker"><?php echo $slide_kicker; ?></span>
							<?php endif; ?>

							<?php if ( $slide_title ) : ?>
								<p class="dm-carousel__slide-title"><?php echo $slide_title; ?></p>
							<?php endif; ?>

							<?php if ( $slide_text ) : ?>
								<p class="dm-carousel__slide-text"><?php echo $slide_text; ?></p>
							<?php endif; ?>

							<span class="dm-carousel__slide-cta" aria-hidden="true">
								<?php esc_html_e( 'Ver más', 'daniela-child' ); ?> →
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
						aria-label="<?php esc_attr_e( 'Diapositiva anterior', 'daniela-child' ); ?>"
					>
						<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<button
						class="dm-carousel__btn dm-carousel__btn--next"
						type="button"
						aria-label="<?php esc_attr_e( 'Siguiente diapositiva', 'daniela-child' ); ?>"
					>
						<svg viewBox="0 0 24 24" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
				</div>

				<!-- Dots -->
				<div class="dm-carousel__dots" role="tablist" aria-label="<?php esc_attr_e( 'Diapositivas', 'daniela-child' ); ?>"></div>

			</div><!-- /.dm-carousel -->

		</div><!-- /.dm-necesitas__right -->

	</div><!-- /.dm-necesitas__inner -->
</section><!-- /.dm-necesitas -->