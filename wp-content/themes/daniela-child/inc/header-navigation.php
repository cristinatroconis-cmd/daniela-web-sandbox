<?php

if (! defined('ABSPATH')) {
    exit;
}

function dm_header_login_target_url()
{
    if (function_exists('wc_get_page_permalink')) {
        $myaccount_url = wc_get_page_permalink('myaccount');
        if (is_string($myaccount_url) && $myaccount_url !== '') {
            return $myaccount_url;
        }
    }

    return home_url('/acceso/');
}

function dm_is_header_login_current()
{
    if (function_exists('is_account_page') && is_account_page()) {
        return true;
    }

    $request_path = isset($_SERVER['REQUEST_URI']) ? wp_parse_url(home_url(wp_unslash($_SERVER['REQUEST_URI'])), PHP_URL_PATH) : '';
    $target_path  = wp_parse_url(dm_header_login_target_url(), PHP_URL_PATH);

    return is_string($request_path) && is_string($target_path) && untrailingslashit($request_path) === untrailingslashit($target_path);
}

function dm_render_header_login_item()
{
    $classes = ['menu-item', 'dm-header-login'];
    if (dm_is_header_login_current()) {
        $classes[] = 'current-menu-item';
    }

    printf(
        '<li class="%1$s"><a href="%2$s"><span>%3$s</span></a></li>',
        esc_attr(implode(' ', $classes)),
        esc_url(dm_header_login_target_url()),
        esc_html__('Iniciar sesion', 'daniela-child')
    );
}

function dm_render_header_cart_link()
{
    if (! function_exists('shoptimizer_woo_cart_available') || ! shoptimizer_woo_cart_available()) {
        return;
    }

    $sidebar_cart_enabled = function_exists('shoptimizer_get_option') ? shoptimizer_get_option('shoptimizer_layout_woocommerce_enable_sidebar_cart') : false;
    $cart_icon            = function_exists('shoptimizer_get_option') ? shoptimizer_get_option('shoptimizer_layout_woocommerce_cart_icon') : 'cart';
    $cart_count           = function_exists('WC') && WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
    $cart_href            = $sidebar_cart_enabled ? '#' : wc_get_cart_url();

    echo '<div class="cart-click">';
    echo '<a class="cart-contents" href="' . esc_url($cart_href) . '" title="' . esc_attr__('View your shopping cart', 'shoptimizer') . '">';

    if ('basket' === $cart_icon) {
        echo '<span class="count">' . wp_kses_post(sprintf(_n('%d', '%d', $cart_count, 'shoptimizer'), $cart_count)) . '</span>';
    }

    if ('cart' === $cart_icon) {
        echo '<span class="shoptimizer-cart-icon">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>';
        echo '<span class="mini-count">' . wp_kses_data(sprintf(_n('%d', '%d', $cart_count, 'shoptimizer'), $cart_count)) . '</span>';
        echo '</span>';
    }

    if ('bag' === $cart_icon) {
        echo '<span class="shoptimizer-cart-icon">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>';
        echo '<span class="mini-count">' . wp_kses_data(sprintf(_n('%d', '%d', $cart_count, 'shoptimizer'), $cart_count)) . '</span>';
        echo '</span>';
    }

    echo '</a>';
    echo '</div>';
}

function dm_header_cart()
{
    $display_cart = function_exists('shoptimizer_get_option') ? shoptimizer_get_option('shoptimizer_layout_woocommerce_display_cart') : true;

    if (! function_exists('shoptimizer_is_woocommerce_activated') || ! shoptimizer_is_woocommerce_activated()) {
        return;
    }

    if (true !== $display_cart) {
        return;
    }

    echo '<ul class="site-header-cart menu">';
    dm_render_header_login_item();
    echo '<li class="dm-header-cart-item">';
    dm_render_header_cart_link();
    echo '</li>';
    echo '</ul>';
}

function dm_cart_link_fragment($fragments)
{
    ob_start();
    dm_render_header_cart_link();
    $fragments['div.cart-click'] = ob_get_clean();

    return $fragments;
}

function dm_remove_access_item_from_primary_menu($items, $args)
{
    if (! isset($args->theme_location) || 'primary' !== $args->theme_location || ! is_array($items)) {
        return $items;
    }

    return array_values(array_filter($items, static function ($item) {
        return isset($item->ID) ? (int) $item->ID !== 9366 : true;
    }));
}

add_action('after_setup_theme', static function () {
    remove_action('shoptimizer_navigation', 'shoptimizer_header_cart', 60);
    add_action('shoptimizer_navigation', 'dm_header_cart', 60);
}, 20);

add_filter('wp_nav_menu_objects', 'dm_remove_access_item_from_primary_menu', 20, 2);
add_filter('woocommerce_add_to_cart_fragments', 'dm_cart_link_fragment', 20);
