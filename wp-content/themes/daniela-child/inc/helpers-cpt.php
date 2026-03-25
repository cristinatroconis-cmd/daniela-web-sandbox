<?php
/**
 * CPT Helpers — Metabox WooCommerce + renderizado de CTA y chips de taxonomía.
 *
 * Depende de: inc/cpt.php (CPTs y taxonomías registradas).
 * Reutiliza estilos dm-card / dm-btn / dm-chips definidos en el theme.
 *
 * @package Daniela_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// METABOX — Vinculación con producto WooCommerce
// =============================================================================

/**
 * Registra el metabox "Producto WooCommerce relacionado" en los 3 CPTs.
 */
add_action( 'add_meta_boxes', 'dm_cpt_register_wc_metabox' );

function dm_cpt_register_wc_metabox() {
	$post_types = [ 'dm_recurso', 'dm_escuela', 'dm_servicio' ];
	foreach ( $post_types as $pt ) {
		add_meta_box(
			'dm_wc_product',
			__( 'Producto WooCommerce relacionado', 'daniela-child' ),
			'dm_cpt_wc_metabox_html',
			$pt,
			'side',
			'default'
		);
	}
}

/**
 * HTML del metabox.
 *
 * @param WP_Post $post
 */
function dm_cpt_wc_metabox_html( $post ) {
	$product_id = (int) get_post_meta( $post->ID, '_dm_wc_product_id', true );
	wp_nonce_field( 'dm_wc_product_save', 'dm_wc_product_nonce' );
	?>
	<p>
		<label for="dm_wc_product_id">
			<?php esc_html_e( 'ID del producto en WooCommerce:', 'daniela-child' ); ?>
		</label>
		<input
			type="number"
			id="dm_wc_product_id"
			name="dm_wc_product_id"
			value="<?php echo esc_attr( $product_id ?: '' ); ?>"
			min="0"
			step="1"
			style="width:100%;margin-top:4px;"
			placeholder="Ej: 123"
		/>
	</p>
	<p class="description">
		<?php esc_html_e( 'Deja vacío si no hay producto vinculado. El CTA (botón de compra) usará este ID para mostrar precio y enlace.', 'daniela-child' ); ?>
	</p>
	<?php
}

/**
 * Guarda el meta _dm_wc_product_id al guardar el post.
 *
 * @param int $post_id
 */
add_action( 'save_post', 'dm_cpt_wc_metabox_save' );

function dm_cpt_wc_metabox_save( $post_id ) {
	// Verifica nonce, autoguardado y permisos.
	if (
		! isset( $_POST['dm_wc_product_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['dm_wc_product_nonce'] ), 'dm_wc_product_save' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$cpt_types = [ 'dm_recurso', 'dm_escuela', 'dm_servicio' ];
	if ( ! in_array( get_post_type( $post_id ), $cpt_types, true ) ) {
		return;
	}

	if ( isset( $_POST['dm_wc_product_id'] ) ) {
		$product_id = absint( $_POST['dm_wc_product_id'] );
		if ( $product_id > 0 ) {
			update_post_meta( $post_id, '_dm_wc_product_id', $product_id );
		} else {
			delete_post_meta( $post_id, '_dm_wc_product_id' );
		}
	}
}

// =============================================================================
// HELPERS — CTA del producto relacionado
// =============================================================================

/**
 * Obtiene el producto WooCommerce vinculado al post CPT actual.
 *
 * @param int|null $post_id  ID del post CPT. Usa el global si es null.
 * @return WC_Product|null   Producto WC o null si WC no está activo o no hay vínculo.
 */
function dm_cpt_get_linked_product( $post_id = null ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

	if ( null === $post_id ) {
		$post_id = get_the_ID();
	}

	$wc_product_id = (int) get_post_meta( $post_id, '_dm_wc_product_id', true );
	if ( ! $wc_product_id ) {
		return null;
	}

	return wc_get_product( $wc_product_id ) ?: null;
}

/**
 * Renderiza el CTA (botón de compra/descarga) para el producto relacionado.
 *
 * Comportamiento:
 * - Precio 0 → "Recíbelo gratis" (add_to_cart_url).
 * - Precio > 0 → "Comprar" (add_to_cart_url).
 * - Sin producto vinculado o WC inactivo → cadena vacía (falla silenciosamente).
 *
 * @param int|null $post_id  ID del post CPT. Usa el global si es null.
 * @return string            HTML del botón o cadena vacía.
 */
function dm_cpt_render_cta( $post_id = null ) {
	$product = dm_cpt_get_linked_product( $post_id );
	if ( ! $product ) {
		return '';
	}

	$price   = (float) $product->get_price();
	$is_free = ( $price == 0 ); // phpcs:ignore WordPress.PHP.StrictComparisons

	$label = $is_free
		? __( 'Recíbelo gratis', 'daniela-child' )
		: __( 'Comprar', 'daniela-child' );

	$btn_class = $is_free ? 'dm-btn dm-btn--secondary' : 'dm-btn dm-btn--primary';

	$url = $product->add_to_cart_url();

	ob_start();
	?>
	<div class="dm-cta">
		<?php if ( ! $is_free ) : ?>
			<span class="dm-cta__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
		<?php endif; ?>
		<a
			href="<?php echo esc_url( $url ); ?>"
			class="<?php echo esc_attr( $btn_class ); ?> add_to_cart_button ajax_add_to_cart"
			data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
			data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
		>
			<?php echo esc_html( $label ); ?>
		</a>
	</div>
	<?php
	return ob_get_clean();
}

// =============================================================================
// HELPERS — Chips de taxonomía para archives
// =============================================================================

/**
 * Renderiza los chips de una taxonomía CPT para filtrar el archive.
 *
 * Usa el querystring ?{param}=<slug> para el chip activo.
 * El chip "Todos" siempre aparece primero.
 *
 * @param string $taxonomy   Slug de la taxonomía (ej. 'dm_tipo_recurso').
 * @param string $param      Nombre del parámetro de la URL (ej. 'tipo').
 * @param string $base_url   URL base del archive (sin querystring).
 * @return string            HTML de los chips o cadena vacía si no hay términos.
 */
function dm_cpt_render_taxonomy_chips( $taxonomy, $param = 'tipo', $base_url = '' ) {
	$terms = get_terms( [
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	] );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_slug = isset( $_GET[ $param ] )
		? sanitize_title( wp_unslash( $_GET[ $param ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	if ( ! $base_url ) {
		$base_url = get_post_type_archive_link( get_queried_object()->name ?? '' );
	}

	ob_start();
	?>
	<nav class="dm-chips" aria-label="<?php esc_attr_e( 'Filtrar', 'daniela-child' ); ?>">
		<a
			href="<?php echo esc_url( $base_url ); ?>"
			class="dm-chip<?php echo '' === $active_slug ? ' dm-chip--active' : ''; ?>"
			<?php echo '' === $active_slug ? 'aria-current="true"' : ''; ?>
		>
			<?php esc_html_e( 'Todos', 'daniela-child' ); ?>
		</a>
		<?php foreach ( $terms as $term ) : ?>
			<a
				href="<?php echo esc_url( add_query_arg( $param, $term->slug, $base_url ) ); ?>"
				class="dm-chip<?php echo $active_slug === $term->slug ? ' dm-chip--active' : ''; ?>"
				<?php echo $active_slug === $term->slug ? 'aria-current="true"' : ''; ?>
			>
				<?php echo esc_html( $term->name ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	<?php
	return ob_get_clean();
}

/**
 * Construye los argumentos de WP_Query para el archive de un CPT con filtro de taxonomía.
 *
 * @param string $post_type  Slug del CPT.
 * @param string $taxonomy   Slug de la taxonomía de filtro.
 * @param string $param      Querystring param (ej. 'tipo').
 * @return array             Args para WP_Query.
 */
function dm_cpt_archive_query_args( $post_type, $taxonomy, $param = 'tipo' ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_slug = isset( $_GET[ $param ] )
		? sanitize_title( wp_unslash( $_GET[ $param ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	$args = [
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	];

	if ( $active_slug ) {
		$args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery
			[
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $active_slug,
			],
		];
	}

	return $args;
}

/**
 * Renderiza un grid de posts CPT como tarjetas.
 *
 * Cada tarjeta muestra: imagen destacada, título, excerpt y CTA WooCommerce.
 *
 * @param WP_Query $query    Query de posts CPT.
 * @return string            HTML del grid o mensaje "sin resultados".
 */
function dm_cpt_render_grid( $query ) {
	if ( ! $query->have_posts() ) {
		return '<p class="dm-no-results">' .
		       esc_html__( 'No hay ítems disponibles.', 'daniela-child' ) .
		       '</p>';
	}

	$html = '<div class="dm-grid">';

	while ( $query->have_posts() ) {
		$query->the_post();
		$post_id   = get_the_ID();
		$permalink = get_permalink();
		$title     = get_the_title();
		$excerpt   = get_the_excerpt();
		$thumb_id  = get_post_thumbnail_id();

		$html .= '<article class="dm-card">';

		if ( $thumb_id ) {
			$html .= '<a href="' . esc_url( $permalink ) . '" class="dm-card__image-link" tabindex="-1" aria-hidden="true">';
			$html .= '<div class="dm-card__thumb">';
			$html .= get_the_post_thumbnail( $post_id, 'woocommerce_thumbnail' );
			$html .= '</div>';
			$html .= '</a>';
		}

		$html .= '<div class="dm-card__body">';
		$html .= '<h3 class="dm-card__title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h3>';
		if ( $excerpt ) {
			$html .= '<p class="dm-card__excerpt">' . wp_kses_post( wp_trim_words( $excerpt, 20 ) ) . '</p>';
		}
		$html .= '</div>';

		$cta = dm_cpt_render_cta( $post_id );
		if ( $cta ) {
			$html .= '<div class="dm-card__footer">' . $cta . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		$html .= '</article>';
	}

	wp_reset_postdata();

	$html .= '</div>';

	return $html;
}
