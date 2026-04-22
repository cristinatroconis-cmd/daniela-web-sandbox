<?php

/**
 * WooCommerce Emails — Estética + CTA de descarga directa
 *
 * Aplica el sistema de diseño del child theme a los correos transaccionales
 * de WooCommerce mediante filtros nativos (sin plugins). Cubre:
 *
 *  - Defaults de opciones de email (solo si no están ya configuradas).
 *  - CSS email-safe usando tokens de dm_get_email_tokens().
 *  - Subject/heading personalizados para Processing y Completed.
 *  - Bloque CTA con enlace de descarga/pedido (guest-friendly).
 *
 * @package daniela-child
 */

if (! defined('ABSPATH')) {
	exit;
}

// =============================================================================
// 1) DEFAULTS DE OPCIONES WOO EMAIL (solo si el admin no las ha tocado)
// =============================================================================

add_action('init', 'dm_set_woo_email_defaults');

/**
 * Establece valores por defecto para las opciones visuales de los emails de
 * WooCommerce solo si todavía no existen (respeta lo que el admin haya
 * configurado desde WP Admin → WooCommerce → Ajustes → Correos electrónicos).
 */
function dm_set_woo_email_defaults(): void
{
	$tokens = dm_get_email_tokens();

	$defaults = [
		'woocommerce_email_background_color'      => $tokens['color_bg'],
		'woocommerce_email_body_background_color'  => $tokens['color_bg_card'],
		'woocommerce_email_base_color'             => $tokens['color_primary'],
		'woocommerce_email_text_color'             => $tokens['color_text'],
		'woocommerce_email_footer_text'            => 'Daniela Montes Psicóloga · {site_url}',
	];

	foreach ($defaults as $option => $value) {
		if (null === get_option($option, null)) {
			update_option($option, $value);
		}
	}
}

// =============================================================================
// 2) CSS EMAIL-SAFE (tokens del tema inyectados vía filtro de WooCommerce)
// =============================================================================

add_filter('woocommerce_email_styles', 'dm_woo_email_styles', 20);

/**
 * Añade CSS email-safe al final de los estilos base de WooCommerce.
 * Usa tokens extraídos de style.css para alinear la apariencia al tema.
 *
 * @param  string $css CSS existente generado por WooCommerce.
 * @return string CSS enriquecido.
 */
function dm_woo_email_styles(string $css): string
{
	$t = dm_get_email_tokens();

	$custom = "
/* ── Daniela Montes Psicóloga — email theme ───────────────────────────────── */

/* Wrapper principal */
#wrapper {
	background-color: {$t['color_bg']} !important;
}

/* Contenedor de contenido */
#template_container {
	border-radius: {$t['radius']} !important;
	box-shadow: {$t['shadow']} !important;
	border: 1px solid {$t['color_border']} !important;
}

/* Cabecera */
#template_header {
	background-color: {$t['color_primary']} !important;
	border-radius: {$t['radius']} {$t['radius']} 0 0 !important;
}
#template_header h1,
#template_header h1 a {
	color: #ffffff !important;
	font-family: Georgia, 'Times New Roman', serif !important;
	font-weight: 400 !important;
	letter-spacing: 0.01em !important;
}

/* Cuerpo */
#template_body,
#body_content {
	background-color: {$t['color_bg_card']} !important;
}
#body_content table td {
	color: {$t['color_text']} !important;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
	font-size: 15px !important;
	line-height: 1.6 !important;
}

/* Párrafos de cuerpo */
#body_content_inner p {
	color: {$t['color_text']} !important;
}

/* Títulos de sección */
h2 {
	color: {$t['color_primary_dark']} !important;
	font-family: Georgia, 'Times New Roman', serif !important;
}

/* Tabla de pedido */
.td {
	border-color: {$t['color_border']} !important;
}

/* Pie */
#template_footer {
	border-top: 2px solid {$t['color_accent']} !important;
}
#template_footer td,
#template_footer p {
	color: {$t['color_text_muted']} !important;
	font-size: 12px !important;
}

/* Botones de acción WooCommerce */
.button,
.button a {
	background-color: {$t['color_primary']} !important;
	border-color: {$t['color_primary_dark']} !important;
	color: #ffffff !important;
	border-radius: {$t['radius']} !important;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
	font-size: 14px !important;
	font-weight: 600 !important;
	text-decoration: none !important;
}

/* CTA de descarga DM */
.dm-email-cta {
	margin: 24px 0 !important;
	text-align: center !important;
}
.dm-email-cta__link {
	display: inline-block !important;
	background-color: {$t['color_accent']} !important;
	color: #ffffff !important;
	text-decoration: none !important;
	padding: 14px 28px !important;
	border-radius: {$t['radius']} !important;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
	font-size: 15px !important;
	font-weight: 700 !important;
	letter-spacing: 0.02em !important;
}
.dm-email-cta__note {
	display: block !important;
	margin-top: 10px !important;
	color: {$t['color_text_muted']} !important;
	font-size: 12px !important;
}
/* ─────────────────────────────────────────────────────────────────────────── */
";

	return $css . $custom;
}

// =============================================================================
// 3) SUBJECT + HEADING PERSONALIZADOS
// =============================================================================

/**
 * Subject del email de pedido en proceso.
 *
 * @param  string           $subject Asunto original.
 * @param  WC_Order         $order   Objeto pedido.
 * @param  WC_Email         $email   Objeto email.
 * @return string
 */
add_filter(
	'woocommerce_email_subject_customer_processing_order',
	'dm_email_subject_processing',
	20,
	3
);
function dm_email_subject_processing(string $subject, WC_Order $order, WC_Email $email): string
{ // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	/* translators: %s: order number */
	return sprintf(__('✅ Recibimos tu pedido #%s — ya lo estamos procesando', 'daniela-child'), $order->get_order_number());
}

/**
 * Heading del email de pedido en proceso.
 *
 * @param  string   $heading Encabezado original.
 * @param  WC_Order $order   Objeto pedido.
 * @param  WC_Email $email   Objeto email.
 * @return string
 */
add_filter(
	'woocommerce_email_heading_customer_processing_order',
	'dm_email_heading_processing',
	20,
	3
);
function dm_email_heading_processing(string $heading, WC_Order $order, WC_Email $email): string
{ // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	return __('¡Gracias por tu compra! 🌿', 'daniela-child');
}

/**
 * Subject del email de pedido completado.
 *
 * @param  string   $subject Asunto original.
 * @param  WC_Order $order   Objeto pedido.
 * @param  WC_Email $email   Objeto email.
 * @return string
 */
add_filter(
	'woocommerce_email_subject_customer_completed_order',
	'dm_email_subject_completed',
	20,
	3
);
function dm_email_subject_completed(string $subject, WC_Order $order, WC_Email $email): string
{ // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	/* translators: %s: order number */
	return sprintf(__('🎉 Tu pedido #%s está listo — descarga tu recurso', 'daniela-child'), $order->get_order_number());
}

/**
 * Heading del email de pedido completado.
 *
 * @param  string   $heading Encabezado original.
 * @param  WC_Order $order   Objeto pedido.
 * @param  WC_Email $email   Objeto email.
 * @return string
 */
add_filter(
	'woocommerce_email_heading_customer_completed_order',
	'dm_email_heading_completed',
	20,
	3
);
function dm_email_heading_completed(string $heading, WC_Order $order, WC_Email $email): string
{ // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	return __('¡Tu recurso está listo! 🎉', 'daniela-child');
}

// =============================================================================
// 4) BLOQUE CTA — DESCARGA DIRECTA (guest-friendly)
// =============================================================================

/**
 * Inyecta el bloque CTA de descarga en los emails de Processing y Completed.
 * Se evalúa el tipo de email para mostrar el bloque solo cuando aplica.
 *
 * @param  WC_Order $order         Objeto pedido.
 * @param  bool     $sent_to_admin Si es para admin.
 * @param  bool     $plain_text    Si es texto plano.
 * @param  WC_Email $email         Objeto email.
 */
add_action(
	'woocommerce_email_after_order_table',
	'dm_email_cta_block',
	20,
	4
);
function dm_email_cta_block(WC_Order $order, bool $sent_to_admin, bool $plain_text, WC_Email $email): void
{
	if ($sent_to_admin || $plain_text) {
		return;
	}

	$is_processing = $email instanceof WC_Email_Customer_Processing_Order;
	$is_completed  = $email instanceof WC_Email_Customer_Completed_Order;

	if (! $is_processing && ! $is_completed) {
		return;
	}

	dm_render_email_cta($order, $is_processing ? 'processing' : 'completed');
}

/**
 * Renderiza el bloque CTA con enlace(s) de descarga para el pedido.
 *
 * Prioridad de enlaces (guest-friendly):
 *   1. Links de descarga directa del pedido (wc_get_customer_available_downloads).
 *   2. URL de visualización del pedido (no requiere login en Woo si el order-pay está habilitado).
 *
 * @param  WC_Order $order  Objeto pedido.
 * @param  string   $context 'processing' | 'completed'.
 */
function dm_render_email_cta(WC_Order $order, string $context): void
{
	// Recopilar links de descarga asociados al pedido.
	$download_links = dm_get_order_download_links($order);

	if ('processing' === $context && empty($download_links)) {
		// En procesando, si no hay descargas todavía, no mostramos CTA de descarga.
		return;
	}

	$order_view_url = $order->get_view_order_url();
	$t              = dm_get_email_tokens();
	$cta_title      = (string) get_option('dm_downloads_email_cta_title', __('⬇️ Accede a tu descarga', 'daniela-child'));
	$cta_note       = (string) get_option('dm_downloads_email_cta_note', __('Los enlaces de descarga tienen un límite de usos y tiempo de validez.', 'daniela-child'));
	$button_label   = (string) get_option('dm_freebie_email_button_text', __('Descargar recurso', 'daniela-child'));

?>
	<table cellspacing="0" cellpadding="0" border="0" style="width:100%;background-color:<?php echo esc_attr($t['color_bg']); ?>;border-top:1px solid <?php echo esc_attr($t['color_border']); ?>;margin-top:24px;">
		<tr>
			<td style="padding:24px 48px;text-align:center;">
				<?php if (! empty($download_links)) : ?>
					<p style="margin:0 0 16px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:15px;color:<?php echo esc_attr($t['color_text']); ?>;font-weight:600;">
						<?php echo esc_html($cta_title); ?>
					</p>
					<?php foreach ($download_links as $dl) : ?>
						<div style="margin-bottom:12px;">
							<a href="<?php echo esc_url($dl['url']); ?>"
								style="display:inline-block;background-color:<?php echo esc_attr($t['color_accent']); ?>;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:<?php echo esc_attr($t['radius']); ?>;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:15px;font-weight:700;letter-spacing:0.02em;">
								<?php
								if (false !== strpos($button_label, '%s')) {
									/* translators: %s: product name */
									printf(esc_html($button_label), esc_html($dl['name']));
								} else {
									echo esc_html($button_label);
								}
								?>
							</a>
						</div>
					<?php endforeach; ?>
					<span style="display:block;margin-top:10px;color:<?php echo esc_attr($t['color_text_muted']); ?>;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:12px;">
						<?php echo esc_html($cta_note); ?>
					</span>
				<?php endif; ?>

				<?php if ($order_view_url) : ?>
					<p style="margin:<?php echo empty($download_links) ? '0' : '16px'; ?> 0 0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:13px;color:<?php echo esc_attr($t['color_text_muted']); ?>;">
						<?php esc_html_e('¿Necesitas acceder más tarde?', 'daniela-child'); ?>
						<a href="<?php echo esc_url($order_view_url); ?>"
							style="color:<?php echo esc_attr($t['color_primary']); ?>;text-decoration:underline;">
							<?php esc_html_e('Ver detalles del pedido', 'daniela-child'); ?>
						</a>
					</p>
				<?php endif; ?>
			</td>
		</tr>
	</table>
<?php
}

/**
 * Devuelve los links de descarga directa disponibles para un pedido.
 *
 * Usa WC_Order_Item_Product::get_item_downloads() para obtener las URLs de
 * descarga generadas por WooCommerce, que son guest-friendly (no requieren
 * login; usan la clave de descarga única del pedido).
 *
 * @param  WC_Order $order  Objeto pedido.
 * @return array<int, array{name: string, url: string}> Lista de descargas.
 */
function dm_get_order_download_links(WC_Order $order): array
{
	$links = [];

	foreach ($order->get_items() as $item) {
		if (! $item instanceof WC_Order_Item_Product) {
			continue;
		}

		$product = $item->get_product();
		if (! $product || ! $product->is_downloadable()) {
			continue;
		}

		// get_item_downloads() devuelve las URLs con clave única por pedido;
		// no requieren que el cliente esté logueado.
		$downloads = $item->get_item_downloads();
		if (empty($downloads)) {
			$fallback_url = dm_get_free_product_direct_download_url($product);
			if ($fallback_url) {
				dm_store_best_order_download_link($links, $product, $fallback_url);
			}
			continue;
		}

		foreach ($downloads as $dl) {
			$url = ! empty($dl['download_url']) ? (string) $dl['download_url'] : '';
			if (! dm_is_valid_guest_download_url($url)) {
				$url = dm_get_free_product_direct_download_url($product);
			}

			if (! $url) {
				continue;
			}

			dm_store_best_order_download_link($links, $product, $url);
		}
	}

	return array_values($links);
}

/**
 * Conserva el mejor enlace por producto, priorizando el guest-link válido de Woo.
 *
 * @param array<int|string, array{name: string, url: string, priority: int}> $links
 */
function dm_store_best_order_download_link(array &$links, WC_Product $product, string $url): void
{
	if ($url === '') {
		return;
	}

	$key      = (string) $product->get_id();
	$priority = dm_is_valid_guest_download_url($url) ? 2 : 1;

	if (! isset($links[$key]) || $priority > $links[$key]['priority']) {
		$links[$key] = [
			'name'     => $product->get_name(),
			'url'      => $url,
			'priority' => $priority,
		];
	}
}

/**
 * Valida que la URL guest-friendly de Woo incluya un token `key` usable.
 */
function dm_is_valid_guest_download_url(string $url): bool
{
	if ($url === '') {
		return false;
	}

	$query = wp_parse_url($url, PHP_URL_QUERY);
	if (! is_string($query) || $query === '') {
		return false;
	}

	parse_str($query, $params);

	return ! empty($params['download_file'])
		&& ! empty($params['order'])
		&& ! empty($params['key']);
}

/**
 * Fallback para recursos gratuitos: usa el archivo directo del producto.
 */
function dm_get_free_product_direct_download_url(WC_Product $product): string
{
	$price = $product->get_price();
	if ($price === '' || (float) $price > 0.0 || ! $product->is_downloadable()) {
		return '';
	}

	$downloads = $product->get_downloads();
	if (empty($downloads)) {
		return '';
	}

	$first = reset($downloads);
	if (! $first instanceof WC_Product_Download) {
		return '';
	}

	return (string) $first->get_file();
}

/**
 * Helper: mark last known source for customer_completed_order email.
 *
 * @param int    $order_id Order ID.
 * @param string $source   automatic|manual
 */
function dm_mark_customer_completed_email_source(int $order_id, string $source): void
{
	if ($order_id <= 0) {
		return;
	}

	set_transient('dm_completed_email_source_' . $order_id, $source, 10 * MINUTE_IN_SECONDS);
}

/**
 * Automatic source marker: order reached completed status.
 */
add_action('woocommerce_order_status_completed', function ($order_id): void {
	dm_mark_customer_completed_email_source((int) $order_id, 'automatic');
}, 1);

/**
 * Manual source marker: admin triggered "Resend order emails".
 */
add_action('woocommerce_before_resend_order_emails', function ($order, $email_type): void {
	if ('customer_completed_order' !== (string) $email_type) {
		return;
	}

	if (! $order instanceof WC_Order) {
		return;
	}

	dm_mark_customer_completed_email_source((int) $order->get_id(), 'manual');
}, 10, 2);

/**
 * Marca cuando el email completed se envió correctamente.
 */
add_action('woocommerce_email_sent', function ($sent, $email_id, $email): void {
	if (! $sent || 'customer_completed_order' !== $email_id) {
		return;
	}

	$order = $email instanceof WC_Email ? $email->object : null;
	if (! $order instanceof WC_Order) {
		return;
	}

	$source = get_transient('dm_completed_email_source_' . $order->get_id());
	if (! is_string($source) || $source === '') {
		$source = 'unknown';
	}

	$order->update_meta_data('_dm_customer_completed_email_sent', current_time('mysql'));
	$order->update_meta_data('_dm_customer_completed_email_last_source', $source);
	$order->update_meta_data('_dm_customer_completed_email_last_source_at', current_time('mysql'));
	$order->add_order_note(
		sprintf(
			/* translators: %s: trigger source */
			__('Email customer_completed_order enviado (%s).', 'daniela-child'),
			$source
		)
	);
	$order->save();

	delete_transient('dm_completed_email_source_' . $order->get_id());
}, 20, 3);

/**
 * Reintento controlado para pedidos gratuitos completados si Woo no envió el email.
 */
add_action('woocommerce_order_status_completed', function ($order_id): void {
	$order = wc_get_order($order_id);
	if (! $order instanceof WC_Order) {
		return;
	}

	if ((float) $order->get_total() > 0.0 || ! $order->has_downloadable_item()) {
		return;
	}

	if ($order->get_meta('_dm_customer_completed_email_sent')) {
		return;
	}

	if (! function_exists('WC') || ! WC()->mailer()) {
		return;
	}

	$email = WC()->mailer()->emails['WC_Email_Customer_Completed_Order'] ?? null;
	if (! $email instanceof WC_Email_Customer_Completed_Order) {
		return;
	}

	$email->trigger($order->get_id(), $order);
}, 30);
