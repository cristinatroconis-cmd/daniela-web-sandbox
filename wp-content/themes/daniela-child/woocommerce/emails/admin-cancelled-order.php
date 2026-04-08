<?php
/**
 * Admin cancelled order email — Child theme override.
 *
 * Se envía al admin cuando un pedido es cancelado.
 * Mantiene la estructura completa de WooCommerce para que los hooks
 * de estilos del child theme se ejecuten correctamente.
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

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header.
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: %s: Order link. */
			__( 'The following order has been cancelled. Please check your store to see what action needs to be taken.', 'woocommerce' ),
			esc_html( $order->get_formatted_billing_full_name() )
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
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details.
 * @hooked WC_Emails::email_address() Shows email address.
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action( 'woocommerce_email_footer', $email );
