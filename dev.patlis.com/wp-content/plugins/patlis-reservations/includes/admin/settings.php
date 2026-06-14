<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin POST handler for Patlis Reservations settings.
 * action: patlis_reservations_save_settings
 */

add_action('admin_post_patlis_reservations_save_settings', function () {

    if (!current_user_can('patlis_manage')) {
        wp_die('Not allowed.');
    }

    check_admin_referer('patlis_reservations_save_settings');

    // Settings array name is the option key (e.g. patlis_reservations_settings[mode]=...)
    $key = function_exists('patlis_reservations_option_key')
        ? patlis_reservations_option_key()
        : 'patlis_reservations_settings';

    $raw = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : [];

    // Sanitize (function lives in includes/admin/pages/settings.php)
    if (function_exists('patlis_reservations_sanitize_settings')) {
        $clean = patlis_reservations_sanitize_settings($raw);
    } else {
        // Fallback: at least ensure it's an array
        $clean = is_array($raw) ? $raw : [];
    }

    update_option($key, $clean, false);

    // Redirect back to settings page with flag
    $slug = function_exists('patlis_reservations_page_slug_safe')
        ? patlis_reservations_page_slug_safe()
        : 'patlis-reservations';

    wp_safe_redirect(
        admin_url('admin.php?page=' . urlencode($slug) . '&patlis_saved=1')
    );
    exit;
});