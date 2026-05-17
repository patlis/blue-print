<?php
if (!defined('ABSPATH')) exit;

/**
 * Add admin menu for kiosk settings
 */
function patlis_kiosk_add_admin_menu() {
    add_menu_page(
        'Kiosk Mode',
        'Kiosk Mode',
        'manage_options',
        'patlis-kiosk-mode',
        'patlis_kiosk_settings_page',
        'dashicons-layout',
        58
    );
}

/**
 * Render settings page
 */
function patlis_kiosk_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    require_once PATLIS_KIOSK_INCLUDES_DIR . 'admin/pages/settings.php';
}
