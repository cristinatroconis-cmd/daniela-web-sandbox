<?php
/**
 * WP-CLI Command — Importador idempotente de recursos desde Media Library.
 *
 * Uso:
 *   wp dm import-recursos
 *   wp dm import-recursos --dry-run
 *
 * Qué hace:
 *   1. Busca attachments (PDF / MP3 / M4A) en wp_posts tipo 'attachment'.
 *   2. Por cada attachment:
 *      a. Crea o actualiza un producto WooCommerce descargable (simple).
 *         – precio 0 si el título contiene "gratuito" (case-insensitive); si no, $5.
 *         – asigna product_tag según keywords del título/descripción.
 *         – Detecta familia "Afirmaciones" → bundle ($9, tag=bundle).
 *      b. Crea o actualiza el CPT dm_recurso con excerpt y contenido.
 *         – asigna dm_tema con los mismos slugs que las product_tag.
 *      c. Guarda meta de trazabilidad:
 *         – _dm_source_attachment_id en producto y CPT.
 *         – _dm_wc_product_id en CPT.
 *
 * Idempotente: si el meta ya existe con el mismo attachment_id no duplica.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Comandos DM para Daniela Montes.
 */
class DM_CLI_Commands {

	// -------------------------------------------------------------------------
	// CONSTANTES DE CONFIGURACIÓN
	// -------------------------------------------------------------------------

	/** Precio por defecto para recursos de pago. */
	const DEFAULT_PRICE = 5.0;

	/** Precio para bundles. */
	const BUNDLE_PRICE = 9.0;

	/** Tipos MIME de archivos aceptados. */
	const ACCEPTED_MIME_TYPES = [
		'application/pdf',
		'audio/mpeg',
		'audio/mp4',
		'audio/x-m4a',
		'audio/m4a',
	];

	/**
	 * Mapa keyword → tag slug (para product_tag y dm_tema).
	 * Las claves son patrones en minúsculas para buscar en título + descripción.
	 * Máximo 3 tags por recurso.
	 */
	const KEYWORD_TAG_MAP = [
		// Emociones y regulación
		'emocion'      => 'gestion-emocional',
		'emocional'    => 'gestion-emocional',
		'regulac'      => 'gestion-emocional',
		'sentimiento'  => 'gestion-emocional',
		'escritura'    => 'gestion-emocional',

		// Autoestima / autoconocimiento
		'autoestima'   => 'autoestima',
		'autoimagen'   => 'autoestima',
		'autovalorac'  => 'autoestima',
		'identidad'    => 'autoconocimiento',
		'autoconocim'  => 'autoconocimiento',
		'valores'      => 'autoconocimiento',

		// Ansiedad
		'ansiedad'     => 'ansiedad',
		'preocupac'    => 'ansiedad',
		'nervios'      => 'ansiedad',
		'estrés'       => 'ansiedad',
		'estres'       => 'ansiedad',

		// Pensamientos
		'pensamiento'  => 'pensamientos',
		'distorsion'   => 'pensamientos',
		'distorsión'   => 'pensamientos',
		'creencia'     => 'pensamientos',
		'cognitiv'     => 'pensamientos',

		// Niña interior
		'niña interior' => 'nina-interior',
		'nina interior' => 'nina-interior',
		'inner child'   => 'nina-interior',

		// Afirmaciones
		'afirmacion'   => 'afirmaciones',
		'afirmación'   => 'afirmaciones',
		'afirmaciones' => 'afirmaciones',

		// Respiración
		'respirac'     => 'respiracion',
		'respira'      => 'respiracion',
		'mindfulness'  => 'respiracion',
		'meditac'      => 'respiracion',

		// Relaciones
		'relacion'     => 'relaciones',
		'relación'     => 'relaciones',
		'vinculo'      => 'relaciones',
		'vínculo'      => 'relaciones',
		'apego'        => 'relaciones',

		// Abundancia
		'abundancia'   => 'abundancia',
		'dinero'       => 'abundancia',
		'prosperidad'  => 'abundancia',
	];

	// -------------------------------------------------------------------------
	// COMANDO PRINCIPAL
	// -------------------------------------------------------------------------

	/**
	 * Importa attachments (PDF/MP3/M4A) de la Media Library como productos y recursos.
	 *
	 * ## OPTIONS
	 * [--dry-run]
	 * : No crea ni actualiza nada; solo reporta qué haría.
	 *
	 * [--force]
	 * : Actualiza productos/CPTs ya importados aunque no hayan cambiado.
	 *
	 * ## EXAMPLES
	 *
	 *     wp dm import-recursos
	 *     wp dm import-recursos --dry-run
	 *     wp dm import-recursos --force
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Named args.
	 */
	public function import_recursos( $args, $assoc_args ) {
		$dry_run = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$force   = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		if ( $dry_run ) {
			WP_CLI::line( '[DRY RUN] No se crearán ni actualizarán registros.' );
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			WP_CLI::error( 'WooCommerce no está activo. Actívalo antes de ejecutar este comando.' );
		}

		$attachments = $this->get_media_attachments();

		if ( empty( $attachments ) ) {
			WP_CLI::warning( 'No se encontraron attachments PDF/MP3/M4A en la Media Library.' );
			return;
		}

		WP_CLI::line( sprintf( 'Se encontraron %d attachments elegibles.', count( $attachments ) ) );

		$created  = 0;
		$updated  = 0;
		$skipped  = 0;
		$errors   = 0;

		foreach ( $attachments as $attachment ) {
			$result = $this->process_attachment( $attachment, $dry_run, $force );

			switch ( $result ) {
				case 'created':
					$created++;
					WP_CLI::success( sprintf( '[%d] Creado: "%s"', $attachment->ID, $attachment->post_title ) );
					break;
				case 'updated':
					$updated++;
					WP_CLI::line( sprintf( '[%d] Actualizado: "%s"', $attachment->ID, $attachment->post_title ) );
					break;
				case 'skipped':
					$skipped++;
					WP_CLI::debug( sprintf( '[%d] Sin cambios: "%s"', $attachment->ID, $attachment->post_title ) );
					break;
				default:
					$errors++;
					WP_CLI::warning( sprintf( '[%d] Error: "%s"', $attachment->ID, $attachment->post_title ) );
					break;
			}
		}

		WP_CLI::line( '---' );
		WP_CLI::success( sprintf(
			'Importación completa. Creados: %d | Actualizados: %d | Sin cambios: %d | Errores: %d',
			$created,
			$updated,
			$skipped,
			$errors
		) );
	}

	// -------------------------------------------------------------------------
	// HELPERS DE CONSULTA
	// -------------------------------------------------------------------------

	/**
	 * Obtiene todos los attachments PDF/MP3/M4A de la Media Library.
	 *
	 * @return WP_Post[]
	 */
	private function get_media_attachments() {
		$args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_mime_type' => self::ACCEPTED_MIME_TYPES,
		];

		return get_posts( $args );
	}

	// -------------------------------------------------------------------------
	// PROCESAMIENTO POR ATTACHMENT
	// -------------------------------------------------------------------------

	/**
	 * Procesa un attachment: crea/actualiza producto Woo y CPT dm_recurso.
	 *
	 * @param WP_Post $attachment Objeto attachment.
	 * @param bool    $dry_run    Solo simula.
	 * @param bool    $force      Fuerza actualización aunque no haya cambios.
	 * @return string 'created' | 'updated' | 'skipped' | 'error'
	 */
	private function process_attachment( WP_Post $attachment, bool $dry_run, bool $force ) {
		$attachment_id = $attachment->ID;
		$raw_title     = $attachment->post_title;
		$title         = $this->clean_title( $raw_title );
		$file_url      = wp_get_attachment_url( $attachment_id );

		if ( ! $file_url ) {
			return 'error';
		}

		$is_free      = $this->is_free( $title );
		$is_bundle    = $this->is_bundle( $title );
		$tags         = $this->derive_tags( $title . ' ' . $attachment->post_excerpt );
		$excerpt      = $this->generate_excerpt( $title, $attachment->post_excerpt );
		$price        = $is_free ? 0.0 : ( $is_bundle ? self::BUNDLE_PRICE : self::DEFAULT_PRICE );

		if ( $is_bundle ) {
			$tags = array_unique( array_merge( [ 'bundle' ], $tags ) );
		}

		// Limitar a 3 tags (el de bundle + 2 más, o 3 temáticos).
		$tags = array_slice( $tags, 0, 3 );

		// ---------------------------------------------------------------
		// Buscar producto existente por meta _dm_source_attachment_id
		// ---------------------------------------------------------------
		$existing_product_id = $this->find_post_by_attachment_meta( 'product', $attachment_id );
		$existing_recurso_id = $this->find_post_by_attachment_meta( 'dm_recurso', $attachment_id );

		$is_new = ( ! $existing_product_id && ! $existing_recurso_id );

		if ( ! $is_new && ! $force ) {
			return 'skipped';
		}

		if ( $dry_run ) {
			WP_CLI::line( sprintf(
				'  [DRY] %s | Título: "%s" | Precio: $%.2f | Tags: %s | Bundle: %s',
				$is_new ? 'CREAR' : 'ACTUALIZAR',
				$title,
				$price,
				implode( ', ', $tags ) ?: '(ninguno)',
				$is_bundle ? 'sí' : 'no'
			) );
			return $is_new ? 'created' : 'updated';
		}

		// ---------------------------------------------------------------
		// Crear/actualizar producto WooCommerce
		// ---------------------------------------------------------------
		$product_id = $this->upsert_wc_product(
			$existing_product_id,
			$title,
			$excerpt,
			$price,
			$file_url,
			$attachment_id,
			$tags
		);

		if ( ! $product_id ) {
			return 'error';
		}

		// ---------------------------------------------------------------
		// Crear/actualizar CPT dm_recurso
		// ---------------------------------------------------------------
		$recurso_id = $this->upsert_dm_recurso(
			$existing_recurso_id,
			$title,
			$excerpt,
			$attachment_id,
			$product_id,
			$tags
		);

		if ( ! $recurso_id ) {
			return 'error';
		}

		return $is_new ? 'created' : 'updated';
	}

	// -------------------------------------------------------------------------
	// WooCommerce PRODUCT UPSERT
	// -------------------------------------------------------------------------

	/**
	 * Crea o actualiza un producto WooCommerce descargable.
	 *
	 * @param int|null $product_id         Producto existente (o null para crear).
	 * @param string   $title              Título limpio.
	 * @param string   $excerpt            Excerpt del recurso.
	 * @param float    $price              Precio ($0 = gratis).
	 * @param string   $file_url           URL del archivo adjunto.
	 * @param int      $attachment_id      ID del attachment en Media Library.
	 * @param string[] $tag_slugs          Slugs de product_tag a asignar.
	 * @return int|null ID del producto o null en caso de error.
	 */
	private function upsert_wc_product( $product_id, string $title, string $excerpt, float $price, string $file_url, int $attachment_id, array $tag_slugs ) {
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				$product = new WC_Product_Simple();
			}
		} else {
			$product = new WC_Product_Simple();
		}

		$product->set_name( $title );
		$product->set_status( 'publish' );
		$product->set_description( $excerpt );
		$product->set_short_description( $excerpt );
		$product->set_downloadable( true );
		$product->set_virtual( true );
		$product->set_regular_price( (string) $price );
		$product->set_price( (string) $price );

		// Archivo descargable.
		$download_id   = md5( $file_url );
		$download_data = [
			$download_id => [
				'id'   => $download_id,
				'name' => $title,
				'file' => $file_url,
			],
		];
		$product->set_downloads( $download_data );
		$product->set_download_limit( -1 ); // Sin límite por defecto (el freebie flow usa tokens).
		$product->set_download_expiry( -1 );

		// Guardar.
		$saved_id = $product->save();

		if ( ! $saved_id ) {
			return null;
		}

		// Meta de trazabilidad.
		update_post_meta( $saved_id, '_dm_source_attachment_id', $attachment_id );

		// Product tags.
		if ( ! empty( $tag_slugs ) ) {
			$term_ids = $this->ensure_product_tags( $tag_slugs );
			wp_set_object_terms( $saved_id, $term_ids, 'product_tag', false );
		}

		// Categoría "Recursos" (product_cat).
		$this->ensure_product_cat( $saved_id, 'recursos' );

		return $saved_id;
	}

	// -------------------------------------------------------------------------
	// dm_recurso CPT UPSERT
	// -------------------------------------------------------------------------

	/**
	 * Crea o actualiza el CPT dm_recurso vinculado al attachment/producto.
	 *
	 * @param int|null $recurso_id    CPT existente (o null para crear).
	 * @param string   $title         Título limpio.
	 * @param string   $excerpt       Excerpt.
	 * @param int      $attachment_id ID del attachment.
	 * @param int      $product_id    ID del producto WC vinculado.
	 * @param string[] $tag_slugs     Slugs para dm_tema.
	 * @return int|null ID del CPT o null en caso de error.
	 */
	private function upsert_dm_recurso( $recurso_id, string $title, string $excerpt, int $attachment_id, int $product_id, array $tag_slugs ) {
		$post_data = [
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $excerpt,
			'post_status'  => 'publish',
			'post_type'    => 'dm_recurso',
		];

		if ( $recurso_id ) {
			$post_data['ID'] = $recurso_id;
			$saved_id        = wp_update_post( $post_data, true );
		} else {
			$saved_id = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $saved_id ) ) {
			WP_CLI::warning( 'wp_insert/update_post error: ' . $saved_id->get_error_message() );
			return null;
		}

		// Metas de trazabilidad y vínculo con producto.
		update_post_meta( $saved_id, '_dm_source_attachment_id', $attachment_id );
		update_post_meta( $saved_id, '_dm_wc_product_id', $product_id );

		// dm_tema taxonomy terms.
		if ( ! empty( $tag_slugs ) ) {
			$tema_term_ids = $this->ensure_dm_tema_terms( $tag_slugs );
			wp_set_object_terms( $saved_id, $tema_term_ids, 'dm_tema', false );
		}

		return $saved_id;
	}

	// -------------------------------------------------------------------------
	// TAXONOMÍAS Y TÉRMINOS
	// -------------------------------------------------------------------------

	/**
	 * Garantiza que los slugs de product_tag existen y devuelve sus IDs.
	 *
	 * @param string[] $slugs
	 * @return int[]
	 */
	private function ensure_product_tags( array $slugs ): array {
		$ids = [];
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, 'product_tag' );
			if ( ! $term ) {
				$result = wp_insert_term( $this->slug_to_name( $slug ), 'product_tag', [ 'slug' => $slug ] );
				if ( ! is_wp_error( $result ) ) {
					$ids[] = (int) $result['term_id'];
				}
			} else {
				$ids[] = (int) $term->term_id;
			}
		}
		return $ids;
	}

	/**
	 * Garantiza que los slugs de dm_tema existen y devuelve sus IDs.
	 *
	 * @param string[] $slugs
	 * @return int[]
	 */
	private function ensure_dm_tema_terms( array $slugs ): array {
		$ids = [];
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, 'dm_tema' );
			if ( ! $term ) {
				$result = wp_insert_term( $this->slug_to_name( $slug ), 'dm_tema', [ 'slug' => $slug ] );
				if ( ! is_wp_error( $result ) ) {
					$ids[] = (int) $result['term_id'];
				}
			} else {
				$ids[] = (int) $term->term_id;
			}
		}
		return $ids;
	}

	/**
	 * Asegura que el producto esté en la categoría product_cat dada.
	 *
	 * @param int    $product_id
	 * @param string $cat_slug
	 */
	private function ensure_product_cat( int $product_id, string $cat_slug ) {
		$term = get_term_by( 'slug', $cat_slug, 'product_cat' );
		if ( ! $term ) {
			$result = wp_insert_term( ucfirst( $cat_slug ), 'product_cat', [ 'slug' => $cat_slug ] );
			if ( is_wp_error( $result ) ) {
				return;
			}
			$term_id = (int) $result['term_id'];
		} else {
			$term_id = (int) $term->term_id;
		}

		wp_set_object_terms( $product_id, [ $term_id ], 'product_cat', true );
	}

	// -------------------------------------------------------------------------
	// BÚSQUEDA DE EXISTENTES
	// -------------------------------------------------------------------------

	/**
	 * Busca un post existente por meta _dm_source_attachment_id.
	 *
	 * @param string $post_type  'product' o 'dm_recurso'.
	 * @param int    $attachment_id
	 * @return int|null
	 */
	private function find_post_by_attachment_meta( string $post_type, int $attachment_id ) {
		$posts = get_posts( [
			'post_type'      => $post_type,
			'post_status'    => [ 'publish', 'draft', 'private' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_dm_source_attachment_id', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $attachment_id,             // phpcs:ignore WordPress.DB.SlowDBQuery
		] );

		return ! empty( $posts ) ? (int) $posts[0] : null;
	}

	// -------------------------------------------------------------------------
	// LÓGICA DE NEGOCIO
	// -------------------------------------------------------------------------

	/**
	 * Determina si el texto indica que el recurso es gratuito.
	 *
	 * Regla de negocio: SOLO el título que contenga la palabra "gratuito"
	 * (sin distinción de mayúsculas) establece el precio en $0.
	 *
	 * @param string $text
	 * @return bool
	 */
	private function is_free( string $text ): bool {
		return str_contains( mb_strtolower( $text, 'UTF-8' ), 'gratuito' );
	}

	/**
	 * Determina si el recurso pertenece a la familia "Afirmaciones" (bundle).
	 *
	 * @param string $title
	 * @return bool
	 */
	private function is_bundle( string $title ): bool {
		$lower = mb_strtolower( $title, 'UTF-8' );
		return (
			str_contains( $lower, 'afirmacion' ) ||
			str_contains( $lower, 'afirmación' )
		);
	}

	/**
	 * Deriva los slugs de tag a partir de palabras clave del texto.
	 *
	 * @param string $text
	 * @return string[] Array de slugs únicos.
	 */
	private function derive_tags( string $text ): array {
		$text_lower = mb_strtolower( $text, 'UTF-8' );
		$found      = [];

		foreach ( self::KEYWORD_TAG_MAP as $keyword => $tag_slug ) {
			if ( str_contains( $text_lower, $keyword ) ) {
				$found[ $tag_slug ] = true; // Usa slug como clave para deduplicar.
			}
		}

		return array_keys( $found );
	}

	/**
	 * Genera un excerpt legible para el CPT y el producto.
	 *
	 * @param string $title           Título limpio.
	 * @param string $existing_excerpt Descripción existente del attachment.
	 * @return string
	 */
	private function generate_excerpt( string $title, string $existing_excerpt ): string {
		if ( ! empty( trim( $existing_excerpt ) ) ) {
			return wp_strip_all_tags( wp_trim_words( $existing_excerpt, 30 ) );
		}

		// Fallback: usar el título como base del excerpt.
		return sprintf(
			/* translators: %s: resource title */
			__( 'Recurso descargable: %s. Herramienta práctica para tu bienestar psicológico.', 'daniela-child' ),
			$title
		);
	}

	/**
	 * Limpia el título del attachment (elimina extensiones, guiones/guiones bajos).
	 *
	 * @param string $raw
	 * @return string
	 */
	private function clean_title( string $raw ): string {
		// Eliminar extensión si aparece en el título.
		$clean = preg_replace( '/\.(pdf|mp3|m4a|mp4)$/i', '', $raw );
		// Convertir guiones/guiones bajos en espacios.
		$clean = str_replace( [ '-', '_' ], ' ', $clean ?? $raw );
		// Capitalizar primera letra de cada palabra.
		$clean = mb_convert_case( trim( $clean ), MB_CASE_TITLE, 'UTF-8' );

		return $clean ?: $raw;
	}

	/**
	 * Convierte un slug a nombre legible (desguioniza y capitaliza).
	 *
	 * @param string $slug
	 * @return string
	 */
	private function slug_to_name( string $slug ): string {
		return mb_convert_case( str_replace( '-', ' ', $slug ), MB_CASE_TITLE, 'UTF-8' );
	}
}

// Registrar el comando WP-CLI.
WP_CLI::add_command( 'dm', 'DM_CLI_Commands' );
