<?php
/**
 * Plugin Name: Disable Public API Access
 * Description: Blocks REST API for non-logged-in users and disables XML-RPC/Application Passwords.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Block REST API access for visitors.
 * Keep REST available only for logged-in users.
 */
add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) {
        return $result;
    }

    if (is_user_logged_in()) {
        return $result;
    }

    return new WP_Error(
        'rest_forbidden',
        __('REST API is disabled on this site.', 'default'),
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
