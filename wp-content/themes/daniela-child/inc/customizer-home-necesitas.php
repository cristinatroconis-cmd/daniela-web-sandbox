<?php
/**
 * Customizer — Sección "¿Qué necesitas?" del Home.
 *
 * Configura desde WP Admin → Personalizar → "Home: ¿Qué necesitas?"
 * sin depender de ACF ni de plugins de pago.
 * Compatible con Kirki si está activo (progressive enhancement),
 * pero usa la API nativa de WordPress Customizer como base.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   Slides — defaults
   ========================================================================= */
define( 'DM_NECESITAS_SLIDES_DEFAULT', wp_json_encode( [
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
] ) );

/* =========================================================================
   Registro en el Customizer
   ========================================================================= */
add_action( 'customize_register', 'dm_necesitas_customizer_register' );

/**
 * @param WP_Customize_Manager $wp_customize
 */
function dm_necesitas_customizer_register( $wp_customize ) {

	/* -----------------------------------------------------------------
	   Panel / Sección
	   ----------------------------------------------------------------- */
	$wp_customize->add_section( 'dm_necesitas', [
		'title'    => __( 'Home: ¿Qué necesitas?', 'daniela-child' ),
		'priority' => 130,
	] );

	/* -----------------------------------------------------------------
	   Helper para registrar un setting + control en un solo paso.
	   ----------------------------------------------------------------- */
	$add = function ( $id, $args, $control_args ) use ( $wp_customize ) {

		$setting_defaults = [
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		];

		$wp_customize->add_setting(
			'dm_necesitas_' . $id,
			array_merge( $setting_defaults, $args )
		);

		$control_defaults = [
			'section' => 'dm_necesitas',
			'label'   => '',
		];

		$control_args = array_merge( $control_defaults, $control_args, [
			'settings' => 'dm_necesitas_' . $id,
		] );

		if ( isset( $control_args['type'] ) && 'image' === $control_args['type'] ) {
			$wp_customize->add_control(
				new WP_Customize_Image_Control(
					$wp_customize,
					'dm_necesitas_' . $id,
					$control_args
				)
			);
		} elseif ( isset( $control_args['type'] ) && 'color' === $control_args['type'] ) {
			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					'dm_necesitas_' . $id,
					$control_args
				)
			);
		} else {
			$wp_customize->add_control( 'dm_necesitas_' . $id, $control_args );
		}
	};

	/* -----------------------------------------------------------------
	   Contenido
	   ----------------------------------------------------------------- */
	$add(
		'kicker',
		[ 'default' => '¿Dónde estás parada hoy?' ],
		[
			'label'    => __( 'Kicker (texto pequeño sobre el título)', 'daniela-child' ),
			'type'     => 'text',
			'priority' => 10,
		]
	);

	$add(
		'title',
		[ 'default' => '¿Qué necesitas?' ],
		[
			'label'    => __( 'Título principal', 'daniela-child' ),
			'type'     => 'text',
			'priority' => 20,
		]
	);

	$add(
		'lead',
		[
			'default'           => 'Elige el camino que mejor encaja con lo que estás viviendo ahora mismo.',
			'sanitize_callback' => 'sanitize_textarea_field',
		],
		[
			'label'    => __( 'Texto de apoyo (lead)', 'daniela-child' ),
			'type'     => 'textarea',
			'priority' => 30,
		]
	);

	$add(
		'image',
		[
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		],
		[
			'label'    => __( 'Imagen opcional (columna izquierda)', 'daniela-child' ),
			'type'     => 'image',
			'priority' => 40,
		]
	);

	/* -----------------------------------------------------------------
	   Estética / Layout
	   ----------------------------------------------------------------- */
	$add(
		'pad_y',
		[ 'default' => '72' ],
		[
			'label'       => __( 'Padding vertical (px)', 'daniela-child' ),
			'type'        => 'number',
			'priority'    => 50,
			'input_attrs' => [ 'min' => 0, 'max' => 200, 'step' => 4 ],
		]
	);

	$add(
		'gap',
		[ 'default' => '48' ],
		[
			'label'       => __( 'Gap entre columnas (px)', 'daniela-child' ),
			'type'        => 'number',
			'priority'    => 60,
			'input_attrs' => [ 'min' => 0, 'max' => 120, 'step' => 4 ],
		]
	);

	$add(
		'min_height',
		[ 'default' => '420' ],
		[
			'label'       => __( 'Altura mínima del carousel (px)', 'daniela-child' ),
			'type'        => 'number',
			'priority'    => 70,
			'input_attrs' => [ 'min' => 200, 'max' => 800, 'step' => 10 ],
		]
	);

	$add(
		'radius',
		[ 'default' => '16' ],
		[
			'label'       => __( 'Border radius de las tarjetas (px)', 'daniela-child' ),
			'type'        => 'number',
			'priority'    => 80,
			'input_attrs' => [ 'min' => 0, 'max' => 48, 'step' => 2 ],
		]
	);

	/* -----------------------------------------------------------------
	   Carousel
	   ----------------------------------------------------------------- */
	$add(
		'autoplay',
		[ 'default' => '4000' ],
		[
			'label'       => __( 'Autoplay (ms; 0 = desactivado)', 'daniela-child' ),
			'type'        => 'number',
			'priority'    => 90,
			'input_attrs' => [ 'min' => 0, 'max' => 15000, 'step' => 500 ],
		]
	);

	/* -----------------------------------------------------------------
	   Slides (JSON textarea — máxima flexibilidad sin UI compleja)
	   ----------------------------------------------------------------- */
	$add(
		'slides_json',
		[
			'default'           => DM_NECESITAS_SLIDES_DEFAULT,
			'sanitize_callback' => 'dm_necesitas_sanitize_slides_json',
		],
		[
			'label'       => __( 'Slides (JSON)', 'daniela-child' ),
			'description' => __(
				'Array JSON con objetos: kicker, title, text, url, bg (color hex). Mantén los 4 slides o añade más.',
				'daniela-child'
			),
			'type'        => 'textarea',
			'priority'    => 100,
		]
	);
}

/* =========================================================================
   Sanitización del JSON de slides
   ========================================================================= */
function dm_necesitas_sanitize_slides_json( $value ) {
	$decoded = json_decode( wp_unslash( $value ), true );

	if ( ! is_array( $decoded ) ) {
		return DM_NECESITAS_SLIDES_DEFAULT;
	}

	$clean = [];
	foreach ( $decoded as $slide ) {
		if ( ! is_array( $slide ) ) {
			continue;
		}
		$clean[] = [
			'kicker' => sanitize_text_field( $slide['kicker'] ?? '' ),
			'title'  => sanitize_text_field( $slide['title']  ?? '' ),
			'text'   => sanitize_textarea_field( $slide['text'] ?? '' ),
			'url'    => esc_url_raw( $slide['url'] ?? '' ),
			'bg'     => sanitize_hex_color( $slide['bg'] ?? '' ) ?: '#f4f0eb',
		];
	}

	return $clean ? wp_json_encode( $clean ) : DM_NECESITAS_SLIDES_DEFAULT;
}

/* =========================================================================
   Helper: obtener slides parseados
   ========================================================================= */
function dm_necesitas_get_slides() {
	$json = get_theme_mod( 'dm_necesitas_slides_json', DM_NECESITAS_SLIDES_DEFAULT );
	$slides = json_decode( wp_unslash( $json ), true );

	if ( ! is_array( $slides ) || empty( $slides ) ) {
		$slides = json_decode( DM_NECESITAS_SLIDES_DEFAULT, true );
	}

	return (array) $slides;
}

/* =========================================================================
   Nota: la inyección de variables CSS inline (dm-necesitas-vars) se eliminó.
   Los valores visuales se editan directamente en:
   assets/css/home-necesitas.css  ← única fuente de verdad para esta sección.
   ========================================================================= */
