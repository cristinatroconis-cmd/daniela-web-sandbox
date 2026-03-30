<?php

/**
 * Sync WooCommerce product_tag → dm_tema on CPT save.
 *
 * WooCommerce (product_tag) es la FUENTE DE VERDAD para los tags de marketing.
 * Al guardar un post de tipo dm_recurso, dm_escuela o dm_servicio que tenga
 * _dm_wc_product_id válido, se copian los product_tag del producto vinculado
 * a la taxonomía dm_tema del CPT (máximo 3, ordenados por term_id ASC).
 *
 * Criterio de ordenación: term_id ASC (orden de creación; estable y predecible).
 *
 * Reglas de seguridad:
 * - No actúa en autosave ni en revisiones.
 * - No actúa si WooCommerce no está activo.
 * - No actúa si no hay producto vinculado.
 * - Previene loops eliminando/re-añadiendo la acción antes/después de
 *   wp_set_object_terms().
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
	exit;
}

add_action('save_post', 'dm_sync_wc_tags_to_dm_tema', 20);

/**
 * Copia los product_tag del producto WC vinculado a dm_tema del CPT.
 *
 * @param int $post_id ID del post que se está guardando.
 */
function dm_sync_wc_tags_to_dm_tema($post_id)
{
	// --- Seguridad básica ---
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (wp_is_post_revision($post_id)) {
		return;
	}

	// Solo para los CPTs que gestionamos.
	$cpt_types = ['dm_recurso', 'dm_escuela', 'dm_servicio'];
	if (! in_array(get_post_type($post_id), $cpt_types, true)) {
		return;
	}

	// WooCommerce debe estar activo.
	if (! function_exists('wc_get_product')) {
		return;
	}

	// Debe haber un producto vinculado.
	$product_id = (int) get_post_meta($post_id, '_dm_wc_product_id', true);
	if ($product_id <= 0) {
		return;
	}

	$product = wc_get_product($product_id);
	if (! $product instanceof WC_Product) {
		return;
	}

	// Obtiene product_tag del producto ordenados por term_id ASC (criterio estable).
	$wc_tags = wp_get_object_terms(
		$product_id,
		'product_tag',
		[
			'orderby' => 'term_id',
			'order'   => 'ASC',
			'fields'  => 'slugs',
		]
	);

	if (is_wp_error($wc_tags)) {
		return;
	}

	// Limita a 3 tags (máximo por requisito).
	$wc_tags = array_slice($wc_tags, 0, 3);

	// Resuelve slugs a term_ids en dm_tema (crea el término si no existe).
	$term_ids = [];
	foreach ($wc_tags as $slug) {
		$slug = sanitize_title($slug);
		$term = get_term_by('slug', $slug, 'dm_tema');
		if (! $term) {
			$result = wp_insert_term(
				$slug,
				'dm_tema',
				[
					'slug' => $slug,
					'name' => $slug, // El admin puede cambiar el name después.
				]
			);
			if (! is_wp_error($result)) {
				$term_ids[] = (int) $result['term_id'];
			}
		} else {
			$term_ids[] = (int) $term->term_id;
		}
	}

	// Asigna los términos. Se desconecta el hook para evitar loops.
	remove_action('save_post', 'dm_sync_wc_tags_to_dm_tema', 20);
	wp_set_object_terms($post_id, $term_ids, 'dm_tema', false);
	add_action('save_post', 'dm_sync_wc_tags_to_dm_tema', 20);
}
