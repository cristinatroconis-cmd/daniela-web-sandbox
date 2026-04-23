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

$first_name = trim((string) $order->get_billing_first_name());
?>

<p style="margin:0 0 12px;font-family:'Open Sans',Arial,sans-serif;font-size:16px;line-height:1.7;color:#2d2d2d;">
	<?php echo esc_html($first_name ? sprintf(__('Hola %s,', 'daniela-child'), $first_name) : __('Hola,', 'daniela-child')); ?>
</p>
<p style="margin:0;font-family:'Open Sans',Arial,sans-serif;font-size:15px;line-height:1.8;color:#6b6b6b;">
	<?php esc_html_e('Tu recurso ya esta preparado. Te dejo todo listo para que lo descargues de forma simple y sin pasos de mas.', 'daniela-child'); ?>
</p>

<?php
if (! $sent_to_admin && ! $plain_text) {
	dm_render_cta_block($order);
	dm_render_completed_order_summary($order);
	dm_render_newsletter_block($order);
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action('woocommerce_email_footer', $email);
