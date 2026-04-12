<?php

/**
 * Customer on-hold order email — Child theme override.
 *
 * Se envía al cliente cuando el pedido pasa al estado "En espera".
 * Mantiene la estructura completa de WooCommerce para que los hooks del
 * child theme (woocommerce_email_styles, woocommerce_email_after_order_table,
 * etc.) se ejecuten correctamente.
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
			/* translators: %1$s: Site title, %2$s: Order number, %3$s: Order link */
			__("Your %1$s order receipt and any important links can be found below. Your order is currently on hold - we'll send you an email when it is ready. In the meantime, you can view your order by visiting the following link:", 'woocommerce'),
			esc_html(get_bloginfo('name', 'display'))
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

/**
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action('woocommerce_email_footer', $email);
