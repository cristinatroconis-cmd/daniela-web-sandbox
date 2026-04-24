<?php

/**
 * WooCommerce Emails — Estética + entrega de descargables en completed
 *
 * Aplica el sistema de diseño del child theme a los correos transaccionales
 * de WooCommerce mediante filtros nativos (sin plugins). Cubre:
 *
 *  - Defaults de opciones de email (solo si no están ya configuradas).
 *  - CSS email-safe usando tokens de dm_get_email_tokens().
 *  - Subject/heading personalizados para Customer Completed Order.
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
add_action('init', 'dm_migrate_legacy_download_email_options', 5);

/**
 * Migra opciones legacy `dm_freebie_*` al naming neutral de descargables.
 */
function dm_migrate_legacy_download_email_options(): void
{
	$legacy_value = get_option('dm_freebie_email_button_text', null);
	$new_value    = get_option('dm_downloads_email_button_text', null);

	if (null === $new_value && null !== $legacy_value) {
		update_option('dm_downloads_email_button_text', $legacy_value);
	}

	if (null !== $legacy_value) {
		delete_option('dm_freebie_email_button_text');
	}
}

/**
 * Migra opciones legacy de newsletter en emails.
 */
function dm_migrate_legacy_newsletter_email_options(): void
{
	$legacy_enabled = get_option('dm_email_newsletter_enabled', null);
	$new_enabled    = get_option('dm_newsletter_email_enabled', null);

	if (null === $new_enabled && null !== $legacy_enabled) {
		update_option('dm_newsletter_email_enabled', $legacy_enabled);
	}

	if (null !== $legacy_enabled) {
		delete_option('dm_email_newsletter_enabled');
	}
}

add_action('init', 'dm_migrate_legacy_newsletter_email_options', 5);

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
		// Newsletter defaults (section 5).
		'dm_newsletter_email_enabled'              => '1',
		'dm_newsletter_email_title'                => __('¿Quieres recibir más recursos?', 'daniela-child'),
		'dm_newsletter_email_description'          => __('Suscríbete a mi newsletter y recibe actualizaciones, tips exclusivos y nuevos recursos directamente en tu inbox.', 'daniela-child'),
		'dm_newsletter_email_button_text'          => __('Suscribirme', 'daniela-child'),
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
	background-color: {$t['color_bg_card']} !important;
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
	color: {$t['color_text']} !important;
	font-family: {$t['font_heading']} !important;
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
	font-family: {$t['font_body']} !important;
	font-size: 15px !important;
	line-height: 1.6 !important;
}

/* Párrafos de cuerpo */
#body_content_inner > p {
	color: {$t['color_text_muted']} !important;
}

/* Títulos de sección */
h2 {
	color: {$t['color_text_muted']} !important;
	font-family: {$t['font_heading']} !important;
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
	background-color: {$t['btn_primary']} !important;
	border-color: {$t['btn_primary']} !important;
	color: #ffffff !important;
	border-radius: {$t['radius']} !important;
	font-family: {$t['font_button']} !important;
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
	background-color: {$t['btn_primary']} !important;
	border: 1px solid {$t['btn_primary']} !important;
	color: #ffffff !important;
	text-decoration: none !important;
	padding: 14px 28px !important;
	border-radius: {$t['radius']} !important;
	font-family: {$t['font_button']} !important;
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

/* Newsletter block styles */
.dm-email-newsletter {
	margin: 24px 0 !important;
	text-align: center !important;
	background-color: {$t['color_bg_card']} !important;
	border: 1px solid {$t['color_border']} !important;
	box-shadow: {$t['shadow']} !important;
	border-radius: {$t['radius']} !important;
	padding: 24px 48px !important;
}
.dm-email-newsletter__title {
	margin: 0 0 12px !important;
	color: {$t['color_text_muted']} !important;
	font-family: {$t['font_heading']} !important;
	font-size: 18px !important;
	font-weight: 400 !important;
	letter-spacing: 0.01em !important;
}
.dm-email-newsletter__description {
	margin: 0 0 16px !important;
	color: {$t['color_text_muted']} !important;
	font-family: {$t['font_body']} !important;
	font-size: 14px !important;
	line-height: 1.5 !important;
}
.dm-email-newsletter__link {
	display: inline-block !important;
	background-color: {$t['btn_primary']} !important;
	border: 1px solid {$t['btn_primary']} !important;
	color: #ffffff !important;
	text-decoration: none !important;
	padding: 12px 28px !important;
	border-radius: {$t['radius']} !important;
	font-family: {$t['font_button']} !important;
	font-size: 14px !important;
	font-weight: 700 !important;
	letter-spacing: 0.02em !important;
}
/* ─────────────────────────────────────────────────────────────────────────── */
";

	return $css . $custom;
}

/**
 * Lee una opción DM desde el email activo y hace fallback a la opción global.
 *
 * @param WC_Email|null $email              Objeto email activo.
 * @param string        $key                Clave de la opción del email.
 * @param string        $legacy_option_name Opción global legacy.
 * @param string        $default            Valor por defecto.
 * @return string
 */
function dm_get_email_option_value(?WC_Email $email, string $key, string $legacy_option_name, string $default = ''): string
{
	if ($email instanceof WC_Email) {
		$value = $email->get_option($key, null);
		if (null !== $value) {
			return is_scalar($value) ? (string) $value : '';
		}
	}

	return (string) get_option($legacy_option_name, $default);
}

/**
 * Lee una opción booleana DM desde el email activo y hace fallback a la opción global.
 *
 * @param WC_Email|null $email              Objeto email activo.
 * @param string        $key                Clave de la opción del email.
 * @param string        $legacy_option_name Opción global legacy.
 * @param bool          $default            Valor por defecto.
 * @return bool
 */
function dm_get_email_option_bool(?WC_Email $email, string $key, string $legacy_option_name, bool $default = true): bool
{
	if ($email instanceof WC_Email) {
		$value = $email->get_option($key, '');
		if ($value !== '') {
			return 'yes' === $value || '1' === $value;
		}
	}

	$legacy_value = get_option($legacy_option_name, $default ? 'yes' : 'no');

	return 'yes' === $legacy_value || '1' === $legacy_value || true === $legacy_value;
}

// =============================================================================
// 4) BLOQUE CTA — DESCARGA DIRECTA (guest-friendly)
// =============================================================================

/**
 * Renderiza el bloque CTA con enlace(s) de descarga para el pedido.
 *
 * Prioridad de enlaces (guest-friendly):
 *   1. Links de descarga directa del pedido (wc_get_customer_available_downloads).
 *   2. URL de visualización del pedido (no requiere login en Woo si el order-pay está habilitado).
 *
 * @param  WC_Order $order  Objeto pedido.
 */
function dm_render_cta_block(WC_Order $order, ?WC_Email $email = null): void
{
	// Recopilar links de descarga asociados al pedido.
	$download_links = dm_get_order_download_links($order);
	if (empty($download_links)) {
		return;
	}

	$t              = dm_get_email_tokens();
	$cta_note       = dm_get_email_option_value($email, 'dm_downloads_email_cta_note', 'dm_downloads_email_cta_note', __('Los enlaces de descarga tienen un límite de usos y tiempo de validez.', 'daniela-child'));
	$button_label   = dm_get_email_option_value($email, 'dm_downloads_email_button_text', 'dm_downloads_email_button_text', __('Descargar recurso', 'daniela-child'));

?>
	<table cellspacing="0" cellpadding="0" border="0" style="width:100%;margin:18px 0 0;">
		<tr>
			<td style="padding:0;">
				<div style="text-align:center;">
					<?php foreach ($download_links as $dl) : ?>
						<div style="margin-bottom:10px;">
							<a href="<?php echo esc_url($dl['url']); ?>"
								style="display:inline-block;background-color:<?php echo esc_attr($t['btn_primary']); ?>;border:1px solid <?php echo esc_attr($t['btn_primary']); ?>;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:999px;font-family:<?php echo esc_attr($t['font_button']); ?>;font-size:14px;font-weight:600;line-height:1.2;">
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
					<?php if ($cta_note !== '') : ?>
						<p style="margin:8px 0 0;color:<?php echo esc_attr($t['color_text_muted']); ?>;font-family:<?php echo esc_attr($t['font_body']); ?>;font-size:12px;line-height:1.5;">
							<?php echo esc_html($cta_note); ?>
						</p>
					<?php endif; ?>
				</div>
			</td>
		</tr>
	</table>
<?php
}

/**
 * Renderiza un resumen editorial del pedido completado sin tablas de Woo.
 *
 * @param WC_Order $order Objeto pedido.
 */
function dm_render_completed_order_summary(WC_Order $order): void
{
	$t     = dm_get_email_tokens();
	$items = $order->get_items();

	if (empty($items)) {
		return;
	}

	$purchase_date = $order->get_date_created();
?>
	<table cellspacing="0" cellpadding="0" border="0" style="width:100%;margin-top:20px;">
		<tr>
			<td style="padding:0;">
				<table cellspacing="0" cellpadding="0" border="0" style="width:100%;background-color:#ffffff;border:1px solid <?php echo esc_attr($t['color_border']); ?>;border-radius:<?php echo esc_attr($t['radius']); ?>;overflow:hidden;">
					<tr>
						<td style="padding:24px 32px;">
							<p style="margin:0 0 6px;font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.2;color:<?php echo esc_attr($t['color_primary']); ?>;font-weight:400;">
								<?php esc_html_e('Tu compra incluye', 'daniela-child'); ?>
							</p>
							<?php if ($purchase_date) : ?>
								<p style="margin:0 0 18px;font-family:'Open Sans',Arial,sans-serif;font-size:13px;line-height:1.5;color:<?php echo esc_attr($t['color_text_muted']); ?>;">
									<?php echo esc_html(wp_date('j \d\e F, Y', $purchase_date->getTimestamp())); ?>
								</p>
							<?php endif; ?>
							<?php foreach ($items as $item) : ?>
								<?php if (! $item instanceof WC_Order_Item_Product) {
									continue;
								} ?>
								<div style="padding:14px 16px;margin-bottom:12px;background-color:#fbf7f2;border:1px solid rgba(124,107,142,0.14);border-radius:<?php echo esc_attr($t['radius']); ?>;">
									<p style="margin:0;font-family:'Open Sans',Arial,sans-serif;font-size:15px;line-height:1.5;color:<?php echo esc_attr($t['color_text']); ?>;font-weight:600;">
										<?php echo esc_html($item->get_name()); ?>
									</p>
								</div>
							<?php endforeach; ?>
							<p style="margin:4px 0 0;font-family:'Open Sans',Arial,sans-serif;font-size:13px;line-height:1.6;color:<?php echo esc_attr($t['color_text_muted']); ?>;">
								<?php esc_html_e('Preparado para una experiencia mas limpia, simple y facil de descargar.', 'daniela-child'); ?>
							</p>
						</td>
					</tr>
				</table>
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

// =============================================================================
// 5) NEWSLETTER BLOCK — INYECTABLE EN TODOS LOS EMAILS DE CLIENTE
// =============================================================================

/**
 * Renderiza el bloque de newsletter en los emails de cliente.
 * Ofrece suscripción con link directo a MailerLite (o formulario embebido).
 *
 * @param WC_Order $order Objeto pedido.
 */
function dm_render_newsletter_block(WC_Order $order, ?WC_Email $email = null): void
{
	$t = dm_get_email_tokens();

	$is_enabled = dm_get_email_option_bool($email, 'dm_newsletter_email_enabled', 'dm_newsletter_email_enabled', true);
	if (! $is_enabled) {
		return;
	}

	$title       = dm_get_email_option_value($email, 'dm_newsletter_email_title', 'dm_newsletter_email_title', __('¿Quieres recibir más recursos?', 'daniela-child'));
	$description = dm_get_email_option_value($email, 'dm_newsletter_email_description', 'dm_newsletter_email_description', __('Suscríbete a mi newsletter y recibe actualizaciones, tips exclusivos y nuevos recursos directamente en tu inbox.', 'daniela-child'));
	$button_text = dm_get_email_option_value($email, 'dm_newsletter_email_button_text', 'dm_newsletter_email_button_text', __('Suscribirme', 'daniela-child'));
	$link_url    = function_exists('dm_newsletter_get_email_subscribe_url')
		? dm_newsletter_get_email_subscribe_url($order)
		: home_url('#dm-newsletter');

?>
	<table cellspacing="0" cellpadding="0" border="0" style="width:100%;margin-top:24px;">
		<tr>
			<td style="padding:0;text-align:center;">
				<table cellspacing="0" cellpadding="0" border="0" style="width:100%;background:<?php echo esc_attr($t['color_bg_card']); ?>;border:1px solid <?php echo esc_attr($t['color_border']); ?>;border-radius:<?php echo esc_attr($t['radius']); ?>;box-shadow:<?php echo esc_attr($t['shadow']); ?>;overflow:hidden;margin:0 auto;">
					<tr>
						<td style="padding:24px 48px;text-align:center;">
							<h2 style="margin:0 0 12px 0;color:<?php echo esc_attr($t['color_text_muted']); ?>;font-family:<?php echo esc_attr($t['font_heading']); ?>;font-size:18px;font-weight:400;letter-spacing:0.01em;">
								<?php echo wp_kses_post($title); ?>
							</h2>
							<p style="margin:0 0 16px 0;color:<?php echo esc_attr($t['color_text_muted']); ?>;font-family:<?php echo esc_attr($t['font_body']); ?>;font-size:14px;line-height:1.5;">
								<?php echo wp_kses_post($description); ?>
							</p>
							<?php if (! empty($link_url)) : ?>
								<a href="<?php echo esc_url($link_url); ?>"
									style="display:inline-block;background-color:<?php echo esc_attr($t['btn_primary']); ?>;border:1px solid <?php echo esc_attr($t['btn_primary']); ?>;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:<?php echo esc_attr($t['radius']); ?>;font-family:<?php echo esc_attr($t['font_button']); ?>;font-size:14px;font-weight:700;letter-spacing:0.02em;">
									<?php echo esc_html($button_text); ?>
								</a>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<?php
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
