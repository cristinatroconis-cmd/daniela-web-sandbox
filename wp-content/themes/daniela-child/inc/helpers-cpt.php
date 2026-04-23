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
// ADMIN UI — Selector de medios para metaboxes de imagen
// =============================================================================

add_action('admin_enqueue_scripts', 'dm_admin_enqueue_media_for_metaboxes');

function dm_admin_enqueue_media_for_metaboxes()
{
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (! $screen || ! in_array($screen->post_type, ['dm_recurso', 'dm_escuela', 'dm_servicio', 'product', 'page'], true)) {
		return;
	}

	wp_enqueue_media();
}

add_action('admin_print_footer_scripts-post.php', 'dm_admin_print_media_picker_script');
add_action('admin_print_footer_scripts-post-new.php', 'dm_admin_print_media_picker_script');

function dm_admin_print_media_picker_script()
{
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (! $screen || ! in_array($screen->post_type, ['dm_recurso', 'dm_escuela', 'dm_servicio', 'product', 'page'], true)) {
		return;
	}
?>
	<script>
		document.addEventListener('click', function(event) {
			var selectButton = event.target.closest('.dm-media-field__select');
			var removeButton = event.target.closest('.dm-media-field__remove');

			if (selectButton) {
				event.preventDefault();
				var wrapper = selectButton.closest('.dm-media-field');
				if (!wrapper || typeof wp === 'undefined' || !wp.media) {
					return;
				}

				var input = wrapper.querySelector('.dm-media-field__input');
				var preview = wrapper.querySelector('.dm-media-field__preview');
				var frame = wp.media({
					title: 'Selecciona una imagen',
					button: {
						text: 'Usar esta imagen'
					},
					multiple: false
				});

				frame.on('select', function() {
					var attachment = frame.state().get('selection').first().toJSON();
					if (input) {
						input.value = attachment.url || '';
					}
					if (preview) {
						preview.innerHTML = attachment.url ? '<img src="' + attachment.url + '" alt="" style="max-width:100%;height:auto;border-radius:6px;display:block;" />' : '';
					}
				});

				frame.open();
			}

			if (removeButton) {
				event.preventDefault();
				var removeWrapper = removeButton.closest('.dm-media-field');
				if (!removeWrapper) {
					return;
				}
				var removeInput = removeWrapper.querySelector('.dm-media-field__input');
				var removePreview = removeWrapper.querySelector('.dm-media-field__preview');
				if (removeInput) {
					removeInput.value = '';
				}
				if (removePreview) {
					removePreview.innerHTML = '';
				}
			}
		});
	</script>
<?php
}

function dm_render_media_picker_field($field_id, $value = '', $help = '')
{
	$value = trim((string) $value);
	echo '<div class="dm-media-field">';
	echo '<input type="text" class="dm-media-field__input" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" style="width:100%;margin-bottom:8px;" readonly />';
	echo '<p><button type="button" class="button dm-media-field__select">' . esc_html__('Seleccionar imagen', 'daniela-child') . '</button> <button type="button" class="button-link-delete dm-media-field__remove">' . esc_html__('Quitar imagen', 'daniela-child') . '</button></p>';
	echo '<div class="dm-media-field__preview" style="margin-top:8px;">';
	if ($value !== '') {
		echo '<img src="' . esc_url($value) . '" alt="" style="max-width:100%;height:auto;border-radius:6px;display:block;" />';
	}
	echo '</div>';
	if ($help !== '') {
		echo '<p class="description" style="margin-top:6px;">' . esc_html($help) . '</p>';
	}
	echo '</div>';
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
		'ansiedad',
		'autoestima',
		'autoconocimiento',
		'gestion-emocional',
		'mindfulness',
		'relaciones',
		'sanacion',
		'abundancia',
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

			<?php if ($total_tags > 0) : ?>
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
			<?php else : ?>
				<p class="description" style="font-size:12px;">
					<?php esc_html_e('Este producto aún no tiene product_tag asignados en WooCommerce.', 'daniela-child'); ?>
				</p>
			<?php endif; // $total_tags > 0 
			?>
		<?php endif; // $product instanceof WC_Product 
		?>
	<?php endif; // $product_id > 0 && wc 
	?>

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

function dm_cpt_get_catalog_excerpt($post_id)
{
	$product = dm_cpt_get_linked_product($post_id);
	if ($product instanceof WC_Product) {
		$excerpt = trim(wp_strip_all_tags((string) $product->get_short_description()));
		if ($excerpt !== '') {
			return $excerpt;
		}
	}

	return trim(wp_strip_all_tags((string) get_the_excerpt($post_id)));
}

function dm_cpt_get_catalog_image_html($post_id, $size = 'woocommerce_thumbnail')
{
	$product = dm_cpt_get_linked_product($post_id);
	if ($product instanceof WC_Product && $product->get_image_id()) {
		return wp_get_attachment_image($product->get_image_id(), $size, false, ['loading' => 'lazy']);
	}

	if (has_post_thumbnail($post_id)) {
		return get_the_post_thumbnail($post_id, $size, ['loading' => 'lazy']);
	}

	return '';
}

function dm_cpt_get_catalog_image_url($post_id, $size = 'large')
{
	$product = dm_cpt_get_linked_product($post_id);
	if ($product instanceof WC_Product && $product->get_image_id()) {
		$image_url = wp_get_attachment_image_url($product->get_image_id(), $size);
		if ($image_url) {
			return (string) $image_url;
		}
	}

	if (has_post_thumbnail($post_id)) {
		$image_url = get_the_post_thumbnail_url($post_id, $size);
		if ($image_url) {
			return (string) $image_url;
		}
	}

	return '';
}

function dm_cpt_get_single_hero_image($post_id, $size = 'large')
{
	$post_id = absint($post_id);
	if ($post_id <= 0) {
		return [
			'url'    => '',
			'source' => '',
		];
	}

	$hero_image_url = trim((string) get_post_meta($post_id, '_dm_single_hero_image_url', true));
	if ($hero_image_url !== '') {
		return [
			'url'    => $hero_image_url,
			'source' => 'single-meta',
		];
	}

	if (has_post_thumbnail($post_id)) {
		$image_url = get_the_post_thumbnail_url($post_id, $size);
		if ($image_url) {
			return [
				'url'    => (string) $image_url,
				'source' => 'post-thumbnail',
			];
		}
	}

	$product = dm_cpt_get_linked_product($post_id);
	if ($product instanceof WC_Product && $product->get_image_id()) {
		$image_url = wp_get_attachment_image_url($product->get_image_id(), $size);
		if ($image_url) {
			return [
				'url'    => (string) $image_url,
				'source' => 'product-image',
			];
		}
	}

	return [
		'url'    => '',
		'source' => '',
	];
}

function dm_cpt_render_editorial_heading($title = '', $image_url = '', $variant = 'section')
{
	$title     = trim((string) $title);
	$image_url = trim((string) $image_url);
	$class     = 'final' === $variant ? 'dm-editorial__final-title' : 'dm-editorial__section-title';

	if ($image_url !== '') {
		$alt = $title !== '' ? $title : __('Título de sección', 'daniela-child');
		return '<div class="dm-editorial__title-media dm-editorial__title-media--' . esc_attr($variant) . '"><img src="' . esc_url($image_url) . '" alt="' . esc_attr($alt) . '" loading="lazy" /></div>';
	}

	if ($title === '') {
		return '';
	}

	return '<h2 class="' . esc_attr($class) . '">' . esc_html($title) . '</h2>';
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
function dm_cpt_render_cta($post_id = null, $args = [])
{
	$product = dm_cpt_get_linked_product($post_id);
	if (! $product) {
		return '';
	}

	$price   = (float) $product->get_price();
	$is_free = ((float) $price <= 0.0); // phpcs:ignore WordPress.PHP.StrictComparisons
	$uses_waitlist = function_exists('dm_product_uses_waitlist') && dm_product_uses_waitlist($product);

	// Stock: siempre respetar stock.
	if (! $uses_waitlist && ! $product->is_in_stock()) {
		return '';
	}

	// Para productos de pago: exigir purchasable para evitar casos raros.
	// Para productos gratis: permitir el CTA aunque purchasable sea false
	// (p.ej. por plugins de memberships/subscriptions), manteniendo el mismo UX.
	if (! $uses_waitlist && ! $is_free && ! $product->is_purchasable()) {
		return '';
	}

	$args      = is_array($args) ? $args : [];
	$btn_class = isset($args['class']) && trim((string) $args['class']) !== ''
		? trim((string) $args['class'])
		: 'dm-btn dm-btn--primary';
	$cta       = function_exists('dm_get_product_primary_cta')
		? dm_get_product_primary_cta($product, [
			'class'          => $btn_class,
			'label'          => isset($args['label']) ? (string) $args['label'] : '',
			'waitlist_label' => __('Unirme a la lista de espera', 'daniela-child'),
		])
		: [
			'mode'       => 'cart',
			'url'        => (string) $product->add_to_cart_url(),
			'label'      => isset($args['label']) && trim((string) $args['label']) !== '' ? trim((string) $args['label']) : __('Agregar al carrito', 'daniela-child'),
			'class'      => $btn_class,
			'attributes' => [
				'data-product_id'   => (string) $product->get_id(),
				'data-product_sku'  => (string) $product->get_sku(),
				'data-quantity'     => '1',
				'data-product_name' => (string) $product->get_name(),
			],
		];

	ob_start();
?>
	<div class="dm-cta">
		<a
			href="<?php echo esc_url($cta['url']); ?>"
			class="<?php echo esc_attr($cta['class']); ?><?php echo 'cart' === $cta['mode'] ? ' add_to_cart_button ajax_add_to_cart' : ''; ?>"
			<?php if ('cart' === $cta['mode']) : ?>data-product_id="<?php echo esc_attr($cta['attributes']['data-product_id']); ?>"
			data-product_sku="<?php echo esc_attr($cta['attributes']['data-product_sku']); ?>"
			data-quantity="<?php echo esc_attr($cta['attributes']['data-quantity']); ?>"
			data-product_name="<?php echo esc_attr($cta['attributes']['data-product_name']); ?>"
			<?php endif; ?>>
			<?php echo esc_html($cta['label']); ?>
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
// METABOX — Imagen Hero para Singles (dm_recurso, dm_escuela, dm_servicio)
// =============================================================================

add_action('add_meta_boxes', function () {
	$post_types = ['dm_recurso', 'dm_escuela', 'dm_servicio'];
	foreach ($post_types as $pt) {
		add_meta_box(
			'dm_single_hero_image',
			__('Imagen Hero del single', 'daniela-child'),
			function ($post) {
				$value = (string) get_post_meta($post->ID, '_dm_single_hero_image_url', true);
				wp_nonce_field('dm_single_hero_image_save', 'dm_single_hero_image_nonce');
		?>
			<p>
				<label for="dm_single_hero_image_url_field">
					<?php esc_html_e('URL de imagen vertical para el single (opcional):', 'daniela-child'); ?>
				</label>
				<input
					type="url"
					id="dm_single_hero_image_url_field"
					name="dm_single_hero_image_url_field"
					value="<?php echo esc_attr($value); ?>"
					style="width:100%;margin-top:4px;"
					placeholder="https://.../imagen-vertical.jpg" />
			</p>
			<p class="description" style="font-size:11px;">
				<?php esc_html_e('Si se completa, esta imagen reemplaza la destacada solo en el template single del CPT.', 'daniela-child'); ?>
			</p>
	<?php
			},
			$pt,
			'side',
			'default'
		);
	}
});

add_action('save_post', function ($post_id) {
	if (
		! isset($_POST['dm_single_hero_image_nonce']) ||
		! wp_verify_nonce(sanitize_key($_POST['dm_single_hero_image_nonce']), 'dm_single_hero_image_save')
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

	$url = isset($_POST['dm_single_hero_image_url_field'])
		? esc_url_raw(trim((string) wp_unslash($_POST['dm_single_hero_image_url_field'])))
		: '';

	if ($url !== '') {
		update_post_meta($post_id, '_dm_single_hero_image_url', $url);
	} else {
		delete_post_meta($post_id, '_dm_single_hero_image_url');
	}
});

// =============================================================================
// ADMIN UI — Lista blanca de metaboxes por post type
// =============================================================================

/**
 * Limita los metaboxes visibles en edicion para simplificar la interfaz.
 *
 * Product (Woo):
 * - Product data
 * - Memberships
 * - Publish
 * - Product categories
 * - Product tags
 * - Facebook Product Sync
 *
 * dm_recurso / dm_servicio:
 * - Excerpt
 * - Membership
 * - Publish
 * - Producto Woo relacionado
 * - Featured image
 * - Imagen hero del single
 *
 * dm_escuela:
 * - Igual que arriba + URL Tutor.
 */
add_action('add_meta_boxes', 'dm_limit_metaboxes_for_editorial_flow', 999);

function dm_limit_metaboxes_for_editorial_flow()
{
	global $wp_meta_boxes;

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (! $screen || 'post' !== $screen->base) {
		return;
	}

	$post_type = $screen->post_type;
	if (! $post_type || ! isset($wp_meta_boxes[$post_type])) {
		return;
	}

	$allowed = [
		'product' => [
			'submitdiv',
			'woocommerce-product-data',
			'wc-memberships-product-memberships-data',
			'product_catdiv',
			'tagsdiv-product_tag',
			'facebook_metabox',
			'postexcerpt',
			'postimagediv',
		],
		'dm_recurso' => [
			'wc-memberships-post-memberships-data',
			'submitdiv',
			'dm_wc_product',
			'dm_editorial_sections',
		],
		'dm_servicio' => [
			'wc-memberships-post-memberships-data',
			'submitdiv',
			'dm_wc_product',
			'dm_editorial_sections',
		],
		'dm_escuela' => [
			'wc-memberships-post-memberships-data',
			'submitdiv',
			'dm_wc_product',
			'dm_editorial_sections',
			'dm_tutor_course_url',
		],
	];

	if (! isset($allowed[$post_type])) {
		return;
	}

	$allowed_ids = $allowed[$post_type];
	$contexts    = ['normal', 'advanced', 'side'];

	foreach ($contexts as $context) {
		if (! isset($wp_meta_boxes[$post_type][$context])) {
			continue;
		}

		foreach ($wp_meta_boxes[$post_type][$context] as $priority_boxes) {
			if (! is_array($priority_boxes)) {
				continue;
			}

			foreach ($priority_boxes as $meta_id => $meta_box) {
				if (in_array($meta_id, $allowed_ids, true)) {
					continue;
				}

				remove_meta_box($meta_id, $post_type, $context);
			}
		}
	}
}

/**
 * Ajusta la UI de edición para productos y CPTs editoriales.
 */
add_action('init', 'dm_cleanup_editorial_cpt_supports', 20);
add_action('init', 'dm_enable_product_excerpt_support', 21);
add_action('admin_head-post-new.php', 'dm_adjust_editorial_admin_boxes');
add_action('admin_head-post.php', 'dm_adjust_editorial_admin_boxes');
add_action('admin_footer-post-new.php', 'dm_reorder_product_catalog_metaboxes');
add_action('admin_footer-post.php', 'dm_reorder_product_catalog_metaboxes');
add_action('add_meta_boxes_product', 'dm_customize_product_catalog_metaboxes', 30);
add_filter('default_hidden_meta_boxes', 'dm_force_editorial_metabox_visibility', 10, 2);
add_filter('hidden_meta_boxes', 'dm_force_editorial_metabox_visibility', 10, 2);

function dm_cleanup_editorial_cpt_supports()
{
	foreach (['dm_recurso', 'dm_escuela', 'dm_servicio'] as $post_type) {
		remove_post_type_support($post_type, 'editor');
		remove_post_type_support($post_type, 'excerpt');
	}
}

function dm_enable_product_excerpt_support()
{
	add_post_type_support('product', 'excerpt');
}

function dm_adjust_editorial_admin_boxes()
{
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (! $screen || empty($screen->post_type)) {
		return;
	}

	if ('product' === $screen->post_type) {
		echo '<style>#postdivrich{display:none !important;}</style>';
		return;
	}

	if (in_array($screen->post_type, ['dm_recurso', 'dm_escuela', 'dm_servicio'], true)) {
		echo '<style>#postdivrich,#postexcerpt{display:none !important;}</style>';
	}
}

function dm_force_editorial_metabox_visibility($hidden, $screen)
{
	if (! $screen || empty($screen->post_type)) {
		return $hidden;
	}

	if ('product' === $screen->post_type) {
		$hidden = array_diff((array) $hidden, ['postimagediv', 'postexcerpt', 'dm_product_catalog_excerpt']);
	}

	if (in_array($screen->post_type, ['dm_recurso', 'dm_escuela', 'dm_servicio'], true)) {
		$hidden = array_unique(array_merge((array) $hidden, ['postexcerpt']));
	}

	return array_values($hidden);
}

function dm_customize_product_catalog_metaboxes()
{
	add_post_type_support('product', 'excerpt');

	remove_meta_box('postimagediv', 'product', 'side');
	remove_meta_box('postimagediv', 'product', 'normal');
	add_meta_box(
		'postimagediv',
		__('Imagen que se muestra en el catálogo', 'daniela-child'),
		'post_thumbnail_meta_box',
		'product',
		'normal',
		'high'
	);

	remove_meta_box('postexcerpt', 'product', 'normal');
	remove_meta_box('postexcerpt', 'product', 'advanced');
	add_meta_box(
		'dm_product_catalog_excerpt',
		__('Excerpt que se muestra en el catálogo', 'daniela-child'),
		'dm_render_product_catalog_excerpt_metabox',
		'product',
		'normal',
		'core'
	);
}

function dm_render_product_catalog_excerpt_metabox($post)
{
	wp_nonce_field('dm_product_catalog_excerpt_save', 'dm_product_catalog_excerpt_nonce');
	$value = (string) get_post_field('post_excerpt', $post->ID, 'edit');

	echo '<textarea id="dm_product_catalog_excerpt_field" name="dm_product_catalog_excerpt" rows="5" style="width:100%;">' . esc_textarea($value) . '</textarea>';
	echo '<p class="description" style="margin-top:8px;">' . esc_html__('Este texto se usa en las tarjetas de catálogo y archives del sitio.', 'daniela-child') . '</p>';
}

add_action('save_post_product', 'dm_save_product_catalog_excerpt', 20, 2);

function dm_save_product_catalog_excerpt($post_id, $post)
{
	if (! $post || 'product' !== $post->post_type) {
		return;
	}

	if (
		! isset($_POST['dm_product_catalog_excerpt_nonce']) ||
		! wp_verify_nonce(sanitize_key($_POST['dm_product_catalog_excerpt_nonce']), 'dm_product_catalog_excerpt_save')
	) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (! current_user_can('edit_post', $post_id)) {
		return;
	}

	$excerpt = isset($_POST['dm_product_catalog_excerpt'])
		? sanitize_textarea_field(wp_unslash($_POST['dm_product_catalog_excerpt']))
		: '';

	remove_action('save_post_product', 'dm_save_product_catalog_excerpt', 20);
	wp_update_post([
		'ID'           => $post_id,
		'post_excerpt' => $excerpt,
	]);
	add_action('save_post_product', 'dm_save_product_catalog_excerpt', 20, 2);
}

function dm_reorder_product_catalog_metaboxes()
{
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (! $screen || 'product' !== $screen->post_type) {
		return;
	}
	?>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			var normalSortables = document.getElementById('normal-sortables');
			if (!normalSortables) {
				return;
			}

			var imageBox = document.getElementById('postimagediv');
			var excerptBox = document.getElementById('dm_product_catalog_excerpt');
			var membershipsBox = document.getElementById('wc-memberships-product-memberships-data');

			if (imageBox) {
				normalSortables.insertBefore(imageBox, normalSortables.firstChild);
			}

			if (excerptBox && membershipsBox && membershipsBox.parentNode === normalSortables) {
				normalSortables.insertBefore(excerptBox, membershipsBox);
			}

			if (imageBox && excerptBox && imageBox.nextSibling !== excerptBox) {
				normalSortables.insertBefore(excerptBox, imageBox.nextSibling);
			}
		});
	</script>
<?php
}

// =============================================================================
// METABOX — Contenido estructurado tipo landing para singles CPT
// =============================================================================

function dm_cpt_editorial_fields_config()
{
	return [
		'_dm_single_hero_image_url'        => ['label' => __('Imagen hero del single', 'daniela-child'), 'type' => 'media', 'help' => __('Selecciona una imagen desde medios. Si la dejas vacía, se usará la imagen del producto o el fallback disponible.', 'daniela-child')],
		'_dm_editorial_hero_kicker'        => ['label' => __('Texto superior del hero', 'daniela-child'), 'type' => 'text', 'placeholder' => 'Aprende a regular tu mente y tu cuerpo desde la raíz'],
		'_dm_editorial_hero_intro'         => ['label' => __('Bajada del hero', 'daniela-child'), 'type' => 'textarea', 'placeholder' => 'Deja de luchar con...', 'help' => __('Texto breve de 1–3 líneas para explicar el beneficio principal.', 'daniela-child')],
		'_dm_editorial_hero_button_label'  => ['label' => __('Texto botón hero', 'daniela-child'), 'type' => 'text', 'placeholder' => 'Agregar al carrito'],
		'_dm_editorial_fit_title'          => ['label' => __('Título sección “Es para ti si...”', 'daniela-child'), 'type' => 'text', 'placeholder' => 'Es para ti si...'],
		'_dm_editorial_fit_title_image'    => ['label' => __('Imagen título sección “Es para ti si...”', 'daniela-child'), 'type' => 'media', 'help' => __('Opcional. Si eliges una imagen, reemplaza al título escrito en el frontend. Si la dejas vacía, se usa el título de texto.', 'daniela-child')],
		'_dm_editorial_fit_items'          => ['label' => __('Items sección “Es para ti si...” (uno por línea)', 'daniela-child'), 'type' => 'textarea', 'placeholder' => "Sabes que tus pensamientos...\nVives en estado de alerta...", 'help' => __('Escribe un item por línea. Ideal 6–14 palabras por item.', 'daniela-child')],
		'_dm_editorial_learn_title'        => ['label' => __('Título sección “Qué vas a aprender”', 'daniela-child'), 'type' => 'text', 'placeholder' => 'Qué vas a aprender'],
		'_dm_editorial_learn_title_image'  => ['label' => __('Imagen título sección aprendizaje', 'daniela-child'), 'type' => 'media', 'help' => __('Opcional. Si eliges una imagen, reemplaza al título escrito en el frontend.', 'daniela-child')],
		'_dm_editorial_learn_intro'        => ['label' => __('Texto corto sección aprendizaje', 'daniela-child'), 'type' => 'textarea', 'placeholder' => 'No es solo otro curso...', 'help' => __('Texto breve para introducir la lista de aprendizajes (1–2 líneas).', 'daniela-child')],
		'_dm_editorial_learn_items'        => ['label' => __('Lista aprendizaje (uno por línea)', 'daniela-child'), 'type' => 'textarea', 'placeholder' => "A entender qué te pasa...\nA calmar tu cuerpo...", 'help' => __('Un aprendizaje por línea. Empieza con verbos en infinitivo ("Entender…", "Aprender…").', 'daniela-child')],
		'_dm_editorial_learn_image'        => ['label' => __('Imagen sección aprendizaje', 'daniela-child'), 'type' => 'media', 'help' => __('Selecciona la imagen desde la biblioteca de medios.', 'daniela-child')],
		'_dm_editorial_learn_button_label' => ['label' => __('Texto botón sección aprendizaje', 'daniela-child'), 'type' => 'text', 'placeholder' => 'Agregar al carrito'],
		'_dm_editorial_diff_title'         => ['label' => __('Título sección “Qué hace diferente...”', 'daniela-child'), 'type' => 'text', 'placeholder' => '¿Qué hace diferente a este proceso?'],
		'_dm_editorial_diff_title_image'   => ['label' => __('Imagen título sección diferenciadores', 'daniela-child'), 'type' => 'media', 'help' => __('Opcional. Si eliges una imagen, reemplaza al título escrito en el frontend.', 'daniela-child')],
		'_dm_editorial_diff_items'         => ['label' => __('Lista diferenciadores (uno por línea)', 'daniela-child'), 'type' => 'textarea', 'placeholder' => "Te explico solo lo necesario...\nDiseñado para pocos minutos...", 'help' => __('Un diferenciador por línea. Enfócate en beneficios claros y concretos.', 'daniela-child')],
		'_dm_editorial_diff_image'         => ['label' => __('Imagen sección diferenciadores', 'daniela-child'), 'type' => 'media', 'help' => __('Selecciona la imagen desde la biblioteca de medios.', 'daniela-child')],
		'_dm_editorial_diff_button_label'  => ['label' => __('Texto botón sección diferenciadores', 'daniela-child'), 'type' => 'text', 'placeholder' => 'Agregar al carrito'],
		'_dm_editorial_include_title'      => ['label' => __('Título sección “Incluye”', 'daniela-child'), 'type' => 'text', 'placeholder' => '4 módulos'],
		'_dm_editorial_include_title_image' => ['label' => __('Imagen título sección “Incluye”', 'daniela-child'), 'type' => 'media', 'help' => __('Opcional. Si eliges una imagen, reemplaza al título escrito en el frontend.', 'daniela-child')],
		'_dm_editorial_include_items'      => ['label' => __('Lista “Incluye” (uno por línea)', 'daniela-child'), 'type' => 'textarea', 'placeholder' => "Clases en video...\nRecursos descargables...", 'help' => __('Un item por línea (qué recibe la persona: videos, audios, PDFs, soporte, etc.).', 'daniela-child')],
		'_dm_editorial_final_title'        => ['label' => __('Título CTA final', 'daniela-child'), 'type' => 'text', 'placeholder' => 'Si esto resonó contigo...'],
		'_dm_editorial_final_title_image'  => ['label' => __('Imagen título CTA final', 'daniela-child'), 'type' => 'media', 'help' => __('Opcional. Si eliges una imagen, reemplaza al título escrito en el frontend.', 'daniela-child')],
		'_dm_editorial_final_text'         => ['label' => __('Texto CTA final', 'daniela-child'), 'type' => 'textarea', 'placeholder' => 'Estás a un solo paso...', 'help' => __('Texto de 1–3 líneas para cerrar y reforzar el beneficio con claridad.', 'daniela-child')],
		'_dm_editorial_final_button_label' => ['label' => __('Texto botón CTA final', 'daniela-child'), 'type' => 'text', 'placeholder' => 'Agregar al carrito'],
	];
}

add_action('add_meta_boxes', 'dm_cpt_register_editorial_metabox');

function dm_cpt_register_editorial_metabox()
{
	$post_types = ['dm_recurso', 'dm_escuela', 'dm_servicio'];
	foreach ($post_types as $pt) {
		add_meta_box(
			'dm_editorial_sections',
			__('Secciones del single tipo landing', 'daniela-child'),
			'dm_cpt_editorial_metabox_html',
			$pt,
			'normal',
			'high'
		);
	}
}

function dm_cpt_editorial_metabox_html($post)
{
	wp_nonce_field('dm_editorial_sections_save', 'dm_editorial_sections_nonce');
	$fields = dm_cpt_editorial_fields_config();

	echo '<p class="description" style="margin-bottom:12px;">';
	echo esc_html__('Usa estos campos como un ACF ligero para construir los singles con secciones fijas y estilo consistente. En los listados, escribe un item por línea.', 'daniela-child');
	echo ' ';
	echo esc_html__('Todos los bloques son opcionales: si dejas una sección vacía, no se mostrará en el single.', 'daniela-child');
	echo '</p>';

	foreach ($fields as $key => $field) {
		$value = (string) get_post_meta($post->ID, $key, true);
		echo '<div style="margin-bottom:14px;">';
		echo '<label for="' . esc_attr($key) . '" style="font-weight:600;display:block;margin-bottom:4px;">' . esc_html($field['label']) . '</label>';
		if ('textarea' === $field['type']) {
			echo '<textarea id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" rows="4" placeholder="' . esc_attr(isset($field['placeholder']) ? $field['placeholder'] : '') . '" style="width:100%;">' . esc_textarea($value) . '</textarea>';
			if (! empty($field['help'])) {
				echo '<span class="description" style="display:block;margin-top:4px;">' . esc_html($field['help']) . '</span>';
			}
		} elseif ('media' === $field['type']) {
			dm_render_media_picker_field($key, $value, isset($field['help']) ? (string) $field['help'] : '');
		} else {
			echo '<input type="' . esc_attr($field['type']) . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr(isset($field['placeholder']) ? $field['placeholder'] : '') . '" style="width:100%;" />';
			if (! empty($field['help'])) {
				echo '<span class="description" style="display:block;margin-top:4px;">' . esc_html($field['help']) . '</span>';
			}
		}
		echo '</div>';
	}
}

add_action('save_post', 'dm_cpt_save_editorial_metabox');

function dm_cpt_save_editorial_metabox($post_id)
{
	if (
		! isset($_POST['dm_editorial_sections_nonce']) ||
		! wp_verify_nonce(sanitize_key($_POST['dm_editorial_sections_nonce']), 'dm_editorial_sections_save')
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

	foreach (dm_cpt_editorial_fields_config() as $key => $field) {
		$raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';

		switch ($field['type']) {
			case 'textarea':
				$value = sanitize_textarea_field((string) $raw);
				break;
			case 'media':
			case 'url':
				$value = esc_url_raw(trim((string) $raw));
				break;
			default:
				$value = sanitize_text_field((string) $raw);
				break;
		}

		if ($value !== '') {
			update_post_meta($post_id, $key, $value);
		} else {
			delete_post_meta($post_id, $key);
		}
	}
}

function dm_cpt_has_editorial_sections($post_id)
{
	$keys = array_diff(array_keys(dm_cpt_editorial_fields_config()), ['_dm_single_hero_image_url']);
	foreach ($keys as $key) {
		if (trim((string) get_post_meta($post_id, $key, true)) !== '') {
			return true;
		}
	}
	return false;
}

function dm_cpt_get_meta_lines($post_id, $key)
{
	$value = (string) get_post_meta($post_id, $key, true);
	if ($value === '') {
		return [];
	}
	$lines = preg_split('/\r\n|\r|\n/', $value);
	$lines = array_map('trim', is_array($lines) ? $lines : []);
	return array_values(array_filter($lines, static function ($line) {
		return $line !== '';
	}));
}

function dm_cpt_render_editorial_sections($post_id, $hero_image_url = '', $hero_image_source = '')
{
	$post_id         = absint($post_id);
	$hero_image_url  = trim((string) $hero_image_url);
	$hero_image_source = trim((string) $hero_image_source);
	$has_hero_media  = ($hero_image_url !== '') || has_post_thumbnail($post_id);
	if ($post_id <= 0 || (! dm_cpt_has_editorial_sections($post_id) && ! $has_hero_media)) {
		return '';
	}

	$fit_title       = trim((string) get_post_meta($post_id, '_dm_editorial_fit_title', true));
	$fit_title_image = trim((string) get_post_meta($post_id, '_dm_editorial_fit_title_image', true));
	$fit_items       = dm_cpt_get_meta_lines($post_id, '_dm_editorial_fit_items');
	$fit_has_copy    = ($fit_title !== '') || ($fit_title_image !== '') || ! empty($fit_items);
	$learn_title     = trim((string) get_post_meta($post_id, '_dm_editorial_learn_title', true));
	$learn_title_image = trim((string) get_post_meta($post_id, '_dm_editorial_learn_title_image', true));
	$learn_intro     = trim((string) get_post_meta($post_id, '_dm_editorial_learn_intro', true));
	$learn_items     = dm_cpt_get_meta_lines($post_id, '_dm_editorial_learn_items');
	$learn_image     = trim((string) get_post_meta($post_id, '_dm_editorial_learn_image', true));
	$learn_cta       = trim((string) get_post_meta($post_id, '_dm_editorial_learn_button_label', true));
	$diff_title      = trim((string) get_post_meta($post_id, '_dm_editorial_diff_title', true));
	$diff_title_image = trim((string) get_post_meta($post_id, '_dm_editorial_diff_title_image', true));
	$diff_items      = dm_cpt_get_meta_lines($post_id, '_dm_editorial_diff_items');
	$diff_image      = trim((string) get_post_meta($post_id, '_dm_editorial_diff_image', true));
	$diff_cta        = trim((string) get_post_meta($post_id, '_dm_editorial_diff_button_label', true));
	$inc_title       = trim((string) get_post_meta($post_id, '_dm_editorial_include_title', true));
	$inc_title_image = trim((string) get_post_meta($post_id, '_dm_editorial_include_title_image', true));
	$inc_items       = dm_cpt_get_meta_lines($post_id, '_dm_editorial_include_items');
	$final_title     = trim((string) get_post_meta($post_id, '_dm_editorial_final_title', true));
	$final_title_image = trim((string) get_post_meta($post_id, '_dm_editorial_final_title_image', true));
	$final_text      = trim((string) get_post_meta($post_id, '_dm_editorial_final_text', true));
	$final_cta       = trim((string) get_post_meta($post_id, '_dm_editorial_final_button_label', true));

	ob_start();
?>
	<div class="dm-editorial">
		<?php if ($fit_has_copy || $has_hero_media) : ?>
			<section class="dm-editorial__section dm-editorial__section--panel">
				<div class="dm-editorial__panel dm-editorial__panel--beige">
					<div class="dm-editorial__grid<?php echo ($has_hero_media && $fit_has_copy) ? '' : ' dm-editorial__grid--single'; ?>">
						<div class="dm-editorial__copy">
							<?php echo dm_cpt_render_editorial_heading($fit_title, $fit_title_image, 'section'); // phpcs:ignore WordPress.Security.EscapeOutput 
							?>
							<?php if (! empty($fit_items)) : ?>
								<ul class="dm-editorial__list dm-editorial__list--stars">
									<?php foreach ($fit_items as $item) : ?>
										<li><?php echo esc_html($item); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
						<?php if ($has_hero_media) : ?>
							<div class="dm-editorial__figure dm-editorial__figure--hero">
								<div class="dm-single__media dm-single__media--inline">
									<div class="dm-single__thumbnail dm-single__thumbnail--inline<?php echo 'product-image' === $hero_image_source ? ' dm-single__thumbnail--catalog-fallback' : ''; ?>">
										<?php if ($hero_image_url !== '') : ?>
											<img src="<?php echo esc_url($hero_image_url); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" loading="lazy" />
										<?php else : ?>
											<?php echo get_the_post_thumbnail($post_id, 'large'); // phpcs:ignore WordPress.Security.EscapeOutput 
											?>
										<?php endif; ?>
									</div>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ($learn_title || $learn_title_image || $learn_intro || ! empty($learn_items) || $learn_image) : ?>
			<section class="dm-editorial__section dm-editorial__section--split">
				<div class="dm-editorial__grid">
					<div class="dm-editorial__copy">
						<?php echo dm_cpt_render_editorial_heading($learn_title, $learn_title_image, 'section'); // phpcs:ignore WordPress.Security.EscapeOutput 
						?>
						<?php if ($learn_intro) : ?>
							<p class="dm-editorial__lead"><?php echo nl2br(esc_html($learn_intro)); ?></p>
						<?php endif; ?>
						<?php if (! empty($learn_items)) : ?>
							<ul class="dm-editorial__list">
								<?php foreach ($learn_items as $item) : ?>
									<li><?php echo esc_html($item); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ($learn_cta) : ?>
							<div class="dm-editorial__cta-row"><?php echo dm_cpt_render_cta($post_id, ['label' => $learn_cta, 'class' => 'dm-btn dm-btn--primary']); // phpcs:ignore WordPress.Security.EscapeOutput 
																?></div>
						<?php endif; ?>
					</div>
					<?php if ($learn_image) : ?>
						<div class="dm-editorial__figure"><img src="<?php echo esc_url($learn_image); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" loading="lazy" /></div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ($diff_title || $diff_title_image || ! empty($diff_items) || $diff_image) : ?>
			<section class="dm-editorial__section dm-editorial__section--split dm-editorial__section--alt">
				<div class="dm-editorial__grid">
					<?php if ($diff_image) : ?>
						<div class="dm-editorial__figure"><img src="<?php echo esc_url($diff_image); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" loading="lazy" /></div>
					<?php endif; ?>
					<div class="dm-editorial__copy">
						<?php echo dm_cpt_render_editorial_heading($diff_title, $diff_title_image, 'section'); // phpcs:ignore WordPress.Security.EscapeOutput 
						?>
						<?php if (! empty($diff_items)) : ?>
							<ul class="dm-editorial__list dm-editorial__list--checks">
								<?php foreach ($diff_items as $item) : ?>
									<li><?php echo esc_html($item); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ($diff_cta) : ?>
							<div class="dm-editorial__cta-row"><?php echo dm_cpt_render_cta($post_id, ['label' => $diff_cta, 'class' => 'dm-btn dm-btn--primary']); // phpcs:ignore WordPress.Security.EscapeOutput 
																?></div>
						<?php endif; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ($inc_title || $inc_title_image || ! empty($inc_items)) : ?>
			<section class="dm-editorial__section dm-editorial__section--panel">
				<div class="dm-editorial__panel dm-editorial__panel--soft">
					<?php echo dm_cpt_render_editorial_heading($inc_title, $inc_title_image, 'section'); // phpcs:ignore WordPress.Security.EscapeOutput 
					?>
					<?php if (! empty($inc_items)) : ?>
						<ul class="dm-editorial__list dm-editorial__list--checks">
							<?php foreach ($inc_items as $item) : ?>
								<li><?php echo esc_html($item); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ($final_title || $final_title_image || $final_text || $final_cta) : ?>
			<section class="dm-editorial__section dm-editorial__section--final">
				<div class="dm-editorial__final">
					<?php echo dm_cpt_render_editorial_heading($final_title, $final_title_image, 'final'); // phpcs:ignore WordPress.Security.EscapeOutput 
					?>
					<?php if ($final_text) : ?>
						<p class="dm-editorial__lead"><?php echo nl2br(esc_html($final_text)); ?></p>
					<?php endif; ?>
					<?php if ($final_cta) : ?>
						<div class="dm-editorial__cta-row"><?php echo dm_cpt_render_cta($post_id, ['label' => $final_cta, 'class' => 'dm-btn dm-btn--primary']); // phpcs:ignore WordPress.Security.EscapeOutput 
															?></div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>
	</div>
<?php
	return ob_get_clean();
}

// =============================================================================
// HELPERS — Escuela permalink puente hacia Tutor LMS
// =============================================================================

/**
 * Devuelve la URL Tutor vinculada a un producto WooCommerce de Escuela.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function dm_get_tutor_url_for_escuela_product($product_id)
{
	$product_id = absint($product_id);
	if ($product_id <= 0) {
		return '';
	}

	$posts = get_posts([
		'post_type'      => 'dm_escuela',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			[
				'key'   => '_dm_wc_product_id',
				'value' => (string) $product_id,
			],
		],
	]);

	if (empty($posts)) {
		return '';
	}

	$tutor_url = trim((string) get_post_meta((int) $posts[0], '_dm_tutor_course_url', true));
	if ($tutor_url === '') {
		return '';
	}

	if (preg_match('#^https?://#i', $tutor_url)) {
		return esc_url_raw($tutor_url);
	}

	return esc_url_raw(home_url('/' . ltrim($tutor_url, '/')));
}

add_action('init', function () {
	add_rewrite_rule(
		'^escuela/(curso|taller|programa)/([^/]+)/?$',
		'index.php?dm_escuela_tipo=$matches[1]&dm_escuela_curso=$matches[2]',
		'top'
	);
});

add_filter('query_vars', function ($vars) {
	$vars[] = 'dm_escuela_tipo';
	$vars[] = 'dm_escuela_curso';
	return $vars;
});

/**
 * Devuelve el segmento de ruta editorial para un producto de Escuela.
 *
 * @param int $product_id Product ID.
 * @return string         curso|taller|programa o cadena vacia si no aplica.
 */
function dm_get_escuela_route_segment_for_product($product_id)
{
	$product_id = absint($product_id);
	if ($product_id <= 0) {
		return '';
	}

	$map = [
		'cursos'    => 'curso',
		'talleres'  => 'taller',
		'programas' => 'programa',
	];

	foreach ($map as $cat_slug => $route_segment) {
		if (has_term($cat_slug, 'product_cat', $product_id)) {
			return $route_segment;
		}
	}

	return '';
}

/**
 * Flush controlado para nuevas rutas de Escuela.
 */
add_action('init', function () {
	$version = '2.0';
	if (get_option('dm_escuela_route_rewrite_version') !== $version) {
		flush_rewrite_rules();
		update_option('dm_escuela_route_rewrite_version', $version);
	}
}, 999);

add_filter('post_type_link', function ($permalink, $post) {
	if (! $post instanceof WP_Post || 'product' !== $post->post_type) {
		return $permalink;
	}

	if (! has_term(['cursos', 'talleres', 'programas'], 'product_cat', $post)) {
		return $permalink;
	}

	$tutor_url = dm_get_tutor_url_for_escuela_product((int) $post->ID);
	if ($tutor_url === '') {
		return $permalink;
	}

	$route_segment = dm_get_escuela_route_segment_for_product((int) $post->ID);
	if ($route_segment === '') {
		return $permalink;
	}

	return home_url('/escuela/' . $route_segment . '/' . $post->post_name . '/');
}, 20, 2);

add_action('template_redirect', function () {
	$course_slug = get_query_var('dm_escuela_curso');
	if (! $course_slug) {
		return;
	}

	$product = get_page_by_path(sanitize_title((string) $course_slug), OBJECT, 'product');
	if (! $product instanceof WP_Post) {
		global $wp_query;
		$wp_query->set_404();
		status_header(404);
		return;
	}

	$requested_segment = sanitize_title((string) get_query_var('dm_escuela_tipo'));
	$canonical_segment = dm_get_escuela_route_segment_for_product((int) $product->ID);
	if ($canonical_segment !== '' && $requested_segment !== $canonical_segment) {
		wp_safe_redirect(home_url('/escuela/' . $canonical_segment . '/' . $product->post_name . '/'), 301);
		exit;
	}

	$tutor_url = dm_get_tutor_url_for_escuela_product((int) $product->ID);
	if ($tutor_url === '') {
		wp_safe_redirect(get_permalink((int) $product->ID), 302);
		exit;
	}

	wp_safe_redirect($tutor_url, 301);
	exit;
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
 * Regla de navegación: imagen, título y CTA "Ver detalles" siempre apuntan
 * al single editorial propio. La URL de Tutor solo se usa post-compra.
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
		$excerpt   = dm_cpt_get_catalog_excerpt($post_id);
		$thumb_html = dm_cpt_get_catalog_image_html($post_id, 'woocommerce_thumbnail');

		// Producto vinculado (para precio junto al título y CTA Woo).
		$product = dm_cpt_get_linked_product($post_id);
		$price_html = '';
		$is_free = false;
		$topic_tags_html = '';

		if ($product) {
			$price = (float) $product->get_price();
			$is_free = ($price <= 0.0); // phpcs:ignore WordPress.PHP.StrictComparisons
			if (! $is_free) {
				$price_html = (string) $product->get_price_html();
			}
			$topic_tags_html = dm_get_product_topic_tags_html($product);
		}

		$html .= '<article class="dm-card">';

		if ($thumb_html) {
			$html .= '<a href="' . esc_url($permalink) . '" class="dm-card__image-link" tabindex="-1" aria-hidden="true">';
			$html .= '<div class="dm-card__thumb">';
			$html .= $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput
			$html .= '</div>';
			$html .= '</a>';
		}

		$html .= '<div class="dm-card__body">';

		// Title row (título + precio a la derecha).
		$html .= '<div class="dm-card__title-row">';
		$html .= '<h3 class="dm-card__title"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3>';

		if ($product) {
			if ($is_free) {
				$html .= '<span class="dm-card__price dm-card__price--free">' . esc_html__('Gratis', 'daniela-child') . '</span>';
			} elseif ($price_html !== '') {
				$html .= '<span class="dm-card__price dm-card__price--paid">' . wp_kses_post($price_html) . '</span>';
			}
		}

		$html .= '</div>'; // .dm-card__title-row

		if ($excerpt) {
			$html .= '<p class="dm-card__excerpt">' . esc_html(wp_trim_words(wp_strip_all_tags($excerpt), 20)) . '</p>';
		}

		if ($topic_tags_html !== '') {
			$html .= $topic_tags_html; // phpcs:ignore WordPress.Security.EscapeOutput
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
