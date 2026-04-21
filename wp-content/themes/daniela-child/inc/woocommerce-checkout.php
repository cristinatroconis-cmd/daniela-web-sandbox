<?php

/**
 * WooCommerce checkout — single-product back-link and free-cart redirect.
 *
 * @package Daniela_Child
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * True cuando el carrito contiene al menos un recurso gratuito.
 *
 * @return bool
 */
function dm_cart_has_free_resource_item()
{
    if (! function_exists('WC') || ! WC()->cart) {
        return false;
    }

    foreach (WC()->cart->get_cart() as $item) {
        $product = isset($item['data']) && $item['data'] instanceof WC_Product ? $item['data'] : null;
        if (! $product) {
            continue;
        }

        $price = (float) $product->get_price();
        if ($price <= 0.0 && has_term(array('recursos'), 'product_cat', $product->get_id())) {
            return true;
        }
    }

    return false;
}

/**
 * True cuando el carrito contiene al menos un producto de pago (> 0).
 *
 * @return bool
 */
function dm_cart_has_paid_item()
{
    if (! function_exists('WC') || ! WC()->cart) {
        return false;
    }

    foreach (WC()->cart->get_cart() as $item) {
        $product = isset($item['data']) && $item['data'] instanceof WC_Product ? $item['data'] : null;
        if (! $product) {
            continue;
        }

        if ((float) $product->get_price() > 0.0) {
            return true;
        }
    }

    return false;
}

/**
 * True cuando el carrito tiene productos y todos son gratuitos.
 *
 * @return bool
 */
function dm_cart_is_only_free_items()
{
    if (! function_exists('WC') || ! WC()->cart || WC()->cart->is_empty()) {
        return false;
    }

    return ! dm_cart_has_paid_item();
}

// =============================================================================
// SINGLE PRODUCT — "VOLVER" LINK
// =============================================================================

/**
 * Display a contextual "Volver" link above the single product content.
 *
 * Priority order:
 * 1. ?dm_back= query param set by our listing pages (validated to own host).
 * 2. wp_get_referer() if it points to our own domain.
 * 3. Fallback to /recursos/.
 */
add_action('woocommerce_before_single_product', function () {
    $back_url = '';

    // 1. Query param passed by listing shortcodes.
    if (! empty($_GET['dm_back'])) { // phpcs:ignore WordPress.Security.NonceVerification
        $candidate = esc_url_raw(urldecode(wp_unslash($_GET['dm_back']))); // phpcs:ignore WordPress.Security.NonceVerification
        $back_url  = wp_validate_redirect($candidate, '');
    }

    // 2. Browser referer (same host only).
    if (! $back_url) {
        $referer  = wp_get_referer();
        $back_url = $referer ? wp_validate_redirect($referer, '') : '';
    }

    // 3. Fallback.
    if (! $back_url) {
        $back_url = home_url('/recursos/');
    }

    echo '<a href="' . esc_url($back_url) . '" class="dm-back-link">&#8592; ' .
        esc_html__('Volver', 'daniela-child') . '</a>';
}, 5);

// =============================================================================
// CART → CHECKOUT REDIRECT (free-only cart)
// =============================================================================

/**
 * If the cart is not empty and the total is 0 (all items free),
 * automatically redirect from the cart page to checkout.
 */
add_action('template_redirect', function () {
    if (! function_exists('WC') || ! WC()->cart) {
        return;
    }

    $cart = WC()->cart;

    if (is_cart() && ! $cart->is_empty() && (float) $cart->get_total('edit') == 0) {
        wp_safe_redirect(wc_get_checkout_url());
        exit;
    }
});

/**
 * Si todo el carrito es gratis, WooCommerce no debe solicitar pago.
 */
add_filter('woocommerce_cart_needs_payment', function ($needs_payment, $cart) {
    if (! $cart instanceof WC_Cart) {
        return $needs_payment;
    }

    if ($cart->is_empty()) {
        return $needs_payment;
    }

    return dm_cart_is_only_free_items() ? false : $needs_payment;
}, 20, 2);

/**
 * Completa automáticamente pedidos sin pago (solo productos gratis).
 */
add_action('woocommerce_checkout_order_processed', function ($order_id) {
    $order = wc_get_order($order_id);
    if (! $order instanceof WC_Order) {
        return;
    }

    if ((float) $order->get_total() > 0.0) {
        return;
    }

    $has_paid = false;
    foreach ($order->get_items('line_item') as $item) {
        $product = $item->get_product();
        if ($product && (float) $product->get_price() > 0.0) {
            $has_paid = true;
            break;
        }
    }

    if (! $has_paid && $order->has_status(array('pending', 'on-hold', 'processing'))) {
        $order->update_status(
            'completed',
            __('Pedido gratuito completado automáticamente para enviar enlaces de descarga por correo.', 'daniela-child')
        );
    }
}, 20);

/**
 * Detecta si un pedido tiene permisos de descarga corruptos o desactualizados.
 */
function dm_order_download_permissions_need_repair(WC_Order $order): bool
{
    if (! function_exists('WC_Data_Store')) {
        return false;
    }

    $data_store = WC_Data_Store::load('customer-download');

    foreach ($order->get_items('line_item') as $item) {
        if (! $item instanceof WC_Order_Item_Product) {
            continue;
        }

        $product = $item->get_product();
        if (! $product || ! $product->is_downloadable()) {
            continue;
        }

        $expected_ids = array_keys($product->get_downloads());
        if (empty($expected_ids)) {
            continue;
        }

        $customer_downloads = $data_store->get_downloads([
            'user_email' => $order->get_billing_email(),
            'order_id'   => $order->get_id(),
            'product_id' => $product->get_id(),
        ]);

        if (count($customer_downloads) !== count($expected_ids)) {
            return true;
        }

        foreach ($customer_downloads as $customer_download) {
            $download_id = (string) $customer_download->get_download_id();
            if ($download_id === '' || ! in_array($download_id, $expected_ids, true)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Regenera permisos de descarga si Woo guardó download_id vacíos o desfasados.
 */
function dm_repair_order_download_permissions($order_id): void
{
    $order = wc_get_order($order_id);
    if (! $order instanceof WC_Order) {
        return;
    }

    if (! dm_order_download_permissions_need_repair($order)) {
        return;
    }

    global $wpdb;

    $wpdb->delete(
        $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
        ['order_id' => $order->get_id()],
        ['%d']
    );

    $order->delete_meta_data('_download_permissions_granted');
    $order->save();

    wc_downloadable_product_permissions($order->get_id(), true);
}
add_action('woocommerce_order_status_completed', 'dm_repair_order_download_permissions', 20);
add_action('woocommerce_order_status_processing', 'dm_repair_order_download_permissions', 20);

/**
 * En la página de gracias, la entrega debe ser por correo, no con descarga inmediata.
 */
add_filter('woocommerce_order_downloads_table_show_downloads', function ($show_downloads) {
    if (function_exists('is_order_received_page') && is_order_received_page()) {
        return false;
    }

    return $show_downloads;
}, 20);

/**
 * Oculta también el bloque de descargas del checkout por bloques.
 */
add_filter('render_block', function ($block_content, $block) {
    if (
        function_exists('is_order_received_page')
        && is_order_received_page()
        && is_array($block)
        && (($block['blockName'] ?? '') === 'woocommerce/order-confirmation-downloads')
    ) {
        return '';
    }

    return $block_content;
}, 20, 2);

/**
 * Mensaje UX en checkout para pedidos con recursos gratis.
 */
add_action('woocommerce_review_order_before_submit', function () {
    if (! is_checkout() || ! function_exists('WC') || ! WC()->cart || WC()->cart->is_empty()) {
        return;
    }

    $has_free = dm_cart_has_free_resource_item();
    if (! $has_free) {
        return;
    }

    $has_paid = dm_cart_has_paid_item();
    $message  = $has_paid
        ? __('Tus recursos PDF gratuitos llegarán por correo junto con la confirmación de tu compra.', 'daniela-child')
        : __('Este pedido no requiere pago. Al finalizar, recibirás tus recursos PDF gratuitos por correo.', 'daniela-child');

    echo '<div class="dm-checkout-freebie-note" role="status" aria-live="polite">';
    echo '<strong>' . esc_html__('Entrega por correo:', 'daniela-child') . '</strong> ';
    echo esc_html($message);
    echo '</div>';
}, 15);

// =============================================================================
// CART REDIRECT — Evitar redirección a /tienda/ y al carrito tras agregar.
// =============================================================================

/**
 * Hard override: forzar que WooCommerce nunca redirija al carrito tras agregar
 * un producto, independientemente de la configuración en el panel de WP.
 * El cart drawer off-canvas se encarga de mostrar el carrito al usuario.
 */
add_filter('option_woocommerce_cart_redirect_after_add', function () {
    return 'no';
}, 20);

/**
 * Después de agregar un producto al carrito, evitar cualquier redirección a
 * /tienda/ o /carrito/. Para peticiones AJAX devolvemos false para que
 * WooCommerce omita el redirect en la respuesta JSON y dispare added_to_cart
 * normalmente. Para peticiones no-AJAX (fallback sin JS) volvemos a la página
 * de origen en lugar de ir al carrito.
 *
 * @param string $url URL de redirección propuesta por WooCommerce.
 * @return string|false URL corregida, o false para suprimir el redirect en AJAX.
 */
add_filter('woocommerce_add_to_cart_redirect', function ($url) {
    // En peticiones AJAX: suprimir el redirect para que el drawer pueda abrirse.
    if (wp_doing_ajax()) {
        return false;
    }

    // En peticiones no-AJAX: no redirigir ni a /tienda/ ni a /carrito/.
    $shop_url = function_exists('wc_get_page_id') ? get_permalink(wc_get_page_id('shop')) : '';
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '';

    if (
        ($shop_url && trailingslashit($url) === trailingslashit($shop_url)) ||
        ($cart_url && trailingslashit($url) === trailingslashit($cart_url))
    ) {
        $referer = wp_get_referer();
        return $referer
            ? wp_validate_redirect($referer, home_url('/recursos/'))
            : home_url('/recursos/');
    }

    return $url;
}, 20);

/**
 * Filtrar el enlace "Seguir comprando" en el carrito para que no apunte a /tienda/.
 *
 * @param string $url URL de "seguir comprando".
 * @return string     URL corregida.
 */
add_filter('woocommerce_continue_shopping_redirect', function ($url) {
    $shop_url = get_permalink(wc_get_page_id('shop'));

    if ($shop_url && trailingslashit($url) === trailingslashit($shop_url)) {
        return home_url('/');
    }

    return $url;
});

/**
 * Fuerza traducciones clave de WooCommerce al español si el sitio todavía
 * muestra etiquetas en inglés en frontend/emails.
 */
add_filter('gettext', function ($translated, $text, $domain) {
    if (! in_array($domain, array('woocommerce', 'daniela-child'), true)) {
        return $translated;
    }

    $translations = array(
        'Checkout'                       => 'Finalizar compra',
        'Proceed to checkout'            => 'Finalizar compra',
        'Place order'                    => 'Realizar pedido',
        'View cart'                      => 'Ver carrito',
        'Cart totals'                    => 'Totales del carrito',
        'Return to shop'                 => 'Volver a la tienda',
        'Continue shopping'              => 'Seguir comprando',
        'Billing details'                => 'Datos de facturación',
        'Additional information'         => 'Información adicional',
        'Order notes'                    => 'Notas del pedido',
        'Your order'                     => 'Tu pedido',
        'Coupon code'                    => 'Código de cupón',
        'Apply coupon'                   => 'Aplicar cupón',
        'Update cart'                    => 'Actualizar carrito',
        'Product'                        => 'Producto',
        'Price'                          => 'Precio',
        'Quantity'                       => 'Cantidad',
        'Subtotal'                       => 'Subtotal',
        'Total'                          => 'Total',
        'Cart'                           => 'Carrito',
        'Add to cart'                    => 'Agregar al carrito',
        'Read more'                      => 'Ver más',
        'Select options'                 => 'Ver opciones',
        'Sale!'                          => '¡Oferta!',
        'Out of stock'                   => 'Agotado',
        'Description'                    => 'Descripción',
        'Reviews'                        => 'Reseñas',
        'Related products'               => 'Productos relacionados',
        'My account'                     => 'Mi cuenta',
        'Orders'                         => 'Pedidos',
        'Downloads'                      => 'Descargas',
        'Addresses'                      => 'Direcciones',
        'Account details'                => 'Detalles de la cuenta',
        'Log out'                        => 'Cerrar sesión',
        'Lost your password?'            => '¿Olvidaste tu contraseña?',
        'Remember me'                    => 'Recuérdame',
        'Username or email address'      => 'Nombre de usuario o correo electrónico',
        'Password'                       => 'Contraseña',
        'Email address'                  => 'Correo electrónico',
        'First name'                     => 'Nombre',
        'Last name'                      => 'Apellidos',
        'Phone'                          => 'Teléfono',
        'Company name (optional)'        => 'Empresa (opcional)',
        'Country / Region'               => 'País / Región',
        'Street address'                 => 'Dirección',
        'Apartment, suite, unit, etc. (optional)' => 'Apartamento, unidad, etc. (opcional)',
        'Town / City'                    => 'Ciudad',
        'State / County'                 => 'Provincia',
        'Postcode / ZIP'                 => 'Código postal',
        'Ship to a different address?'   => '¿Enviar a una dirección diferente?',
        'Returning customer?'            => '¿Ya compraste antes?',
        'Click here to login'            => 'Haz clic aquí para iniciar sesión',
        'Have a coupon?'                 => '¿Tienes un cupón?',
        'Click here to enter your code'  => 'Haz clic aquí para introducir tu código',
    );

    return $translations[$text] ?? $translated;
}, 20, 3);
