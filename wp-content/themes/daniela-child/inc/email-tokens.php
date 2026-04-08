<?php
/**
 * Email Tokens — Extrae variables del sistema de diseño desde style.css
 *
 * Lee las variables CSS de `:root` en style.css y las convierte en un array
 * de valores planos para usar como tokens en los emails de WooCommerce.
 * El resultado se cachea en un transient de 12h para evitar leer el archivo
 * en cada request.
 *
 * Uso:
 *   $tokens = dm_get_email_tokens();
 *   $primary = $tokens['color_primary']; // '#7c6b8e'
 *
 * @package daniela-child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve los tokens de diseño extraídos de style.css.
 *
 * @return array<string, string> Mapa token => valor.
 */
function dm_get_email_tokens(): array {
	$cache_key = 'dm_email_tokens_v1';
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) && ! empty( $cached ) ) {
		return $cached;
	}

	$tokens = dm_parse_email_tokens();
	set_transient( $cache_key, $tokens, 12 * HOUR_IN_SECONDS );

	return $tokens;
}

/**
 * Lee style.css y extrae las custom properties de :root.
 *
 * @return array<string, string>
 */
function dm_parse_email_tokens(): array {
	$style_file = get_stylesheet_directory() . '/style.css';

	if ( ! file_exists( $style_file ) ) {
		return dm_email_tokens_fallback();
	}

	$css = file_get_contents( $style_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if ( false === $css ) {
		return dm_email_tokens_fallback();
	}

	// Extrae el bloque :root { … } (primera ocurrencia).
	if ( ! preg_match( '/:root\s*\{([^}]+)\}/s', $css, $root_match ) ) {
		return dm_email_tokens_fallback();
	}

	$root_block = $root_match[1];

	// Parsea cada --dm-* variable.
	preg_match_all(
		'/--dm-([\w-]+)\s*:\s*([^;\/\n]+?)(?:\s*;|\s*\/\*)/s',
		$root_block,
		$matches,
		PREG_SET_ORDER
	);

	$raw = [];
	foreach ( $matches as $m ) {
		$key         = trim( $m[1] ); // e.g. "color-primary"
		$value       = trim( $m[2] ); // e.g. "#7c6b8e"
		$raw[ $key ] = $value;
	}

	// Mapeo a claves cortas y legibles.
	return [
		'color_primary'      => $raw['color-primary']      ?? '#7c6b8e',
		'color_primary_dark' => $raw['color-primary-dark'] ?? '#5e4f6c',
		'color_accent'       => $raw['color-accent']       ?? '#e8a598',
		'color_text'         => $raw['color-text']         ?? '#2d2d2d',
		'color_text_muted'   => $raw['color-text-muted']   ?? '#6b6b6b',
		'color_bg'           => $raw['color-bg']           ?? '#faf9f7',
		'color_bg_card'      => $raw['color-bg-card']      ?? '#ffffff',
		'color_border'       => $raw['color-border']       ?? '#e5e0d8',
		'radius'             => $raw['radius']             ?? '8px',
		'shadow'             => $raw['shadow']             ?? '0 2px 8px rgba(0,0,0,.07)',
	];
}

/**
 * Valores de fallback hardcodeados (idénticos a las variables del tema)
 * por si el archivo no se puede leer (ej: en tests o rutas no estándar).
 *
 * @return array<string, string>
 */
function dm_email_tokens_fallback(): array {
	return [
		'color_primary'      => '#7c6b8e',
		'color_primary_dark' => '#5e4f6c',
		'color_accent'       => '#e8a598',
		'color_text'         => '#2d2d2d',
		'color_text_muted'   => '#6b6b6b',
		'color_bg'           => '#faf9f7',
		'color_bg_card'      => '#ffffff',
		'color_border'       => '#e5e0d8',
		'radius'             => '8px',
		'shadow'             => '0 2px 8px rgba(0,0,0,.07)',
	];
}
