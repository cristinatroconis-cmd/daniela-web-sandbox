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

/**
 * Resolve sink mailbox for non-production deliveries.
 *
 * Only active when DM_STAGING_MAIL_SINK is explicitly defined.
 */
function dm_staging_get_mail_sink_address()
{
    if (! defined('DM_STAGING_MAIL_SINK')) {
        return '';
    }

    $sink = sanitize_email((string) DM_STAGING_MAIL_SINK);

    return is_email($sink) ? $sink : '';
}

/**
 * Redirect all outbound wp_mail() to a controlled sink inbox on non-production.
 * This preserves trigger behavior while avoiding real customer deliveries.
 */
add_filter('wp_mail', function ($atts) {
    if (! is_array($atts)) {
        return $atts;
    }

    $sink = dm_staging_get_mail_sink_address();
    if ($sink === '') {
        return $atts;
    }

    $original_to = $atts['to'] ?? '';
    if (is_array($original_to)) {
        $original_to = implode(', ', array_map('sanitize_text_field', $original_to));
    } else {
        $original_to = sanitize_text_field((string) $original_to);
    }

    $atts['to'] = $sink;

    $subject = isset($atts['subject']) ? (string) $atts['subject'] : '';
    if (strpos($subject, '[STAGING REDIRECT]') !== 0) {
        $atts['subject'] = '[STAGING REDIRECT] ' . $subject;
    }

    $headers = $atts['headers'] ?? array();
    if (! is_array($headers)) {
        $headers = array($headers);
    }

    $headers[] = 'X-DM-Staging-Original-To: ' . $original_to;
    $headers[] = 'X-DM-Staging-Sink-To: ' . $sink;
    $atts['headers'] = $headers;

    return $atts;
}, 20);

// Prevent WooCommerce webhook delivery from non-production.
add_filter('woocommerce_webhook_should_deliver', function ($should_deliver, $webhook, $arg) {
    return false;
}, 10, 3);

// Keep gateways available in non-production so they can run in test mode.
// Stripe/other providers should be configured as TEST from plugin settings.
