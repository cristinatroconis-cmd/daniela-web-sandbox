<?php

if (! defined('ABSPATH')) {
    exit;
}

function dm_get_booking_waitlist_url_default()
{
    return 'https://docs.google.com/forms/d/e/1FAIpQLSez3rvnIR6LBL0oPVyHq1yBa6xXNt8nMGj3a87SbpNYuqVVzw/viewform';
}

function dm_get_booking_waitlist_url()
{
    $stored  = trim((string) get_option('dm_booking_waitlist_url', ''));
    $fallback = dm_get_booking_waitlist_url_default();
    $url     = $stored !== '' ? $stored : $fallback;

    return esc_url_raw($url);
}

function dm_is_booking_open()
{
    return (bool) get_option('dm_booking_sessions_open', false);
}

function dm_is_booking_waitlist_active()
{
    return ! dm_is_booking_open();
}

function dm_get_waitlist_product_category_slugs()
{
    return apply_filters('dm_waitlist_product_category_slugs', ['sesiones']);
}

function dm_product_uses_waitlist($product)
{
    if (! function_exists('wc_get_product')) {
        return false;
    }

    if (! $product instanceof WC_Product) {
        $product = wc_get_product($product);
    }

    if (! $product instanceof WC_Product) {
        return false;
    }

    if (! dm_is_booking_waitlist_active()) {
        return false;
    }

    return has_term(dm_get_waitlist_product_category_slugs(), 'product_cat', $product->get_id());
}

function dm_render_waitlist_button($label = '', $class = '')
{
    $label = trim((string) $label);
    $class = trim((string) $class);

    if ($label === '') {
        $label = __('Unirme a la lista de espera', 'daniela-child');
    }

    if ($class === '') {
        $class = 'dm-btn dm-btn--primary';
    }

    return sprintf(
        '<a href="%1$s" class="%2$s">%3$s</a>',
        esc_url(dm_get_booking_waitlist_url()),
        esc_attr($class),
        esc_html($label)
    );
}

add_action('admin_init', function () {
    register_setting('general', 'dm_booking_sessions_open', [
        'type'              => 'boolean',
        'sanitize_callback' => static function ($value) {
            return ! empty($value) ? 1 : 0;
        },
        'default'           => 0,
    ]);

    register_setting('general', 'dm_booking_waitlist_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => dm_get_booking_waitlist_url_default(),
    ]);

    add_settings_field(
        'dm_booking_sessions_open',
        __('Agenda de sesiones abierta', 'daniela-child'),
        static function () {
            $value = (bool) get_option('dm_booking_sessions_open', false);
            echo '<label><input type="checkbox" name="dm_booking_sessions_open" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Activar compra/agendado normal para productos de sesiones.', 'daniela-child') . '</label>';
            echo '<p class="description">' . esc_html__('Si está desactivado, los CTAs de sesiones llevan a la lista de espera y se bloquea la compra directa.', 'daniela-child') . '</p>';
        },
        'general'
    );

    add_settings_field(
        'dm_booking_waitlist_url',
        __('URL lista de espera', 'daniela-child'),
        static function () {
            $value = (string) get_option('dm_booking_waitlist_url', dm_get_booking_waitlist_url_default());
            echo '<input type="url" class="regular-text code" name="dm_booking_waitlist_url" value="' . esc_attr($value) . '" />';
            echo '<p class="description">' . esc_html__('Destino único para los CTAs de sesiones cuando la agenda esté cerrada.', 'daniela-child') . '</p>';
        },
        'general'
    );
});

add_filter('woocommerce_is_purchasable', function ($purchasable, $product) {
    if (dm_product_uses_waitlist($product)) {
        return false;
    }

    return $purchasable;
}, 20, 2);

add_filter('woocommerce_loop_add_to_cart_link', function ($html, $product, $args) {
    if (! dm_product_uses_waitlist($product)) {
        return $html;
    }

    $label = isset($args['text']) && trim((string) $args['text']) !== ''
        ? trim((string) $args['text'])
        : __('Unirme a la lista de espera', 'daniela-child');
    $class = isset($args['class']) && trim((string) $args['class']) !== ''
        ? trim(str_replace(['add_to_cart_button', 'ajax_add_to_cart'], '', (string) $args['class']))
        : 'button';

    return dm_render_waitlist_button($label, $class);
}, 20, 3);

add_action('woocommerce_single_product_summary', function () {
    global $product;

    if (! $product instanceof WC_Product || ! dm_product_uses_waitlist($product)) {
        return;
    }

    echo '<div class="dm-cta dm-cta--waitlist">';
    echo dm_render_waitlist_button(__('Unirme a la lista de espera', 'daniela-child'), 'single_add_to_cart_button button alt dm-btn dm-btn--primary'); // phpcs:ignore WordPress.Security.EscapeOutput
    echo '</div>';
}, 31);

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || empty($_REQUEST['add-to-cart']) || ! function_exists('wc_get_product')) {
        return;
    }

    $product = wc_get_product(absint(wp_unslash($_REQUEST['add-to-cart'])));
    if (! $product instanceof WC_Product || ! dm_product_uses_waitlist($product)) {
        return;
    }

    wp_safe_redirect(dm_get_booking_waitlist_url());
    exit;
}, 5);

add_filter('woocommerce_add_to_cart_validation', function ($passed, $product_id) {
    $product = function_exists('wc_get_product') ? wc_get_product($product_id) : null;
    if (! $product instanceof WC_Product || ! dm_product_uses_waitlist($product)) {
        return $passed;
    }

    if (! wp_doing_ajax()) {
        return false;
    }

    wc_add_notice(__('La agenda está cerrada por ahora. Puedes unirte a la lista de espera.', 'daniela-child'), 'notice');
    return false;
}, 20, 2);
