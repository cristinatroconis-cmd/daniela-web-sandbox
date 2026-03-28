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
// AUTO-CLASSIFICATION — dm_escuela: infiere dm_tipo_escuela del título si no está asignado.
// =============================================================================

add_action( 'save_post_dm_escuela', 'dm_escuela_auto_classify_tipo', 20, 2 );

/**
 * Asigna automáticamente el término dm_tipo_escuela cuando no hay ninguno asignado.
 *
 * Reglas (sin distinción de mayúsculas/minúsculas):
 *  - título contiene "taller"   → talleres
 *  - título contiene "programa" → programas
 *  - en otro caso               → cursos
 *
 * No sobreescribe si ya hay términos asignados.
 *
 * @param int     $post_id
 * @param WP_Post $post
 */
function dm_escuela_auto_classify_tipo( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	// No sobreescribir si ya hay términos asignados.
	$existing = wp_get_object_terms( $post_id, 'dm_tipo_escuela', [ 'fields' => 'ids' ] );
	if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
		return;
	}

	$title = mb_strtolower( $post->post_title );

	if ( false !== mb_strpos( $title, 'taller' ) ) {
		$slug = 'talleres';
	} elseif ( false !== mb_strpos( $title, 'programa' ) ) {
		$slug = 'programas';
	} else {
		$slug = 'cursos';
	}

	// Asegura que el término exista antes de asignarlo.
	$term = get_term_by( 'slug', $slug, 'dm_tipo_escuela' );
	if ( ! $term ) {
		$names    = [ 'talleres' => 'Talleres', 'programas' => 'Programas', 'cursos' => 'Cursos' ];
		$inserted = wp_insert_term( $names[ $slug ], 'dm_tipo_escuela', [ 'slug' => $slug ] );
		if ( is_wp_error( $inserted ) ) {
			return;
		}
		$term_id = $inserted['term_id'];
	} else {
		$term_id = $term->term_id;
	}

	wp_set_object_terms( $post_id, [ $term_id ], 'dm_tipo_escuela' );
}

// =============================================================================
// ADMIN BULK ACTION — Backfill dm_tipo_escuela en posts existentes de dm_escuela.
// =============================================================================

add_filter( 'bulk_actions-edit-dm_escuela', 'dm_escuela_register_bulk_classify' );

/**
 * Registra la acción masiva "Auto-clasificar tipo (backfill)" en la lista de dm_escuela.
 *
 * @param array $actions
 * @return array
 */
function dm_escuela_register_bulk_classify( $actions ) {
	$actions['dm_backfill_tipo'] = __( 'Auto-clasificar tipo (backfill)', 'daniela-child' );
	return $actions;
}

add_filter( 'handle_bulk_actions-edit-dm_escuela', 'dm_escuela_handle_bulk_classify', 10, 3 );

/**
 * Procesa la acción masiva de clasificación automática.
 *
 * Solo clasifica posts sin dm_tipo_escuela asignado.
 *
 * @param string $redirect_url
 * @param string $action
 * @param int[]  $post_ids
 * @return string
 */
function dm_escuela_handle_bulk_classify( $redirect_url, $action, $post_ids ) {
	if ( 'dm_backfill_tipo' !== $action ) {
		return $redirect_url;
	}

	$count = 0;

	foreach ( $post_ids as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'dm_escuela' !== $post->post_type ) {
			continue;
		}

		// Omite posts que ya tienen término asignado.
		$existing = wp_get_object_terms( $post_id, 'dm_tipo_escuela', [ 'fields' => 'ids' ] );
		if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
			continue;
		}

		$title = mb_strtolower( $post->post_title );

		if ( false !== mb_strpos( $title, 'taller' ) ) {
			$slug = 'talleres';
		} elseif ( false !== mb_strpos( $title, 'programa' ) ) {
			$slug = 'programas';
		} else {
			$slug = 'cursos';
		}

		$term = get_term_by( 'slug', $slug, 'dm_tipo_escuela' );
		if ( $term ) {
			wp_set_object_terms( $post_id, [ $term->term_id ], 'dm_tipo_escuela' );
			$count++;
		}
	}

	return add_query_arg( 'dm_backfill_count', $count, $redirect_url );
}

add_action( 'admin_notices', 'dm_escuela_bulk_classify_notice' );

/**
 * Muestra aviso de administración tras la acción masiva de clasificación.
 */
function dm_escuela_bulk_classify_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['dm_backfill_count'] ) ) {
		return;
	}

	$count = (int) $_GET['dm_backfill_count']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		sprintf(
			/* translators: %d: número de posts actualizados */
			esc_html( _n( '%d ítem de Escuela clasificado.', '%d ítems de Escuela clasificados.', $count, 'daniela-child' ) ),
			$count
		)
	);
}

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
