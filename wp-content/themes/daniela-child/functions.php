<?php
/**
 * Daniela Child (Shoptimizer) - Functions
 *
 * Reglas:
 * - No romper lo existente.
 * - Mejoras progresivas y modulares.
 * - Evitar dependencia nueva de Elementor.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encola el CSS del tema hijo.
 * (El CSS del tema padre Shoptimizer ya se carga por su cuenta.)
 */
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'daniela-child-style',
        get_stylesheet_uri(),
        array(),
        '0.1.0'
    );
}, 20);

// =============================================================================
// CHECKOUT — MINIMAL FIELDS FOR FREE DIGITAL RESOURCES
// =============================================================================

/**
 * For free-only carts (total = 0) that contain only virtual/downloadable items,
 * remove address/phone fields and keep only name + email.
 *
 * This matches the "free resource via email" flow: capture email with minimal
 * friction. Normal (paid) carts and carts with shippable items are unaffected.
 */
add_filter('woocommerce_checkout_fields', function ($fields) {
    if (!function_exists('WC') || !WC()->cart) {
        return $fields;
    }

    $cart = WC()->cart;

    if ($cart->is_empty()) {
        return $fields;
    }

    // Only apply when the cart total is 0.
    if ((float) $cart->get_total('edit') !== 0.0) {
        return $fields;
    }

    // Only apply when every item is virtual OR downloadable (no shipping needed).
    foreach ($cart->get_cart() as $item) {
        $product = isset($item['data']) ? $item['data'] : null;
        if (!$product instanceof WC_Product) {
            return $fields;
        }

        if (!$product->is_virtual() && !$product->is_downloadable()) {
            return $fields; // bail: mixed cart, keep normal checkout
        }
    }

    // Keep only: first name, last name, email.
    $keep_billing = [
        'billing_first_name',
        'billing_last_name',
        'billing_email',
    ];

    foreach ($fields['billing'] as $key => $value) {
        if (!in_array($key, $keep_billing, true)) {
            unset($fields['billing'][$key]);
        }
    }

    // Remove shipping fields completely (just in case).
    if (isset($fields['shipping'])) {
        $fields['shipping'] = [];
    }

    // Remove order comments to keep the form clean.
    if (isset($fields['order']['order_comments'])) {
        unset($fields['order']['order_comments']);
    }

    return $fields;
}, 20);

// =============================================================================
// CHECKOUT — NEWSLETTER OPT-IN
// =============================================================================

/**
 * Display an unchecked newsletter consent checkbox in checkout (after order
 * notes). Consent is not pre-selected (GDPR/best-practice).
 */
add_action('woocommerce_after_order_notes', function ($checkout) {
    if (!function_exists('WC')) {
        return;
    }

    echo '<div id="dm-newsletter-optin" class="dm-newsletter-optin">';
    woocommerce_form_field('dm_newsletter_optin', [
        'type'     => 'checkbox',
        'class'    => ['form-row-wide'],
        'label'    => __('Sí, quiero suscribirme al newsletter (puedes darte de baja cuando quieras).', 'daniela-child'),
        'required' => false,
    ], $checkout->get_value('dm_newsletter_optin'));
    echo '</div>';
}, 20);

/**
 * Persist the newsletter opt-in choice in the order meta for audit trails
 * and future MailerLite sync.
 */
add_action('woocommerce_checkout_create_order', function ($order, $data) {
    if (!function_exists('WC')) {
        return;
    }
    $optin = !empty($_POST['dm_newsletter_optin']) ? 'yes' : 'no'; // phpcs:ignore WordPress.Security.NonceVerification
    $order->update_meta_data('_dm_newsletter_optin', $optin);
}, 20, 2);

// =============================================================================
// THANK YOU PAGE — EDITABLE MESSAGE FOR FREE DOWNLOADABLE ORDERS (B2)
// =============================================================================

/**
 * Default message shown on the Thank You page for free downloadable orders.
 * Editable from WooCommerce → Settings → Advanced → Free Resource Thank You.
 */
define('DM_FREE_THANKYOU_OPTION', 'dm_free_thankyou_message');
define('DM_FREE_THANKYOU_DEFAULT', 'Gracias por tu orden. En unos minutos te debería llegar un correo con el enlace de descarga. Revisa Spam/Promociones si no lo ves.');

/**
 * Display the configurable thank-you message on the order-received page for
 * orders where total = 0 and at least one item is virtual/downloadable.
 *
 * Rendered above the standard order details table via woocommerce_thankyou.
 */
add_action('woocommerce_thankyou', function ($order_id) {
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Only show for $0 orders.
    if ((float) $order->get_total() !== 0.0) {
        return;
    }

    // Only show when the order contains at least one downloadable or virtual item.
    $has_digital = false;
    foreach ($order->get_items() as $item) {
        /** @var WC_Order_Item_Product $item */
        $product = $item->get_product();
        if ($product && ($product->is_downloadable() || $product->is_virtual())) {
            $has_digital = true;
            break;
        }
    }

    if (!$has_digital) {
        return;
    }

    $message = get_option(DM_FREE_THANKYOU_OPTION, DM_FREE_THANKYOU_DEFAULT);
    $message = wp_kses_post($message);

    if ($message) {
        echo '<div class="woocommerce-message dm-free-thankyou-message" role="alert">';
        echo wp_kses_post(wpautop($message));
        echo '</div>';
    }
}, 5); // priority 5 so it renders above the standard order details

// =============================================================================
// THANK YOU MESSAGE — ADMIN SETTINGS PAGE
// =============================================================================

/**
 * Register a settings page under WooCommerce → Settings → Advanced to allow
 * editing the free-resource thank-you message without a code deploy.
 *
 * The page is added as a tab under the "Advanced" WooCommerce settings section.
 */
add_filter('woocommerce_get_sections_advanced', function ($sections) {
    $sections['dm_free_thankyou'] = __('Mensaje Recursos Gratis', 'daniela-child');
    return $sections;
});

add_filter('woocommerce_get_settings_advanced', function ($settings, $current_section) {
    if ('dm_free_thankyou' !== $current_section) {
        return $settings;
    }

    return [
        [
            'title' => __('Mensaje de "Gracias" para Recursos Gratis', 'daniela-child'),
            'type'  => 'title',
            'desc'  => __('Este mensaje se muestra en la página de confirmación de pedido cuando el total es $0 y el pedido contiene al menos un producto descargable o virtual.', 'daniela-child'),
            'id'    => 'dm_free_thankyou_section',
        ],
        [
            'title'    => __('Mensaje', 'daniela-child'),
            'type'     => 'textarea',
            'desc'     => __('Puedes usar HTML básico. Deja vacío para usar el mensaje por defecto.', 'daniela-child'),
            'id'       => DM_FREE_THANKYOU_OPTION,
            'default'  => DM_FREE_THANKYOU_DEFAULT,
            'css'      => 'min-height:120px;width:50%;',
            'desc_tip' => true,
        ],
        [
            'type' => 'sectionend',
            'id'   => 'dm_free_thankyou_section',
        ],
    ];
}, 10, 2);
