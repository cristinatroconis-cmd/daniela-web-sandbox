<?php

/**
 * Plugin Name: DM - Host Theme Routing
 * Description: Forces the correct theme and site URLs depending on the current host.
 * Version: 1.0.0
 * Author: Daniela Child / Custom
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Return the current HTTP host without port.
 *
 * @return string
 */
function dm_current_request_host()
{
    $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
    $host = strtolower(trim($host));

    if ($host === '') {
        return '';
    }

    return preg_replace('/:\d+$/', '', $host);
}

$dm_host         = dm_current_request_host();
$dm_live_host    = 'danielamontespsic.com';
$dm_staging_host = 'v2vvroh9bv-staging.onrocket.site';

if ($dm_host === $dm_staging_host) {
    $dm_staging_base = 'https://' . $dm_staging_host;

    add_filter('pre_option_home', static function () use ($dm_staging_host) {
        return 'https://' . $dm_staging_host;
    });

    add_filter('pre_option_siteurl', static function () use ($dm_staging_host) {
        return 'https://' . $dm_staging_host;
    });

    add_filter('pre_option_whl_page', static function () {
        return 'acceso';
    });

    add_filter('pre_option_whl_redirect_admin', static function () {
        return 'acceso';
    });

    add_filter('pre_option_stylesheet', static function () {
        return 'daniela-child';
    });

    add_filter('pre_option_template', static function () {
        return 'shoptimizer';
    });

    // Safety net: keep all runtime redirects inside staging host.
    add_filter('wp_redirect', static function ($location) use ($dm_staging_base, $dm_live_host) {
        if (! is_string($location) || $location === '') {
            return $location;
        }

        if (stripos($location, 'https://' . $dm_live_host) === 0 || stripos($location, 'http://' . $dm_live_host) === 0) {
            return preg_replace('#^https?://(www\.)?danielamontespsic\.com#i', $dm_staging_base, $location);
        }

        return $location;
    }, 9999);
} elseif ($dm_host === $dm_live_host || $dm_host === 'www.' . $dm_live_host) {
    add_filter('pre_option_home', static function () use ($dm_live_host) {
        return 'https://' . $dm_live_host;
    });

    add_filter('pre_option_siteurl', static function () use ($dm_live_host) {
        return 'https://' . $dm_live_host;
    });

    add_filter('pre_option_stylesheet', static function () {
        return 'shoptimizer';
    });

    add_filter('pre_option_template', static function () {
        return 'shoptimizer';
    });
}
