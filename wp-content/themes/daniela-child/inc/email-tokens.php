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

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Devuelve los tokens de diseño extraídos de style.css.
 *
 * @return array<string, string> Mapa token => valor.
 */
function dm_get_email_tokens(): array
{
	$cache_key = 'dm_email_tokens_v2';
	$cached    = get_transient($cache_key);

	if (dm_email_tokens_cache_is_valid($cached)) {
		return $cached;
	}

	$tokens = dm_parse_email_tokens();
	set_transient($cache_key, $tokens, 12 * HOUR_IN_SECONDS);

	return $tokens;
}

/**
 * Verifica que el cache tenga todos los tokens requeridos y ya resueltos.
 *
 * @param mixed $cached Valor leído desde transient.
 * @return bool
 */
function dm_email_tokens_cache_is_valid($cached): bool
{
	if (! is_array($cached) || empty($cached)) {
		return false;
	}

	$required = [
		'color_primary',
		'color_primary_dark',
		'color_accent',
		'color_text',
		'color_text_muted',
		'color_bg',
		'color_bg_card',
		'color_border',
		'radius',
		'shadow',
		'font_heading',
		'font_body',
		'font_button',
		'btn_primary',
		'btn_primary_hover',
	];

	foreach ($required as $key) {
		if (! isset($cached[$key])) {
			return false;
		}

		$value = trim((string) $cached[$key]);
		if ('' === $value) {
			return false;
		}

		if (0 === strpos($value, 'var(')) {
			return false;
		}
	}

	return true;
}

/**
 * Lee style.css y extrae las custom properties de :root.
 *
 * @return array<string, string>
 */
function dm_parse_email_tokens(): array
{
	$style_file = get_stylesheet_directory() . '/style.css';

	if (! file_exists($style_file)) {
		return dm_email_tokens_fallback();
	}

	$css = file_get_contents($style_file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if (false === $css) {
		return dm_email_tokens_fallback();
	}

	// Extrae el bloque :root { … } (primera ocurrencia).
	if (! preg_match('/:root\s*\{([^}]+)\}/s', $css, $root_match)) {
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
	foreach ($matches as $m) {
		$key         = trim($m[1]); // e.g. "color-primary"
		$value       = trim($m[2]); // e.g. "#7c6b8e"
		$raw[$key] = $value;
	}

	$color_primary      = dm_email_resolve_token_value($raw, 'color-primary', '#7c6b8e');
	$color_primary_dark = dm_email_resolve_token_value($raw, 'color-primary-dark', '#5e4f6c');
	$color_accent       = dm_email_resolve_token_value($raw, 'color-accent', '#e8a598');

	// Mapeo a claves cortas y legibles.
	return [
		'color_primary'      => $color_primary,
		'color_primary_dark' => $color_primary_dark,
		'color_accent'       => $color_accent,
		'color_text'         => dm_email_resolve_token_value($raw, 'color-text', '#2d2d2d'),
		'color_text_muted'   => dm_email_resolve_token_value($raw, 'color-text-muted', '#6b6b6b'),
		'color_bg'           => dm_email_resolve_token_value($raw, 'color-bg', '#faf9f7'),
		'color_bg_card'      => dm_email_resolve_token_value($raw, 'color-bg-card', '#ffffff'),
		'color_border'       => dm_email_resolve_token_value($raw, 'color-border', '#e5e0d8'),
		'radius'             => dm_email_resolve_token_value($raw, 'radius', '8px'),
		'shadow'             => dm_email_resolve_token_value($raw, 'shadow', '0 2px 8px rgba(0,0,0,.07)'),
		'font_heading'       => dm_email_resolve_token_value($raw, 'font-heading-family', "'Abril Fatface', serif"),
		'font_body'          => dm_email_resolve_token_value($raw, 'font-body-family', "'Open Sans', sans-serif"),
		'font_button'        => dm_email_resolve_token_value($raw, 'font-button-family', "'Open Sans', sans-serif"),
		'btn_primary'        => dm_email_resolve_token_value($raw, 'btn-primary-bg', $color_primary),
		'btn_primary_hover'  => dm_email_resolve_token_value($raw, 'btn-primary-bg-hover', $color_primary_dark),
	];
}

/**
 * Resuelve un token simple soportando referencias var(--dm-foo).
 *
 * @param array<string, string> $raw     Tokens crudos parseados desde :root.
 * @param string                $key     Clave a resolver (sin prefijo --dm-).
 * @param string                $default Valor por defecto si no existe.
 * @return string
 */
function dm_email_resolve_token_value(array $raw, string $key, string $default): string
{
	$value = isset($raw[$key]) ? trim((string) $raw[$key]) : '';
	if ('' === $value) {
		return $default;
	}

	$max_depth = 6;
	while ($max_depth > 0 && preg_match('/^var\(--dm-([\w-]+)\)$/', $value, $m)) {
		$ref = trim($m[1]);
		if (! isset($raw[$ref])) {
			break;
		}

		$next = trim((string) $raw[$ref]);
		if ('' === $next || $next === $value) {
			break;
		}

		$value = $next;
		$max_depth--;
	}

	if (preg_match('/^var\(--dm-([\w-]+)\)$/', $value)) {
		return $default;
	}

	return $value;
}

/**
 * Valores de fallback hardcodeados (idénticos a las variables del tema)
 * por si el archivo no se puede leer (ej: en tests o rutas no estándar).
 *
 * @return array<string, string>
 */
function dm_email_tokens_fallback(): array
{
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
		'font_heading'       => "'Abril Fatface', serif",
		'font_body'          => "'Open Sans', sans-serif",
		'font_button'        => "'Open Sans', sans-serif",
		'btn_primary'        => '#7c6b8e',
		'btn_primary_hover'  => '#5e4f6c',
	];
}
