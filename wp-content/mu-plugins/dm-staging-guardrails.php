<?php

/**
 * Plugin Name: DM - Staging Guardrails
 * Description: Safety rails for staging/local to avoid impacting production services.
 * Version: 1.0.0
 * Author: Daniela Child / Custom
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Detect if current request is running on a non-production environment.
 */
function dm_is_non_production_env()
{
    $host = isset($_SERVER['HTTP_HOST']) ? strtolower(trim((string) $_SERVER['HTTP_HOST'])) : '';

    // Explicit production host allowlist. Everything else can be treated as non-production.
    $production_hosts = array(
        'danielamontespsic.com',
        'www.danielamontespsic.com',
    );

    if (in_array($host, $production_hosts, true)) {
        return false;
    }

    if (defined('WP_ENV')) {
        $env = strtolower((string) WP_ENV);
        if (in_array($env, array('staging', 'development', 'local'), true)) {
            return true;
        }
    }

    if (defined('WP_ENVIRONMENT_TYPE')) {
        $env_type = strtolower((string) WP_ENVIRONMENT_TYPE);
        if (in_array($env_type, array('staging', 'development', 'local'), true)) {
            return true;
        }
    }

    if ('' === $host) {
        return false;
    }

    // Hostname heuristics for staging/local environments.
    if (false !== strpos($host, 'staging')) {
        return true;
    }

    if (false !== strpos($host, '.onrocket.site')) {
        return true;
    }

    if (false !== strpos($host, '.local')) {
        return true;
    }

    return false;
}

if (! dm_is_non_production_env()) {
    return;
}

// Always discourage indexing on non-production.
add_filter('pre_option_blog_public', function () {
    return '0';
});

add_filter('wp_robots', function ($robots) {
    $robots['noindex']  = true;
    $robots['nofollow'] = true;
    return $robots;
}, 20);

add_action('send_headers', function () {
    if (! headers_sent()) {
        header('X-Robots-Tag: noindex, nofollow, noarchive', true);
    }
}, 20);

// Block all outbound WordPress mail on non-production.
add_filter('pre_wp_mail', function ($pre, $atts) {
    return true;
}, 10, 2);

// Prevent WooCommerce webhook delivery from non-production.
add_filter('woocommerce_webhook_should_deliver', function ($should_deliver, $webhook, $arg) {
    return false;
}, 10, 3);
