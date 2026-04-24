<?php

/**
 * Customer completed order email — Child theme override.
 *
 * Se envía al cliente cuando el pedido pasa al estado "Completado".
 * Mantiene la estructura completa de WooCommerce para que los hooks del
 * child theme (woocommerce_email_styles, woocommerce_email_after_order_table,
 * etc.) se ejecuten correctamente.
 *
 * Compatible con WooCommerce 8.x / 9.x.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.5.2
 *
 * Variables disponibles en este template (inyectadas por WC_Email):
 * @var WC_Order $order          Objeto pedido.
 * @var bool     $sent_to_admin  Si el email va al admin.
 * @var bool     $plain_text     Si es texto plano.
 * @var WC_Email $email          Objeto email.
 */

defined('ABSPATH') || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header.
 */
do_action('woocommerce_email_header', $email_heading, $email);

if (! $sent_to_admin && ! $plain_text) {
	if (! empty($additional_content)) {
		echo wp_kses_post(wpautop(wptexturize($additional_content)));
	}

	dm_render_cta_block($order, $email);

	dm_render_newsletter_block($order, $email);
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action('woocommerce_email_footer', $email);
