<?php

/**
 * Customer cancelled order email — Child theme override.
 *
 * Se envía al cliente cuando su pedido es cancelado.
 * Mantiene la estructura completa de WooCommerce para que los hooks del
 * child theme se ejecuten correctamente.
 *
 * Compatible con WooCommerce 8.x / 9.x.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
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
do_action('woocommerce_email_header', $email_heading, $email); ?>

<p>
	<?php
	/* translators: %s: Customer first name */
	printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($order->get_billing_first_name()));
	?>
</p>
<p>
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: %s: Order link. */
			__('Your order has been cancelled. Your order details are shown below for your reference. If you didn\'t cancel this order, please <a href="%s">contact us</a> immediately.', 'woocommerce'),
			esc_url(wc_get_page_permalink('myaccount'))
		)
	);
	?>
</p>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @since 2.5.0
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details.
 * @hooked WC_Emails::email_address() Shows email address.
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/* CTA + Newsletter blocks — injected directly from child theme. */
if (! $sent_to_admin && ! $plain_text) {
	dm_render_cta_block($order, $email);
	dm_render_newsletter_block($order, $email);
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action('woocommerce_email_footer', $email);
