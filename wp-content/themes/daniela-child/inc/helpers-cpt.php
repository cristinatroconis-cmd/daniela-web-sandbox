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
 * Muestra el campo de ID de producto WC, información del producto vinculado
 * (nombre, tags, aviso si supera 3) y la nota de sincronización automática
 * de dm_tema desde product_tag.
 *
 * @param WP_Post $post
 */
function dm_cpt_wc_metabox_html($post)
{
	/** @var int $product_id ID del producto WC vinculado (0 si no hay). */
	$product_id = (int) get_post_meta($post->ID, '_dm_wc_product_id', true);
	wp_nonce_field('dm_wc_product_save', 'dm_wc_product_nonce');

	// Diccionario de tags recomendados (slugs core confirmados).
	$core_tags = [
		'ansiedad', 'autoestima', 'autoconocimiento',
		'gestion-emocional', 'mindfulness', 'relaciones',
		'sanacion', 'abundancia',
	];
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

<?php
	// --- Información del producto vinculado (si WC está activo y hay ID) ---
	if ($product_id > 0 && function_exists('wc_get_product')) :
		$product = wc_get_product($product_id);
		if ($product instanceof WC_Product) :
			$wc_tags = wp_get_object_terms(
				$product_id,
				'product_tag',
				[
					'orderby' => 'term_id',
					'order'   => 'ASC',
					'fields'  => 'all',
				]
			);
			$total_tags  = is_wp_error($wc_tags) ? 0 : count($wc_tags);
			$synced_tags = is_wp_error($wc_tags) ? [] : array_slice($wc_tags, 0, 3);
?>
	<div style="background:#f0f6ff;border-left:3px solid #2271b1;padding:8px 10px;margin-bottom:8px;font-size:12px;">
		<strong><?php esc_html_e('Producto vinculado:', 'daniela-child'); ?></strong>
		<?php echo esc_html($product->get_name()); ?>
		<span style="color:#666;"> (#<?php echo esc_html($product_id); ?>)</span>
	</div>

<?php		if ($total_tags > 0) : ?>
	<p style="margin:0 0 4px;font-size:12px;"><strong><?php esc_html_e('Tags del producto (product_tag):', 'daniela-child'); ?></strong></p>
	<p style="margin:0 0 6px;font-size:12px;">
<?php
			foreach ($synced_tags as $i => $tag) {
				$style = 'display:inline-block;background:#e0ffe0;border:1px solid #5cb85c;border-radius:3px;padding:1px 6px;margin:2px 2px 2px 0;font-size:11px;';
				echo '<span style="' . esc_attr($style) . '">' . esc_html($tag->slug) . '</span>';
			}
			if ($total_tags > 3) {
				$extra = $total_tags - 3;
				echo '<span style="color:#d63638;font-weight:bold;font-size:11px;margin-left:4px;">';
				/* translators: %d = número de tags adicionales que no se sincronizan */
				echo esc_html(sprintf(_n('(+%d tag no se sincronizará)', '(+%d tags no se sincronizarán)', $extra, 'daniela-child'), $extra));
				echo '</span>';
			}
?>
	</p>
<?php		else : ?>
	<p class="description" style="font-size:12px;">
		<?php esc_html_e('Este producto aún no tiene product_tag asignados en WooCommerce.', 'daniela-child'); ?>
	</p>
<?php		endif; // $total_tags > 0 ?>
<?php	endif; // $product instanceof WC_Product ?>
<?php	endif; // $product_id > 0 && wc ?>

	<p class="description" style="background:#fff8e1;border-left:3px solid #f0b429;padding:6px 8px;margin-top:6px;font-size:11px;">
		<?php esc_html_e('Los temas del CPT (dm_tema) se sincronizan automáticamente desde los tags de este producto en WooCommerce. Máximo 3 tags (orden: term_id ASC). No edites dm_tema manualmente.', 'daniela-child'); ?>
	</p>

	<p class="description" style="font-size:11px;margin-top:6px;">
		<strong><?php esc_html_e('Tags recomendados:', 'daniela-child'); ?></strong>
		<?php echo esc_html(implode(', ', $core_tags)); ?>
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
 * Renderiza el CTA (botón de compra) para el producto relacionado.
 *
 * Comportamiento:
 * - Producto comprable → "Agregar al carrito" vía AJAX (sin redirección a /carrito/).
 * - Producto NO comprable o sin stock → cadena vacía (no se muestra botón).
 * - Sin producto vinculado o WC inactivo → cadena vacía (falla silenciosamente).
 *
 * NOTA UX (Daniela Montes):
 * - El precio NO se imprime aquí: se muestra al lado del título en el grid.
 * - El popup de confirmación lo gestiona js/add-to-cart-popup.js.
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

	// Solo mostrar "Agregar al carrito" si el producto es realmente comprable.
	// Si no es comprable, add_to_cart_url() devuelve la página del producto,
	// lo que causaría el error "Sorry, this product cannot be purchased".
	if (! $product->is_purchasable() || ! $product->is_in_stock()) {
		return '';
	}

	$price   = (float) $product->get_price();
	$is_free = ((float) $price <= 0.0); // phpcs:ignore WordPress.PHP.StrictComparisons

	$label     = __('Agregar al carrito', 'daniela-child');
	$btn_class = $is_free ? 'dm-btn dm-btn--secondary' : 'dm-btn dm-btn--primary';
	$url       = esc_url(add_query_arg('add-to-cart', $product->get_id(), home_url('/')));

	ob_start();
?>
	<div class="dm-cta">
		<a
			href="<?php echo esc_url($url); ?>"
			class="<?php echo esc_attr($btn_class); ?> add_to_cart_button ajax_add_to_cart"
			data-product_id="<?php echo esc_attr($product->get_id()); ?>"
			data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
			data-quantity="1"
			data-product_name="<?php echo esc_attr($product->get_name()); ?>">
			<?php echo esc_html($label); ?>
		</a>
	</div>
	<?php
	return ob_get_clean();
}

// =============================================================================
// METABOX — URL del curso Tutor (solo dm_escuela)
// =============================================================================

add_action('add_meta_boxes', function () {
	add_meta_box(
		'dm_tutor_course_url',
		__('Curso Tutor (URL)', 'daniela-child'),
		function ($post) {
			$value = (string) get_post_meta($post->ID, '_dm_tutor_course_url', true);
			wp_nonce_field('dm_tutor_course_url_save', 'dm_tutor_course_url_nonce');
	?>
		<p>
			<label for="dm_tutor_course_url_field">
				<?php esc_html_e('Pega el path del curso (ej: /courses/tumenteencalma/):', 'daniela-child'); ?>
			</label>
			<input
				type="text"
				id="dm_tutor_course_url_field"
				name="dm_tutor_course_url_field"
				value="<?php echo esc_attr($value); ?>"
				style="width:100%;margin-top:4px;"
				placeholder="/courses/tumenteencalma/" />
		</p>
	<?php
		},
		'dm_escuela',
		'side',
		'default'
	);
});

add_action('save_post', function ($post_id) {
	if (
		! isset($_POST['dm_tutor_course_url_nonce']) ||
		! wp_verify_nonce(sanitize_key($_POST['dm_tutor_course_url_nonce']), 'dm_tutor_course_url_save')
	) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (! current_user_can('edit_post', $post_id)) {
		return;
	}
	if ('dm_escuela' !== get_post_type($post_id)) {
		return;
	}

	$url = isset($_POST['dm_tutor_course_url_field'])
		? sanitize_text_field(trim((string) wp_unslash($_POST['dm_tutor_course_url_field'])))
		: '';

	if ($url !== '') {
		update_post_meta($post_id, '_dm_tutor_course_url', $url);
	} else {
		delete_post_meta($post_id, '_dm_tutor_course_url');
	}
});

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
 * Para dm_escuela: usa _dm_tutor_course_url como enlace de imagen/título y
 * muestra dos botones en el footer ("Ver curso" + CTA WooCommerce).
 * Para otros CPTs: comportamiento original.
 *
 * UX Daniela Montes:
 * - El precio se muestra junto al título (si hay producto vinculado).
 * - El footer queda limpio solo para acciones.
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

		// Para dm_escuela: enlazar imagen/título al curso Tutor si existe.
		$tutor_url = '';
		if ('dm_escuela' === get_post_type($post_id)) {
			$tutor_url = trim((string) get_post_meta($post_id, '_dm_tutor_course_url', true));
		}
		$card_link = $tutor_url !== '' ? $tutor_url : $permalink;

		// Producto vinculado (para precio junto al título y CTA Woo).
		$product = dm_cpt_get_linked_product($post_id);
		$price_html = '';
		$is_free = false;

		if ($product) {
			$price = (float) $product->get_price();
			$is_free = ($price <= 0.0); // phpcs:ignore WordPress.PHP.StrictComparisons
			if (! $is_free) {
				$price_html = (string) $product->get_price_html();
			}
		}

		$html .= '<article class="dm-card">';

		if ($thumb_id) {
			$html .= '<a href="' . esc_url($card_link) . '" class="dm-card__image-link" tabindex="-1" aria-hidden="true">';
			$html .= '<div class="dm-card__thumb">';
			$html .= get_the_post_thumbnail($post_id, 'woocommerce_thumbnail');
			$html .= '</div>';
			$html .= '</a>';
		}

		$html .= '<div class="dm-card__body">';

		// Title row (título + precio a la derecha).
		$html .= '<div class="dm-card__title-row">';
		$html .= '<h3 class="dm-card__title"><a href="' . esc_url($card_link) . '">' . esc_html($title) . '</a></h3>';

		if ($product) {
			if ($is_free) {
				$html .= '<span class="dm-card__price dm-card__price--free">' . esc_html__('Gratis', 'daniela-child') . '</span>';
			} elseif ($price_html !== '') {
				$html .= '<span class="dm-card__price dm-card__price--paid">' . wp_kses_post($price_html) . '</span>';
			}
		}

		$html .= '</div>'; // .dm-card__title-row

		if ($excerpt) {
			$html .= '<p class="dm-card__excerpt">' . esc_html( wp_trim_words( wp_strip_all_tags( $excerpt ), 20 ) ) . '</p>';
		}

		$html .= '</div>'; // .dm-card__body

		$cta_buy = dm_cpt_render_cta($post_id);

		// Siempre renderizar el footer con "Ver detalles" para todos los CPTs.
		// Para dm_escuela: enlace al single CPT (que tiene la URL con tipo).
		// Para dm_recurso / dm_servicio: enlace al single CPT también.
		$html .= '<div class="dm-card__footer dm-card__footer--actions">';

		// 1) "Ver detalles" — siempre enlaza al single del CPT.
		$html .= '<a class="dm-btn dm-btn--ghost" href="' . esc_url($permalink) . '">'
			. esc_html__('Ver detalles', 'daniela-child')
			. '</a>';

		// 2) CTA WooCommerce (Agregar al carrito) — solo si el producto es comprable.
		if (! empty($cta_buy)) {
			$html .= $cta_buy; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		$html .= '</div>';

		$html .= '</article>';
	}

	wp_reset_postdata();

	$html .= '</div>';

	return $html;
}

// =============================================================================
// HELPERS — Chips de categorías WooCommerce para archive dm_escuela (Ruta A)
// =============================================================================

/**
 * Renderiza los chips de filtro del archive /escuela/ basados en las categorías
 * de producto WooCommerce (cursos / talleres / programas) en lugar de la
 * taxonomía dm_tipo_escuela, siguiendo la Ruta A para no duplicar clasificación.
 *
 * @param string $param     Parámetro de URL (ej. 'tipo'). Default 'tipo'.
 * @param string $base_url  URL base del archive (sin querystring).
 * @return string           HTML de los chips.
 */
function dm_escuela_render_woo_chips($param = 'tipo', $base_url = '')
{
	// Categorías Woo relevantes para Escuela (slug => etiqueta visible).
	$cats = [
		'cursos'    => __('Cursos', 'daniela-child'),
		'talleres'  => __('Talleres', 'daniela-child'),
		'programas' => __('Programas', 'daniela-child'),
	];

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_slug = isset($_GET[$param])
		? sanitize_title(wp_unslash($_GET[$param])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	if (! $base_url) {
		$base_url = get_post_type_archive_link('dm_escuela');
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
		<?php foreach ($cats as $slug => $label) : ?>
			<a
				href="<?php echo esc_url(add_query_arg($param, $slug, $base_url)); ?>"
				class="dm-chip<?php echo $active_slug === $slug ? ' dm-chip--active' : ''; ?>"
				<?php echo $active_slug === $slug ? 'aria-current="true"' : ''; ?>>
				<?php echo esc_html($label); ?>
			</a>
		<?php endforeach; ?>
	</nav>
<?php
	return ob_get_clean();
}

/**
 * Construye los argumentos de WP_Query para el archive de dm_escuela filtrando
 * por categoría WooCommerce del producto relacionado (Ruta A).
 *
 * Cuando ?tipo=<slug> está presente, obtiene en PHP los IDs de posts dm_escuela
 * cuyo _dm_wc_product_id pertenece a esa categoría Woo y limita el query a ellos.
 *
 * @param string $param  Querystring param (ej. 'tipo').
 * @return array         Args para WP_Query.
 */
function dm_escuela_query_args_by_woo_cat($param = 'tipo')
{
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_slug = isset($_GET[$param])
		? sanitize_title(wp_unslash($_GET[$param])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	$base_args = [
		'post_type'      => 'dm_escuela',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	];

	if (! $active_slug) {
		return $base_args;
	}

	// Filtra en PHP: obtener IDs de dm_escuela cuyos productos estén en esa categoría Woo.
	$all_ids = get_posts([
		'post_type'      => 'dm_escuela',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_dm_wc_product_id', // phpcs:ignore WordPress.DB.SlowDBQuery
	]);

	$filtered_ids = [];
	foreach ($all_ids as $post_id) {
		$wc_id = (int) get_post_meta($post_id, '_dm_wc_product_id', true);
		if ($wc_id > 0 && has_term($active_slug, 'product_cat', $wc_id)) {
			$filtered_ids[] = $post_id;
		}
	}

	// post__in con array vacío devuelve todos los posts; usamos [0] para resultado vacío.
	$base_args['post__in'] = ! empty($filtered_ids) ? $filtered_ids : [0];

	return $base_args;
}

// =============================================================================
// HELPERS — Chips WooCommerce para archive dm_servicio (Servicios) — Ruta A (estricto)
// =============================================================================

function dm_servicios_render_woo_chips($param = 'tipo', $base_url = '')
{
	$cats = [
		'sesiones'      => __('Sesiones', 'daniela-child'),
		'paquetes'      => __('Paquetes', 'daniela-child'),
		'membresias'    => __('Membresías', 'daniela-child'),
		'supervisiones' => __('Supervisiones', 'daniela-child'),
	];

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_slug = isset($_GET[$param])
		? sanitize_title(wp_unslash($_GET[$param])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	if (! $base_url) {
		$base_url = get_post_type_archive_link('dm_servicio');
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

		<?php foreach ($cats as $slug => $label) : ?>
			<a
				href="<?php echo esc_url(add_query_arg($param, $slug, $base_url)); ?>"
				class="dm-chip<?php echo $active_slug === $slug ? ' dm-chip--active' : ''; ?>"
				<?php echo $active_slug === $slug ? 'aria-current="true"' : ''; ?>>
				<?php echo esc_html($label); ?>
			</a>
		<?php endforeach; ?>
	</nav>
<?php
	return ob_get_clean();
}

function dm_servicios_query_args_by_woo_cat_strict($param = 'tipo')
{
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_slug = isset($_GET[$param])
		? sanitize_title(wp_unslash($_GET[$param])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		: '';

	$base_args = [
		'post_type'      => 'dm_servicio',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	];

	$allowed = ['sesiones', 'paquetes', 'membresias', 'supervisiones'];
	if ($active_slug && ! in_array($active_slug, $allowed, true)) {
		$base_args['post__in'] = [0];
		return $base_args;
	}

	$all_ids = get_posts([
		'post_type'      => 'dm_servicio',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_dm_wc_product_id', // phpcs:ignore WordPress.DB.SlowDBQuery
	]);

	$filtered_ids = [];

	foreach ($all_ids as $post_id) {
		$wc_id = (int) get_post_meta($post_id, '_dm_wc_product_id', true);
		if ($wc_id <= 0) {
			continue;
		}

		// Estricto: debe estar dentro del árbol de "servicios".
		if (! has_term('servicios', 'product_cat', $wc_id)) {
			continue;
		}

		// Si hay filtro activo, debe estar en esa subcat exacta.
		if ($active_slug && ! has_term($active_slug, 'product_cat', $wc_id)) {
			continue;
		}

		$filtered_ids[] = $post_id;
	}

	$base_args['post__in'] = ! empty($filtered_ids) ? $filtered_ids : [0];

	return $base_args;
}
