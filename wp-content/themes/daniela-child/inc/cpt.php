<?php
/**
 * Custom Post Types & Taxonomies — catálogo editorial.
 *
 * Registra los CPTs dm_recurso, dm_escuela, dm_servicio y sus taxonomías
 * de clasificación interna (chips/filtros en archives).
 *
 * WooCommerce sigue siendo el motor de compra; los CPTs son el motor editorial.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// CUSTOM POST TYPES
// =============================================================================

add_action( 'init', 'dm_register_cpts' );

function dm_register_cpts() {

	// ------------------------------------------------------------------
	// dm_recurso  →  /recursos/
	// ------------------------------------------------------------------
	register_post_type(
		'dm_recurso',
		[
			'labels'       => [
				'name'               => __( 'Recursos', 'daniela-child' ),
				'singular_name'      => __( 'Recurso', 'daniela-child' ),
				'add_new'            => __( 'Añadir recurso', 'daniela-child' ),
				'add_new_item'       => __( 'Añadir nuevo recurso', 'daniela-child' ),
				'edit_item'          => __( 'Editar recurso', 'daniela-child' ),
				'new_item'           => __( 'Nuevo recurso', 'daniela-child' ),
				'view_item'          => __( 'Ver recurso', 'daniela-child' ),
				'view_items'         => __( 'Ver recursos', 'daniela-child' ),
				'search_items'       => __( 'Buscar recursos', 'daniela-child' ),
				'not_found'          => __( 'No se encontraron recursos.', 'daniela-child' ),
				'not_found_in_trash' => __( 'No hay recursos en la papelera.', 'daniela-child' ),
				'all_items'          => __( 'Todos los recursos', 'daniela-child' ),
				'menu_name'          => __( 'Recursos CPT', 'daniela-child' ),
			],
			'public'        => true,
			'has_archive'   => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-media-document',
			'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
			'rewrite'       => [ 'slug' => 'recursos', 'with_front' => false ],
		]
	);

	// ------------------------------------------------------------------
	// dm_escuela  →  /escuela/
	// ------------------------------------------------------------------
	register_post_type(
		'dm_escuela',
		[
			'labels'       => [
				'name'               => __( 'Escuela', 'daniela-child' ),
				'singular_name'      => __( 'Ítem de Escuela', 'daniela-child' ),
				'add_new'            => __( 'Añadir ítem', 'daniela-child' ),
				'add_new_item'       => __( 'Añadir nuevo ítem de Escuela', 'daniela-child' ),
				'edit_item'          => __( 'Editar ítem de Escuela', 'daniela-child' ),
				'new_item'           => __( 'Nuevo ítem de Escuela', 'daniela-child' ),
				'view_item'          => __( 'Ver ítem de Escuela', 'daniela-child' ),
				'view_items'         => __( 'Ver Escuela', 'daniela-child' ),
				'search_items'       => __( 'Buscar en Escuela', 'daniela-child' ),
				'not_found'          => __( 'No se encontraron ítems.', 'daniela-child' ),
				'not_found_in_trash' => __( 'No hay ítems en la papelera.', 'daniela-child' ),
				'all_items'          => __( 'Todos los ítems de Escuela', 'daniela-child' ),
				'menu_name'          => __( 'Escuela CPT', 'daniela-child' ),
			],
			'public'        => true,
			'has_archive'   => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-welcome-learn-more',
			'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
			'rewrite'       => [ 'slug' => 'escuela', 'with_front' => false ],
		]
	);

	// ------------------------------------------------------------------
	// dm_servicio  →  /servicios/
	// ------------------------------------------------------------------
	register_post_type(
		'dm_servicio',
		[
			'labels'       => [
				'name'               => __( 'Servicios', 'daniela-child' ),
				'singular_name'      => __( 'Servicio', 'daniela-child' ),
				'add_new'            => __( 'Añadir servicio', 'daniela-child' ),
				'add_new_item'       => __( 'Añadir nuevo servicio', 'daniela-child' ),
				'edit_item'          => __( 'Editar servicio', 'daniela-child' ),
				'new_item'           => __( 'Nuevo servicio', 'daniela-child' ),
				'view_item'          => __( 'Ver servicio', 'daniela-child' ),
				'view_items'         => __( 'Ver servicios', 'daniela-child' ),
				'search_items'       => __( 'Buscar servicios', 'daniela-child' ),
				'not_found'          => __( 'No se encontraron servicios.', 'daniela-child' ),
				'not_found_in_trash' => __( 'No hay servicios en la papelera.', 'daniela-child' ),
				'all_items'          => __( 'Todos los servicios', 'daniela-child' ),
				'menu_name'          => __( 'Servicios CPT', 'daniela-child' ),
			],
			'public'        => true,
			'has_archive'   => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-awards',
			'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
			'rewrite'       => [ 'slug' => 'servicios', 'with_front' => false ],
		]
	);
}

// =============================================================================
// TAXONOMÍAS
// =============================================================================

add_action( 'init', 'dm_register_taxonomies' );

function dm_register_taxonomies() {

	// ------------------------------------------------------------------
	// dm_tipo_recurso  →  gratis | pagos
	// ------------------------------------------------------------------
	register_taxonomy(
		'dm_tipo_recurso',
		[ 'dm_recurso' ],
		[
			'labels'       => [
				'name'              => __( 'Tipos de recurso', 'daniela-child' ),
				'singular_name'     => __( 'Tipo de recurso', 'daniela-child' ),
				'search_items'      => __( 'Buscar tipos', 'daniela-child' ),
				'all_items'         => __( 'Todos los tipos', 'daniela-child' ),
				'edit_item'         => __( 'Editar tipo', 'daniela-child' ),
				'update_item'       => __( 'Actualizar tipo', 'daniela-child' ),
				'add_new_item'      => __( 'Añadir tipo', 'daniela-child' ),
				'new_item_name'     => __( 'Nuevo tipo', 'daniela-child' ),
				'menu_name'         => __( 'Tipos', 'daniela-child' ),
			],
			'hierarchical'  => false,
			'public'        => true,
			'show_in_rest'  => true,
			'show_admin_column' => true,
			'rewrite'       => [ 'slug' => 'tipo-recurso' ],
		]
	);

	// ------------------------------------------------------------------
	// dm_tipo_escuela  →  cursos | talleres | programas
	// ------------------------------------------------------------------
	register_taxonomy(
		'dm_tipo_escuela',
		[ 'dm_escuela' ],
		[
			'labels'       => [
				'name'              => __( 'Tipos de Escuela', 'daniela-child' ),
				'singular_name'     => __( 'Tipo de Escuela', 'daniela-child' ),
				'search_items'      => __( 'Buscar tipos', 'daniela-child' ),
				'all_items'         => __( 'Todos los tipos', 'daniela-child' ),
				'edit_item'         => __( 'Editar tipo', 'daniela-child' ),
				'update_item'       => __( 'Actualizar tipo', 'daniela-child' ),
				'add_new_item'      => __( 'Añadir tipo', 'daniela-child' ),
				'new_item_name'     => __( 'Nuevo tipo', 'daniela-child' ),
				'menu_name'         => __( 'Tipos', 'daniela-child' ),
			],
			'hierarchical'  => false,
			'public'        => true,
			'show_in_rest'  => true,
			'show_admin_column' => true,
			'rewrite'       => [ 'slug' => 'tipo-escuela' ],
		]
	);

	// ------------------------------------------------------------------
	// dm_tipo_servicio  →  sesiones | membresias
	// ------------------------------------------------------------------
	register_taxonomy(
		'dm_tipo_servicio',
		[ 'dm_servicio' ],
		[
			'labels'       => [
				'name'              => __( 'Tipos de servicio', 'daniela-child' ),
				'singular_name'     => __( 'Tipo de servicio', 'daniela-child' ),
				'search_items'      => __( 'Buscar tipos', 'daniela-child' ),
				'all_items'         => __( 'Todos los tipos', 'daniela-child' ),
				'edit_item'         => __( 'Editar tipo', 'daniela-child' ),
				'update_item'       => __( 'Actualizar tipo', 'daniela-child' ),
				'add_new_item'      => __( 'Añadir tipo', 'daniela-child' ),
				'new_item_name'     => __( 'Nuevo tipo', 'daniela-child' ),
				'menu_name'         => __( 'Tipos', 'daniela-child' ),
			],
			'hierarchical'  => false,
			'public'        => true,
			'show_in_rest'  => true,
			'show_admin_column' => true,
			'rewrite'       => [ 'slug' => 'tipo-servicio' ],
		]
	);

	// ------------------------------------------------------------------
	// dm_tema  →  temas transversales (el admin los crea libremente)
	// ------------------------------------------------------------------
	register_taxonomy(
		'dm_tema',
		[ 'dm_recurso', 'dm_escuela', 'dm_servicio' ],
		[
			'labels'       => [
				'name'              => __( 'Temas', 'daniela-child' ),
				'singular_name'     => __( 'Tema', 'daniela-child' ),
				'search_items'      => __( 'Buscar temas', 'daniela-child' ),
				'all_items'         => __( 'Todos los temas', 'daniela-child' ),
				'edit_item'         => __( 'Editar tema', 'daniela-child' ),
				'update_item'       => __( 'Actualizar tema', 'daniela-child' ),
				'add_new_item'      => __( 'Añadir tema', 'daniela-child' ),
				'new_item_name'     => __( 'Nuevo tema', 'daniela-child' ),
				'menu_name'         => __( 'Temas', 'daniela-child' ),
			],
			'hierarchical'  => false,
			'public'        => true,
			'show_in_rest'  => true,
			'show_admin_column' => true,
			'rewrite'       => [ 'slug' => 'tema' ],
		]
	);
}

// =============================================================================
// DEFAULT TERMS — se crean en la activación del tema o en init si no existen.
// =============================================================================

add_action( 'init', 'dm_create_default_terms' );

function dm_create_default_terms() {
	// Sólo crea el término si no existe; no sobreescribe ni duplica.
	$defaults = [
		'dm_tipo_recurso'  => [ 'Gratis' => 'gratis', 'Pagos' => 'pagos' ],
		'dm_tipo_escuela'  => [ 'Cursos' => 'cursos', 'Talleres' => 'talleres', 'Programas' => 'programas' ],
		'dm_tipo_servicio' => [ 'Sesiones' => 'sesiones', 'Membresías' => 'membresias' ],
	];

	foreach ( $defaults as $taxonomy => $terms ) {
		foreach ( $terms as $name => $slug ) {
			if ( ! term_exists( $slug, $taxonomy ) ) {
				wp_insert_term( $name, $taxonomy, [ 'slug' => $slug ] );
			}
		}
	}
}
