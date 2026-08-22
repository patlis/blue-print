<?php
if (!defined('ABSPATH')) exit;

define('PATLIS_RESERVATIONS_DB_VERSION', 2);

function patlis_reservations_create_or_update_tables(): void {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // ── patlis_reservations ───────────────────────────────────────────────────
    $table = $wpdb->prefix . 'patlis_reservations';
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        lead_uuid VARCHAR(36) NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NULL,
        message TEXT NULL,
        reservation_date DATETIME NOT NULL,
        guests TINYINT UNSIGNED NOT NULL DEFAULT 1,
        status TINYINT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY lead_uuid (lead_uuid),
        KEY reservation_date (reservation_date),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    dbDelta($sql);

    update_option('patlis_reservations_db_version', PATLIS_RESERVATIONS_DB_VERSION);
}

function patlis_reservations_maybe_upgrade_db(): void {
    $installed = (int) get_option('patlis_reservations_db_version', 0);
    if ($installed >= PATLIS_RESERVATIONS_DB_VERSION) return;

    patlis_reservations_create_or_update_tables();

    // v2: reservation_date DATE → DATETIME to preserve time
    if ($installed < 2) {
        global $wpdb;
        $table = $wpdb->prefix . 'patlis_reservations';
        $wpdb->query("ALTER TABLE `$table` MODIFY COLUMN `reservation_date` DATETIME NOT NULL");
    }

    update_option('patlis_reservations_db_version', PATLIS_RESERVATIONS_DB_VERSION);
}
add_action('plugins_loaded', 'patlis_reservations_maybe_upgrade_db');
