<?php
/**
 * Plugin Name: Patlis Kiosk Mode
 * Plugin URI: https://patlis.com/kiosk-mode
 * Description: Simple kiosk inactivity redirect.
 * Version: 1.0.0
 * Author: Patlis
 * Author URI: https://patlis.com
 * License: GPL v2 or later
 * Text Domain: patlis-kiosk-mode
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

// Plugin constants
define('PATLIS_KIOSK_VERSION', '1.0.12');
define('PATLIS_KIOSK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PATLIS_KIOSK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PATLIS_KIOSK_ASSETS_URL', PATLIS_KIOSK_PLUGIN_URL . 'assets/');
define('PATLIS_KIOSK_INCLUDES_DIR', PATLIS_KIOSK_PLUGIN_DIR . 'includes/');

// Include core functions
require_once PATLIS_KIOSK_INCLUDES_DIR . 'post-types.php';
require_once PATLIS_KIOSK_INCLUDES_DIR . 'kiosk-functions.php';
require_once PATLIS_KIOSK_INCLUDES_DIR . 'admin/menu.php';
require_once PATLIS_KIOSK_INCLUDES_DIR . 'admin/settings.php';
require_once PATLIS_KIOSK_INCLUDES_DIR . 'admin/metaboxes.php';
require_once PATLIS_KIOSK_INCLUDES_DIR . 'bricks-tags.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'patlis_kiosk_activate');
register_deactivation_hook(__FILE__, 'patlis_kiosk_deactivate');

function patlis_kiosk_activate() {
    // Add default options if they don't exist
    if (!get_option('patlis_kiosk_inactivity_timeout')) {
        update_option('patlis_kiosk_inactivity_timeout', 60);
    }
}

function patlis_kiosk_deactivate() {
    // Cleanup if needed
}

// Frontend hooks
add_action('init', 'patlis_kiosk_manage_cookie');
add_action('wp_enqueue_scripts', 'patlis_kiosk_enqueue_scripts');
add_action('wp_footer', 'patlis_kiosk_init_script');
add_filter('body_class', 'patlis_kiosk_add_body_class');

// Admin hooks
add_action('admin_menu', 'patlis_kiosk_add_admin_menu');
add_action('admin_init', 'patlis_kiosk_register_settings');
