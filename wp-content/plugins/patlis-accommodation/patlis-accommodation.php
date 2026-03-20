<?php
/*
Plugin Name: Patlis Accommodation
Description: Accommodation module (Hotel) + bookings table
Version: 1.0.0
Author: Patlis
*/

if (!defined('ABSPATH')) exit;

define('PATLIS_ACCOMMODATION_PATH', plugin_dir_path(__FILE__));
define('PATLIS_ACCOMMODATION_URL',  plugin_dir_url(__FILE__));

/* ============================================================
 * Version gating (multi-version support: e.g. "gastro, hotel")
 * ============================================================ */
function patlis_accommodation_is_enabled_for_version(): bool {
    if (!defined('PATLIS_VERSION')) return true;

    $parts = array_filter(array_map('trim', explode(',', (string) PATLIS_VERSION)));
    return in_array('hotel', $parts, true);
}

/* ============================================================
 * Activation gating
 * ============================================================ */
function patlis_accommodation_require_supported_version_or_die() {
    if (!patlis_accommodation_is_enabled_for_version()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            'This plugin is available only for customers who have purchased the Hotel website version.',
            'Activation blocked',
            ['back_link' => true]
        );
    }
}

/* ============================================================
 * Includes (keep same structure as other Patlis plugins)
 * ============================================================ */
require_once PATLIS_ACCOMMODATION_PATH . 'includes/settings.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/post-types.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/bricks-tags.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/rooms-query.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/booking-form.php';

require_once PATLIS_ACCOMMODATION_PATH . 'includes/amenities.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/facilities.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/services.php';


 if (is_admin()) {
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/menu.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/settings.php';
    Patlis_Accommodation_Admin_Settings::init();
    
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/settings.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/rooms.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/amenities.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/facilities.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/services.php';
}


/* ============================================================
 * DB
 * ============================================================ */
define('PATLIS_ACCOMMODATION_DB_VERSION', 2);

register_activation_hook(__FILE__, 'patlis_accommodation_on_activate');

function patlis_accommodation_on_activate() {
    patlis_accommodation_require_supported_version_or_die();
    patlis_accommodation_create_or_update_tables();
}

function patlis_accommodation_create_or_update_tables() {
    global $wpdb;

    $table           = $wpdb->prefix . 'patlis_bookings';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table (
        id INT NOT NULL AUTO_INCREMENT,
        room_id INT NOT NULL,
        check_in DATE NOT NULL,
        check_out DATE NOT NULL,
        nights INT NOT NULL,
        adults INT NOT NULL,
        children INT NOT NULL,
        infants INT NOT NULL,
        diet_type_id INT NULL,
        transaction_id VARCHAR(100) NULL,
        status TINYINT NOT NULL DEFAULT 0,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY room_id (room_id),
        KEY check_in (check_in),
        KEY check_out (check_out),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    dbDelta($sql);

    update_option('patlis_accommodation_db_version', PATLIS_ACCOMMODATION_DB_VERSION);
}

add_action('plugins_loaded', 'patlis_accommodation_maybe_upgrade_db');

function patlis_accommodation_maybe_upgrade_db() {
    if (!patlis_accommodation_is_enabled_for_version()) return;

    $installed = (int) get_option('patlis_accommodation_db_version', 0);
    if ($installed >= PATLIS_ACCOMMODATION_DB_VERSION) return;

    // Safe upgrades via dbDelta (when DB version increases later)
    patlis_accommodation_create_or_update_tables();
}

