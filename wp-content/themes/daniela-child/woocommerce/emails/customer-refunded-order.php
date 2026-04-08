<?php
/**
 * Customer refunded order email — Child theme override.
 *
 * Se envía al cliente cuando un pedido es reembolsado total o parcialmente.
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
 * @var WC_Order $order               Objeto pedido.
 * @var bool     $sent_to_admin       Si el email va al admin.
 * @var bool     $plain_text          Si es texto plano.
 * @var WC_Email $email               Objeto email.
 * @var bool     $partial_refund      Si es un reembolso parcial.
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header.
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
	<?php
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
	?>
</p>
<?php if ( ! empty( $partial_refund ) ) : ?>
<p>
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: %s: Site title. */
			__( 'Your %s order has been partially refunded. Your order details are shown below for your reference:', 'woocommerce' ),
			esc_html( get_bloginfo( 'name', 'display' ) )
		)
	);
	?>
</p>
<?php else : ?>
<p>
	<?php
	echo wp_kses_post(
		sprintf(
			/* translators: %s: Site title. */
			__( 'Your %s order has been refunded. Your order details are shown below for your reference:', 'woocommerce' ),
			esc_html( get_bloginfo( 'name', 'display' ) )
		)
	);
	?>
</p>
<?php endif; ?>

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
