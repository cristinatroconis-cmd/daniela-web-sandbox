<?php

/**
 * CPT Helpers — Metabox WooCommerce + renderizado de CTA y chips de taxonomía.
 *
 * Depende de: inc/cpt.php (CPTs y taxonomías registradas).
 * Reutiliza estilos dm-card / dm-btn / dm-chips definidos en el theme.
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
	exit;
}

// =============================================================================
// METABOX — Vinculación con producto WooCommerce
// =============================================================================

/**
 * Registra el metabox "Producto WooCommerce relacionado" en los 3 CPTs.
 */
add_action('add_meta_boxes', 'dm_cpt_register_wc_metabox');

function dm_cpt_register_wc_metabox()
{
	$post_types = ['dm_recurso', 'dm_escuela', 'dm_servicio'];
	foreach ($post_types as $pt) {
		add_meta_box(
			'dm_wc_product',
			__('Producto WooCommerce relacionado', 'daniela-child'),
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
function dm_cpt_wc_metabox_html($post)
{
	$product_id = (int) get_post_meta($post->ID, '_dm_wc_product_id', true);
	wp_nonce_field('dm_wc_product_save', 'dm_wc_product_nonce');
?>
	<p>
		<label for="dm_wc_product_id">
			<?php esc_html_e('ID del producto en WooCommerce:', 'daniela-child'); ?>
		</label>
		<input
			type="number"
			id="dm_wc_product_id"
			name="dm_wc_product_id"
			value="<?php echo esc_attr($product_id ?: ''); ?>"
			min="0"
			step="1"
			style="width:100%;margin-top:4px;"
			placeholder="Ej: 123" />
	</p>
	<p class="description">
		<?php esc_html_e('Deja vacío si no hay producto vinculado. El CTA (botón de compra) usará este ID para mostrar precio y enlace.', 'daniela-child'); ?>
	</p>
<?php
}

/**
 * Guarda el meta _dm_wc_product_id al guardar el post.
 *
 * @param int $post_id
 */
add_action('save_post', 'dm_cpt_wc_metabox_save');

function dm_cpt_wc_metabox_save($post_id)
{
	// Verifica nonce, autoguardado y permisos.
	if (
		! isset($_POST['dm_wc_product_nonce']) ||
		! wp_verify_nonce(sanitize_key($_POST['dm_wc_product_nonce']), 'dm_wc_product_save')
	) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (! current_user_can('edit_post', $post_id)) {
		return;
	}

	$cpt_types = ['dm_recurso', 'dm_escuela', 'dm_servicio'];
	if (! in_array(get_post_type($post_id), $cpt_types, true)) {
		return;
	}

	if (isset($_POST['dm_wc_product_id'])) {
		$product_id = absint($_POST['dm_wc_product_id']);
		if ($product_id > 0) {
			update_post_meta($post_id, '_dm_wc_product_id', $product_id);
		} else {
			delete_post_meta($post_id, '_dm_wc_product_id');
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
function dm_cpt_get_linked_product($post_id = null)
{
	if (! function_exists('wc_get_product')) {
		return null;
	}

	if (null === $post_id) {
		$post_id = get_the_ID();
	}

	$wc_product_id = (int) get_post_meta($post_id, '_dm_wc_product_id', true);
	if (! $wc_product_id) {
		return null;
	}

	return wc_get_product($wc_product_id) ?: null;
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
function dm_cpt_render_cta($post_id = null)
{
	$product = dm_cpt_get_linked_product($post_id);
	if (! $product) {
		return '';
	}

	$price   = (float) $product->get_price();
	$is_free = ((float) $price <= 0.0); // phpcs:ignore WordPress.PHP.StrictComparisons

	// CTA unificado: siempre agregamos al carrito (incluye productos gratis).
	$label = __('Agregar al carrito', 'daniela-child');

	// Si quieres diferenciar gratis visualmente, mantenemos estilos distintos,
	// pero el texto y el flujo son los mismos.
	$btn_class = $is_free ? 'dm-btn dm-btn--secondary' : 'dm-btn dm-btn--primary';

	$url = $product->add_to_cart_url();

	ob_start();
?>
	<div class="dm-cta">
		<?php if (! $is_free) : ?>
			<span class="dm-cta__price"><?php echo wp_kses_post($product->get_price_html()); ?></span>
		<?php endif; ?>
		<a
			href="<?php echo esc_url($url); ?>"
			class="<?php echo esc_attr($btn_class); ?> add_to_cart_button ajax_add_to_cart"
			data-product_id="<?php echo esc_attr($product->get_id()); ?>"
			data-product_sku="<?php echo esc_attr($product->get_sku()); ?>">
			<?php echo esc_html($label); ?>
		</a>
	</div>
<?php
	return ob_get_clean();
}

// =============================================================================
// METABOX — Integración con Tutor LMS (solo dm_escuela)
// =============================================================================

/**
 * Registra el metabox "Curso Tutor LMS relacionado" en dm_escuela.
 */
add_action( 'add_meta_boxes', 'dm_tutor_register_metabox' );

function dm_tutor_register_metabox() {
	add_meta_box(
		'dm_tutor_course',
		__( 'Curso Tutor LMS relacionado', 'daniela-child' ),
		'dm_tutor_metabox_html',
		'dm_escuela',
		'side',
		'default'
	);
}

/**
 * HTML del metabox Tutor LMS.
 *
 * @param WP_Post $post
 */
function dm_tutor_metabox_html( $post ) {
	$course_id  = (int) get_post_meta( $post->ID, '_dm_tutor_course_id', true );
	$course_url = get_post_meta( $post->ID, '_dm_tutor_course_url', true );
	wp_nonce_field( 'dm_tutor_course_save', 'dm_tutor_course_nonce' );
?>
	<p>
		<label for="dm_tutor_course_id">
			<?php esc_html_e( 'ID del curso en Tutor LMS:', 'daniela-child' ); ?>
		</label>
		<input
			type="number"
			id="dm_tutor_course_id"
			name="dm_tutor_course_id"
			value="<?php echo esc_attr( $course_id ?: '' ); ?>"
			min="0"
			step="1"
			style="width:100%;margin-top:4px;"
			placeholder="Ej: 456" />
	</p>
	<p>
		<label for="dm_tutor_course_url">
			<?php esc_html_e( 'URL directa al curso (opcional):', 'daniela-child' ); ?>
		</label>
		<input
			type="url"
			id="dm_tutor_course_url"
			name="dm_tutor_course_url"
			value="<?php echo esc_attr( $course_url ); ?>"
			style="width:100%;margin-top:4px;"
			placeholder="https://..." />
	</p>
	<p class="description">
		<?php esc_html_e( 'Vincula este ítem a un curso de Tutor LMS. Si el usuario está inscrito verá "Ir al curso" en lugar del botón de compra.', 'daniela-child' ); ?>
	</p>
<?php
}

/**
 * Guarda los metas _dm_tutor_course_id y _dm_tutor_course_url al guardar el post.
 *
 * @param int $post_id
 */
add_action( 'save_post', 'dm_tutor_metabox_save' );

function dm_tutor_metabox_save( $post_id ) {
	if (
		! isset( $_POST['dm_tutor_course_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['dm_tutor_course_nonce'] ), 'dm_tutor_course_save' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( 'dm_escuela' !== get_post_type( $post_id ) ) {
		return;
	}

	// Save course ID.
	if ( isset( $_POST['dm_tutor_course_id'] ) ) {
		$course_id = absint( $_POST['dm_tutor_course_id'] );
		if ( $course_id > 0 ) {
			update_post_meta( $post_id, '_dm_tutor_course_id', $course_id );
		} else {
			delete_post_meta( $post_id, '_dm_tutor_course_id' );
		}
	}

	// Save course URL.
	if ( isset( $_POST['dm_tutor_course_url'] ) ) {
		$course_url = esc_url_raw( wp_unslash( $_POST['dm_tutor_course_url'] ) );
		if ( $course_url ) {
			update_post_meta( $post_id, '_dm_tutor_course_url', $course_url );
		} else {
			delete_post_meta( $post_id, '_dm_tutor_course_url' );
		}
	}
}

// =============================================================================
// HELPERS — Acceso y CTA Tutor LMS
// =============================================================================

/**
 * Determina si el usuario actual tiene acceso al curso Tutor LMS vinculado.
 *
 * Estrategia:
 * 1. Usa tutor_utils()->is_enrolled() si Tutor LMS está disponible.
 * 2. Fallback: consulta posts de tipo tutor_enrolled (estructura interna de Tutor).
 *
 * @param int|null $post_id  ID del post dm_escuela. Usa el global si es null.
 * @return bool
 */
function dm_tutor_user_has_access( $post_id = null ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( null === $post_id ) {
		$post_id = get_the_ID();
	}

	$course_id = (int) get_post_meta( $post_id, '_dm_tutor_course_id', true );
	if ( ! $course_id ) {
		return false;
	}

	$user_id = get_current_user_id();

	// Admins siempre tienen acceso.
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	// Tutor LMS: verificación nativa de inscripción.
	if ( function_exists( 'tutor_utils' ) ) {
		return (bool) tutor_utils()->is_enrolled( $course_id, $user_id );
	}

	// Fallback: busca registros de inscripción en posts de tipo tutor_enrolled.
	$enrolled = get_posts( [
		'post_type'      => 'tutor_enrolled',
		'post_status'    => 'completed',
		'author'         => $user_id,
		'post_parent'    => $course_id,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	] );

	return ! empty( $enrolled );
}

/**
 * Devuelve la URL del curso Tutor LMS vinculado al post dm_escuela.
 *
 * Prioridad: URL manual guardada en meta > permalink del post Tutor course.
 *
 * @param int|null $post_id  ID del post dm_escuela. Usa el global si es null.
 * @return string            URL del curso o cadena vacía.
 */
function dm_tutor_get_course_url( $post_id = null ) {
	if ( null === $post_id ) {
		$post_id = get_the_ID();
	}

	$manual_url = get_post_meta( $post_id, '_dm_tutor_course_url', true );
	if ( $manual_url ) {
		return esc_url( $manual_url );
	}

	$course_id = (int) get_post_meta( $post_id, '_dm_tutor_course_id', true );
	if ( $course_id ) {
		$url = get_permalink( $course_id );
		if ( $url ) {
			return esc_url( $url );
		}
	}

	return '';
}

/**
 * Renderiza el CTA "Ir al curso" para usuarios con acceso al curso Tutor LMS.
 *
 * @param int|null $post_id  ID del post dm_escuela. Usa el global si es null.
 * @return string            HTML del botón o cadena vacía.
 */
function dm_cpt_render_tutor_cta( $post_id = null ) {
	$course_url = dm_tutor_get_course_url( $post_id );
	if ( ! $course_url ) {
		return '';
	}

	ob_start();
?>
	<div class="dm-cta dm-cta--tutor">
		<a href="<?php echo esc_url( $course_url ); ?>" class="dm-btn dm-btn--primary">
			<?php esc_html_e( 'Ir al curso', 'daniela-child' ); ?>
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
function dm_cpt_render_taxonomy_chips($taxonomy, $param = 'tipo', $base_url = '')
{
	$terms = get_terms([
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	]);

	if (is_wp_error($terms) || empty($terms)) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_slug = isset($_GET[$param])
		? sanitize_title(wp_unslash($_GET[$param])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	if (! $base_url) {
		$base_url = get_post_type_archive_link(get_queried_object()->name ?? '');
	}

	ob_start();
?>
	<nav class="dm-chips" aria-label="<?php esc_attr_e('Filtrar', 'daniela-child'); ?>">
		<a
			href="<?php echo esc_url($base_url); ?>"
			class="dm-chip<?php echo '' === $active_slug ? ' dm-chip--active' : ''; ?>"
			<?php echo '' === $active_slug ? 'aria-current="true"' : ''; ?>>
			<?php esc_html_e('Todos', 'daniela-child'); ?>
		</a>
		<?php foreach ($terms as $term) : ?>
			<a
				href="<?php echo esc_url(add_query_arg($param, $term->slug, $base_url)); ?>"
				class="dm-chip<?php echo $active_slug === $term->slug ? ' dm-chip--active' : ''; ?>"
				<?php echo $active_slug === $term->slug ? 'aria-current="true"' : ''; ?>>
				<?php echo esc_html($term->name); ?>
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
function dm_cpt_archive_query_args($post_type, $taxonomy, $param = 'tipo')
{
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_slug = isset($_GET[$param])
		? sanitize_title(wp_unslash($_GET[$param])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	$args = [
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	];

	if ($active_slug) {
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
function dm_cpt_render_grid($query)
{
	if (! $query->have_posts()) {
		return '<p class="dm-no-results">' .
			esc_html__('No hay ítems disponibles.', 'daniela-child') .
			'</p>';
	}

	$html = '<div class="dm-grid">';

	while ($query->have_posts()) {
		$query->the_post();
		$post_id   = get_the_ID();
		$permalink = get_permalink();
		$title     = get_the_title();
		$excerpt   = get_the_excerpt();
		$thumb_id  = get_post_thumbnail_id();

		$html .= '<article class="dm-card">';

		if ($thumb_id) {
			$html .= '<a href="' . esc_url($permalink) . '" class="dm-card__image-link" tabindex="-1" aria-hidden="true">';
			$html .= '<div class="dm-card__thumb">';
			$html .= get_the_post_thumbnail($post_id, 'woocommerce_thumbnail');
			$html .= '</div>';
			$html .= '</a>';
		}

		$html .= '<div class="dm-card__body">';
		$html .= '<h3 class="dm-card__title"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3>';
		if ($excerpt) {
			$html .= '<p class="dm-card__excerpt">' . wp_kses_post(wp_trim_words($excerpt, 20)) . '</p>';
		}
		$html .= '</div>';

		$cta = dm_cpt_render_cta($post_id);
		if ($cta) {
			$html .= '<div class="dm-card__footer">' . $cta . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		$html .= '</article>';
	}

	wp_reset_postdata();

	$html .= '</div>';

	return $html;
}
