<?php
/**
 * Plugin Name: Patlis Security
 * Description: Core security rules for Patlis WordPress platform.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public POST routes that are required by frontend features. 
 * SOS is currently the only one, but this can be extended in the future if needed.
 */
function patlis_public_rest_post_route_allowed(string $route): bool
{
    $allowed = [
        '/bricks/v1/load_query_page',
    ];

    return in_array($route, $allowed, true);
}

/**
 * Block REST write access for visitors.
 * Public read requests stay available for frontend features.
 */
add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) {
        return $result;
    }

    if (is_user_logged_in()) {
        return $result;
    }

    $method = isset($_SERVER['REQUEST_METHOD'])
        ? strtoupper(sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])))
        : 'GET';

    // Allow read-only REST requests for visitors.
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        return $result;
    }

    // Extract current REST route (supports both pretty and ?rest_route=... formats).
    $route = '';

    if (isset($_GET['rest_route'])) {
        $route = '/' . ltrim((string) sanitize_text_field(wp_unslash($_GET['rest_route'])), '/');
    } else {
        $requestUri = isset($_SERVER['REQUEST_URI'])
            ? (string) wp_unslash($_SERVER['REQUEST_URI'])
            : '';

        $path = wp_parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';

        $prefix = '/' . trim(rest_get_url_prefix(), '/') . '/';
        $pos = strpos($path, $prefix);

        if ($pos !== false) {
            $route = '/' . ltrim(substr($path, $pos + strlen($prefix)), '/');
        }
    }

    if ($method === 'POST' && patlis_public_rest_post_route_allowed($route)) {
        return $result;
    }

    return new WP_Error(
        'rest_forbidden',
        __('REST write access is disabled for visitors.', 'default'),
        ['status' => 403]
    );
});

/**
 * Disable XML-RPC.
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Disable Application Passwords.
 */
add_filter('wp_is_application_passwords_available', '__return_false');
add_filter('wp_is_application_passwords_available_for_user', '__return_false');

/**
 * Remove REST API links from <head> and headers.
 */
add_action('init', function () {
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);
});

/**
 * Remove user endpoints from REST API for visitors to prevent username enumeration.
 */
add_filter('rest_endpoints', function ($endpoints) {
    if (!is_user_logged_in()) {
        unset($endpoints['/wp/v2/users']);
        unset($endpoints['/wp/v2/users/(?P<id>[\\d]+)']);
        unset($endpoints['/wp/v2/users/me']);
    }
    return $endpoints;
});

/**
 * Block public author archive access to reduce username enumeration.
 */
add_action('template_redirect', function () {
    if (is_user_logged_in()) {
        return;
    }

    if (is_author()) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
});

/**
 * Block ?author=1 style enumeration for visitors.
 */
add_action('init', function () {
    if (is_admin() || is_user_logged_in()) {
        return;
    }

    if (isset($_GET['author'])) {
        wp_die(__('Author pages are not available.', 'default'), '', ['response' => 403]);
    }
}, 1);