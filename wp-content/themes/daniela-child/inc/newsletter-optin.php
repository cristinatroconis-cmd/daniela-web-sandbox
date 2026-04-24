<?php

/**
 * Checkout Newsletter Opt-In
 *
 * Adds a GDPR-compliant, NOT pre-checked consent checkbox to the WooCommerce
 * checkout page and stores the result in order meta `_dm_newsletter_optin`.
 *
 * Integration strategy:
 *   1. Checks if "MailerLite - WooCommerce integration" plugin provides the
 *      `mailerlite_woocommerce_subscribe_to_group` hook/filter and passes the
 *      opt-in status through it (zero duplication of plugin logic).
 *   2. If the plugin hook is not available OR the API-fallback feature flag is
 *      enabled in DM settings, falls back to a minimal direct MailerLite API
 *      call (Groups v1 API) gated behind WP_DEBUG-aware error_log.
 *
 * @package daniela-child
 */

if (! defined('ABSPATH')) {
	exit;
}

// ---------------------------------------------------------------------------
// 1. Add consent checkbox to checkout
// ---------------------------------------------------------------------------

add_action('woocommerce_review_order_before_submit', 'dm_newsletter_checkout_field');
add_action('woocommerce_checkout_after_terms_and_conditions', 'dm_newsletter_checkout_field', 5);

/**
 * Render the newsletter consent checkbox.
 * Placed just before the order button; NOT pre-checked.
 */
function dm_newsletter_checkout_field()
{
	static $rendered = false;

	if ($rendered) {
		return;
	}

	$rendered = true;

	$label = get_option(
		'dm_newsletter_optin_label',
		__('Acepto recibir recursos y novedades de Daniela Montes Psicóloga por email. Puedo darme de baja en cualquier momento.', 'daniela-child')
	);

	woocommerce_form_field(
		'dm_newsletter_optin',
		array(
			'type'     => 'checkbox',
			'class'    => array('form-row-wide', 'dm-newsletter-optin'),
			'label'    => wp_kses_post($label),
			'required' => false,
			'default'  => 0, // NOT pre-checked
		),
		// Intentionally pass 0 so the checkbox is never pre-filled.
		0
	);
}

// ---------------------------------------------------------------------------
// 2. Validate & store consent in order meta
// ---------------------------------------------------------------------------

add_action('woocommerce_checkout_order_created', 'dm_newsletter_save_optin_meta');

/**
 * Persist the opt-in choice to order meta.
 *
 * @param WC_Order $order Newly created order object.
 */
function dm_newsletter_save_optin_meta($order)
{
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$optin = isset($_POST['dm_newsletter_optin']) && '1' === sanitize_key($_POST['dm_newsletter_optin'])
		? 'yes'
		: 'no';

	$order->update_meta_data('_dm_newsletter_optin', $optin);
	$order->save();
}

// ---------------------------------------------------------------------------
// 3. Subscribe on payment (processing / completed)
// ---------------------------------------------------------------------------

add_action('woocommerce_order_status_processing', 'dm_newsletter_maybe_subscribe', 10, 2);
add_action('woocommerce_order_status_completed', 'dm_newsletter_maybe_subscribe', 10, 2);
add_action('template_redirect', 'dm_newsletter_email_subscribe_endpoint');

/**
 * Trigger the MailerLite subscription when an order is paid.
 * Respects consent: only subscribes if meta = 'yes'.
 *
 * @param int      $order_id Order ID.
 * @param WC_Order $order    Order object.
 */
function dm_newsletter_maybe_subscribe($order_id, $order)
{
	$optin = $order->get_meta('_dm_newsletter_optin', true);

	if ('yes' !== $optin) {
		return; // No consent — never subscribe.
	}

	$customer_email = $order->get_billing_email();
	$first_name     = $order->get_billing_first_name();
	$last_name      = $order->get_billing_last_name();

	if (empty($customer_email)) {
		return;
	}

	dm_newsletter_subscribe_contact($customer_email, $first_name, $last_name, (int) $order_id, $order);
}

/**
 * Suscribe un contacto en MailerLite usando la integración disponible.
 *
 * @param string   $customer_email Email del cliente.
 * @param string   $first_name     Nombre.
 * @param string   $last_name      Apellido.
 * @param int      $order_id       ID del pedido.
 * @param WC_Order $order          Pedido.
 * @return bool
 */
function dm_newsletter_subscribe_contact(string $customer_email, string $first_name, string $last_name, int $order_id, WC_Order $order): bool
{
	if ($customer_email === '') {
		return false;
	}

	// --- Strategy 1: delegate to MailerLite WooCommerce plugin if available ---
	// The official plugin fires `mailerlite_woocommerce_after_subscribe` and
	// exposes `mailerlite_woocommerce_optin` filter; but the most reliable hook
	// to pass consent is to let it run naturally — its checkbox has its own name.
	// Since we have our OWN checkbox, we integrate via the "manual subscribe"
	// action the plugin provides (if present).
	if (has_action('mailerlite_woocommerce_subscribe')) {
		/**
		 * Trigger the official plugin's subscribe action.
		 * Expected signature (from plugin source): ($email, $first_name, $last_name, $order_id)
		 */
		do_action('mailerlite_woocommerce_subscribe', $customer_email, $first_name, $last_name, $order_id);

		dm_newsletter_debug_log(
			sprintf('DM Newsletter: triggered mailerlite_woocommerce_subscribe for order %d (%s)', $order_id, $customer_email)
		);
		return true;
	}

	// --- Strategy 2: API fallback (only if enabled in DM settings) ---
	$fallback_enabled = (bool) get_option('dm_mailerlite_fallback_enabled', false);
	if (! $fallback_enabled) {
		dm_newsletter_debug_log(
			sprintf('DM Newsletter: MailerLite plugin hook not found and API fallback disabled. Order %d not subscribed.', $order_id)
		);
		return false;
	}

	$api_key  = get_option('dm_mailerlite_api_key', '');
	$group_id = dm_newsletter_resolve_group_id($order);

	if (empty($api_key)) {
		dm_newsletter_debug_log('DM Newsletter: API fallback enabled but no API key configured.');
		return false;
	}

	return dm_newsletter_api_subscribe($customer_email, $first_name, $last_name, $api_key, $group_id, $order);
}

// ---------------------------------------------------------------------------
// 4. Minimal MailerLite API integration (Groups v1)
// ---------------------------------------------------------------------------

/**
 * Subscribe a customer via MailerLite Groups API v1.
 *
 * @param string   $email      Customer email.
 * @param string   $first_name First name.
 * @param string   $last_name  Last name.
 * @param string   $api_key    MailerLite API key.
 * @param string   $group_id   MailerLite group ID.
 * @param WC_Order $order      Order object (used for tag derivation).
 */
function dm_newsletter_api_subscribe($email, $first_name, $last_name, $api_key, $group_id, WC_Order $order)
{
	if (empty($group_id)) {
		dm_newsletter_debug_log('DM Newsletter: No group ID configured; skipping API call.');
		return false;
	}

	$tags = dm_newsletter_derive_tags($order);

	$payload = array(
		'email'  => $email,
		'fields' => array(
			'name'     => $first_name . ($last_name ? ' ' . $last_name : ''),
			'last_name' => $last_name,
		),
		'resubscribe' => true, // Respect double opt-in if enabled in MailerLite account settings.
	);

	if (! empty($tags)) {
		$payload['groups'] = $tags; // Tag IDs mapped from settings.
	}

	$endpoint = 'https://api.mailerlite.com/api/v2/groups/' . rawurlencode($group_id) . '/subscribers';

	$response = wp_remote_post(
		$endpoint,
		array(
			'timeout' => 10,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-MailerLite-ApiKey' => $api_key,
			),
			'body'    => wp_json_encode($payload),
		)
	);

	if (is_wp_error($response)) {
		dm_newsletter_debug_log(
			sprintf('DM Newsletter: API error for %s — %s', $email, $response->get_error_message())
		);
		return false;
	}

	$code = wp_remote_retrieve_response_code($response);
	if ($code >= 200 && $code < 300) {
		dm_newsletter_debug_log(
			sprintf('DM Newsletter: subscribed %s to group %s (order %d)', $email, $group_id, $order->get_id())
		);
		return true;
	} else {
		dm_newsletter_debug_log(
			sprintf(
				'DM Newsletter: unexpected API response %d for %s — %s',
				$code,
				$email,
				wp_remote_retrieve_body($response)
			)
		);
		return false;
	}
}

/**
 * Genera token firmado para suscripción one-click desde email.
 */
function dm_newsletter_email_subscribe_token(WC_Order $order): string
{
	$payload = $order->get_id() . '|' . $order->get_order_key() . '|' . strtolower((string) $order->get_billing_email());

	return hash_hmac('sha256', $payload, wp_salt('auth'));
}

/**
 * URL one-click para suscribirse al newsletter desde el email.
 */
function dm_newsletter_get_email_subscribe_url(WC_Order $order): string
{
	return add_query_arg(
		array(
			'dm_nl_subscribe' => '1',
			'order_id'        => (string) $order->get_id(),
			'token'           => dm_newsletter_email_subscribe_token($order),
		),
		home_url('/')
	);
}

/**
 * Endpoint one-click: suscribe y muestra mensaje de resultado.
 */
function dm_newsletter_email_subscribe_endpoint(): void
{
	if (! isset($_GET['dm_nl_subscribe']) || '1' !== (string) $_GET['dm_nl_subscribe']) {
		return;
	}

	$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
	$token    = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';

	if ($order_id <= 0 || $token === '') {
		dm_newsletter_render_subscribe_result(false);
	}

	$order = wc_get_order($order_id);
	if (! $order instanceof WC_Order) {
		dm_newsletter_render_subscribe_result(false);
	}

	$expected = dm_newsletter_email_subscribe_token($order);
	if (! hash_equals($expected, $token)) {
		dm_newsletter_render_subscribe_result(false);
	}

	$email = (string) $order->get_billing_email();
	$name  = (string) $order->get_billing_first_name();
	$last  = (string) $order->get_billing_last_name();

	$ok = dm_newsletter_subscribe_contact($email, $name, $last, $order_id, $order);
	dm_newsletter_render_subscribe_result($ok);
}

/**
 * Renderiza respuesta simple para clic de suscripción desde email.
 */
function dm_newsletter_render_subscribe_result(bool $success): void
{
	$status_text = $success
		? __('Suscripción realizada correctamente. Ya estás dentro del newsletter.', 'daniela-child')
		: __('No pudimos procesar tu suscripción en este momento. Intenta nuevamente más tarde.', 'daniela-child');

	$title = $success
		? __('Suscripción confirmada', 'daniela-child')
		: __('Error de suscripción', 'daniela-child');

	wp_die(
		'<div style="max-width:560px;margin:48px auto;padding:24px;font-family:Arial,sans-serif;line-height:1.6;color:#2d2d2d;border:1px solid #ddd;border-radius:10px;text-align:center;">'
			. '<h1 style="margin:0 0 10px;font-size:24px;">' . esc_html($title) . '</h1>'
			. '<p style="margin:0;font-size:14px;color:#5c5c5c;">' . esc_html($status_text) . '</p>'
			. '</div>',
		esc_html($title),
		array('response' => $success ? 200 : 400)
	);
}

// ---------------------------------------------------------------------------
// 5. Tag derivation from cart / order
// ---------------------------------------------------------------------------

/**
 * Derive MailerLite tag group IDs from order line items.
 *
 * Checks product categories to assign:
 *   - 'buyer' (always)
 *   - 'resource-buyer' (if order has products from recursos)
 *   - 'course-buyer'   (if order has products from cursos or talleres)
 *
 * Tag group IDs are read from DM settings (can be left empty).
 *
 * @param WC_Order $order Order object.
 * @return array          Array of group IDs to tag the subscriber with.
 */
function dm_newsletter_derive_tags(WC_Order $order)
{
	$tag_ids = array();

	$buyer_tag_id    = get_option('dm_mailerlite_tag_buyer', '');
	$resource_tag_id = get_option('dm_mailerlite_tag_resource_buyer', '');
	$course_tag_id   = get_option('dm_mailerlite_tag_course_buyer', '');

	if (! empty($buyer_tag_id)) {
		$tag_ids[] = $buyer_tag_id;
	}

	$has_resource = false;
	$has_course   = false;

	foreach ($order->get_items() as $item) {
		$product_id = $item->get_product_id();

		if (has_term(array('recursos'), 'product_cat', $product_id)) {
			$has_resource = true;
		}

		if (has_term(array('cursos', 'talleres'), 'product_cat', $product_id)) {
			$has_course = true;
		}
	}

	if ($has_resource && ! empty($resource_tag_id)) {
		$tag_ids[] = $resource_tag_id;
	}

	if ($has_course && ! empty($course_tag_id)) {
		$tag_ids[] = $course_tag_id;
	}

	return array_filter($tag_ids);
}

/**
 * Resolve the MailerLite group ID to use for this order.
 * Currently always returns the default group, but can be extended.
 *
 * @param WC_Order $order Order object.
 * @return string         Group ID string.
 */
function dm_newsletter_resolve_group_id(WC_Order $order)
{
	return get_option('dm_mailerlite_group_id', '');
}

// ---------------------------------------------------------------------------
// 6. Debug logging helper
// ---------------------------------------------------------------------------

/**
 * Log a message via error_log only when WP_DEBUG is true.
 *
 * @param string $message Log message.
 */
function dm_newsletter_debug_log($message)
{
	if (defined('WP_DEBUG') && WP_DEBUG) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log($message);
	}
}
