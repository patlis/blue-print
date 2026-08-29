<?php
/*
Plugin Name: Patlis Accommodation
Description: Accommodation module (Hotel) + bookings table
Version: 1.2.0
Author: Patlis
Update URI: https://updates.patlis.com/patlis-accommodation/
*/

if (!defined('ABSPATH')) exit;

define('PATLIS_ACCOMMODATION_PATH', plugin_dir_path(__FILE__));
define('PATLIS_ACCOMMODATION_URL',  plugin_dir_url(__FILE__));
define('PATLIS_ACCOMMODATION_VERSION', '1.2.0');

// Updater
if (function_exists('patlis_register_plugin_updater')) {
    patlis_register_plugin_updater(__FILE__, 'patlis-accommodation', PATLIS_ACCOMMODATION_VERSION);
}

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

add_action('wp_enqueue_scripts', function (): void {
    if (!patlis_accommodation_is_enabled_for_version() || !is_page()) {
        return;
    }

    $page_id = (int) get_queried_object_id();
    if ($page_id <= 0) {
        return;
    }

    $taxonomy = function_exists('patlis_version_get_page_template_taxonomy')
        ? patlis_version_get_page_template_taxonomy()
        : 'template';

    if (!taxonomy_exists($taxonomy) || !has_term('booking', $taxonomy, $page_id)) {
        return;
    }

    wp_enqueue_script(
        'patlis-accommodation-booking',
        PATLIS_ACCOMMODATION_URL . 'assets/js/booking.js',
        [],
        PATLIS_ACCOMMODATION_VERSION,
        true
    );
});

/* ============================================================
 * Includes (keep same structure as other Patlis plugins)
 * ============================================================ */
require_once PATLIS_ACCOMMODATION_PATH . 'includes/settings.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/post-types.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/bricks-tags.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/rooms-query.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/booking-form.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/form-handlers.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/multilingual.php';

require_once PATLIS_ACCOMMODATION_PATH . 'includes/term-sync.php';

require_once PATLIS_ACCOMMODATION_PATH . 'includes/amenities.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/facilities.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/services.php';
require_once PATLIS_ACCOMMODATION_PATH . 'includes/meal-plans.php';


 if (is_admin()) {
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/menu.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/settings.php';
    Patlis_Accommodation_Admin_Settings::init();
    
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/settings.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/rooms.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/rates.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/hotel-rate-periods.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/room-rates.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/amenities.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/facilities.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/services.php';
    require_once PATLIS_ACCOMMODATION_PATH . 'includes/admin/pages/meal-plans.php';
}


/* ============================================================
 * DB
 * ============================================================ */
define('PATLIS_ACCOMMODATION_DB_VERSION', 4);

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
        room_id INT NOT NULL,   /*ok*/
        check_in DATE NOT NULL,   /*ok*/
        check_out DATE NOT NULL,   /*ok*/
        nights INT NOT NULL,   /*ok*/

        adults INT NOT NULL,   /*ok*/
        children INT NOT NULL,   /*ok*/
        infants INT NOT NULL,   /*ok*/
        meal_plan_id INT NULL,
        offer_package_id INT NULL,
        offer_package_title VARCHAR(255) NULL,
        status TINYINT NOT NULL DEFAULT 0,   /*ok*/
        customer_name VARCHAR(255) NOT NULL,   /*ok*/
        customer_email VARCHAR(255) NOT NULL,   /*ok*/
        customer_phone VARCHAR(50) NOT NULL,   /*ok*/
        customer_notes TEXT NULL,
        lead_uuid VARCHAR(36) NULL,   /*ok*/
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   /*ok*/
        PRIMARY KEY  (id),
        KEY room_id (room_id),
        KEY check_in (check_in),
        KEY check_out (check_out),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    dbDelta($sql);

    foreach (['diet_type_id', 'transaction_id'] as $column) {
        $exists = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
        if ($exists) {
            $wpdb->query("ALTER TABLE {$table} DROP COLUMN {$column}");
        }
    }

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

