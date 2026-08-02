<?php
if (!defined('ABSPATH')) exit;

define('PATLIS_CORE_DB_VERSION', 2);

function patlis_core_create_or_update_tables(): void {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // ── patlis_leads ──────────────────────────────────────────────────────────
    $table_leads = $wpdb->prefix . 'patlis_leads';
    $sql_leads = "CREATE TABLE $table_leads (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        uuid VARCHAR(36) NOT NULL,
        lead_type VARCHAR(50) NULL,
        visitor_id VARCHAR(100) NULL,
        language VARCHAR(30) NULL,
        device_type VARCHAR(20) NULL,
        utm_source VARCHAR(255) NULL,
        utm_medium VARCHAR(255) NULL,
        utm_campaign VARCHAR(255) NULL,
        utm_content VARCHAR(255) NULL,
        utm_term VARCHAR(255) NULL,
        referrer TEXT NULL,
        landing_page TEXT NULL,
        source_url TEXT NULL,
        gclid VARCHAR(255) NULL,
        gbraid VARCHAR(255) NULL,
        wbraid VARCHAR(255) NULL,
        msclkid VARCHAR(255) NULL,
        fbclid VARCHAR(255) NULL,
        consent_json TEXT NULL,
        cookie_preferences TINYINT NOT NULL DEFAULT 0,
        cookie_statistics TINYINT NOT NULL DEFAULT 0,
        cookie_marketing TINYINT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY uuid (uuid),
        KEY visitor_id (visitor_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    // ── patlis_contacts ───────────────────────────────────────────────────────
    $table_contacts = $wpdb->prefix . 'patlis_contacts';
    $sql_contacts = "CREATE TABLE $table_contacts (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        lead_uuid VARCHAR(36) NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(50) NULL,
        message TEXT NULL,
        status TINYINT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY lead_uuid (lead_uuid),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    dbDelta($sql_leads);
    dbDelta($sql_contacts);

    update_option('patlis_core_db_version', PATLIS_CORE_DB_VERSION);
}

function patlis_core_maybe_upgrade_db(): void {
    $installed = (int) get_option('patlis_core_db_version', 0);
    if ($installed >= PATLIS_CORE_DB_VERSION) return;

    patlis_core_create_or_update_tables();
}
add_action('plugins_loaded', 'patlis_core_maybe_upgrade_db');
