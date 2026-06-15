<?php
if (!defined('ABSPATH')) {
    exit;
}

function patlis_core_translations_table_name(): string
{
    global $wpdb;

    return $wpdb->prefix . 'patlis_translations';
}

function patlis_core_translations_db_version(): string
{
    return '1';
}

function patlis_core_translations_db_version_option_name(): string
{
    return 'patlis_core_translations_db_version';
}

function patlis_core_normalize_translation_key(string $key): string
{
    $key = trim($key);
    $key = strtolower($key);
    $key = str_replace([' ', '-'], '_', $key);
    $key = preg_replace('/[^a-z0-9_]/', '', $key);
    $key = preg_replace('/_+/', '_', $key);

    return trim((string) $key, '_');
}

function patlis_core_is_valid_translation_key(string $key): bool
{
    if ($key === '') {
        return false;
    }

    if (strlen($key) < 4 || strlen($key) > 100) {
        return false;
    }

    if (!preg_match('/^[a-z0-9_]+$/', $key)) {
        return false;
    }

    return strpos($key, 'patlis_') === 0;
}

function patlis_core_normalize_translation_lang(string $lang): string
{
    $lang = sanitize_key($lang);

    return strtolower(trim($lang));
}

function patlis_core_manual_translation_lang_marker(): string
{
    return '__manual_key__';
}

function patlis_core_install_translations_table(): void
{
    $installed_version = (string) get_option(patlis_core_translations_db_version_option_name(), '');

    if ($installed_version === patlis_core_translations_db_version()) {
        return;
    }

    $table_name = patlis_core_translations_table_name();
    $charset    = function_exists('get_charset_collate') ? get_charset_collate() : '';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table_name} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        translation_key varchar(191) NOT NULL,
        lang varchar(32) NOT NULL,
        translation_value longtext NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY translation_key_lang (translation_key,lang),
        KEY lang (lang),
        KEY translation_key (translation_key)
    ) {$charset};";

    dbDelta($sql);

    update_option(patlis_core_translations_db_version_option_name(), patlis_core_translations_db_version(), false);
}

function patlis_core_maybe_install_translations_table(): void
{
    if (!function_exists('dbDelta')) {
        patlis_core_install_translations_table();
        return;
    }

    patlis_core_install_translations_table();
}

function patlis_core_translation_storage_ready(): bool
{
    static $storage_ready = null;

    if (is_bool($storage_ready)) {
        return $storage_ready;
    }

    global $wpdb;

    $table_name = patlis_core_translations_table_name();
    $found      = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));

    $storage_ready = is_string($found) && $found === $table_name;

    return $storage_ready;
}

function patlis_core_get_translation_keys_from_db(): array
{
    global $wpdb;

    if (!patlis_core_translation_storage_ready()) {
        return [];
    }

    $table_name = patlis_core_translations_table_name();
    $keys = $wpdb->get_col("SELECT DISTINCT translation_key FROM {$table_name} ORDER BY translation_key ASC");

    if (!is_array($keys)) {
        return [];
    }

    $keys = array_map(static function ($key): string {
        return patlis_core_normalize_translation_key((string) $key);
    }, $keys);
    $keys = array_filter($keys, 'patlis_core_is_valid_translation_key');

    return array_values(array_unique($keys));
}

function patlis_core_get_translations_from_db(): array
{
    static $translations_cache = null;

    if (is_array($translations_cache)) {
        return $translations_cache;
    }

    global $wpdb;

    if (!patlis_core_translation_storage_ready()) {
        return [];
    }

    $table_name = patlis_core_translations_table_name();
    $rows = $wpdb->get_results("SELECT translation_key, lang, translation_value FROM {$table_name} ORDER BY translation_key ASC, lang ASC", ARRAY_A);

    if (!is_array($rows)) {
        return [];
    }

    $translations = [];

    foreach ($rows as $row) {
        $key   = patlis_core_normalize_translation_key((string) ($row['translation_key'] ?? ''));
        $lang  = patlis_core_normalize_translation_lang((string) ($row['lang'] ?? ''));
        $value = (string) ($row['translation_value'] ?? '');

        if (!patlis_core_is_valid_translation_key($key) || $lang === '' || $lang === patlis_core_manual_translation_lang_marker()) {
            continue;
        }

        $translations[$key][$lang] = $value;
    }

    $translations_cache = $translations;

    return $translations_cache;
}

function patlis_core_get_translation_value(string $key, string $lang): string
{
    global $wpdb;

    $key  = patlis_core_normalize_translation_key($key);
    $lang = patlis_core_normalize_translation_lang($lang);

    if (!patlis_core_translation_storage_ready() || !patlis_core_is_valid_translation_key($key) || $lang === '') {
        return '';
    }

    $table_name = patlis_core_translations_table_name();
    $value = $wpdb->get_var($wpdb->prepare(
        "SELECT translation_value FROM {$table_name} WHERE translation_key = %s AND lang = %s LIMIT 1",
        $key,
        $lang
    ));

    return is_string($value) ? $value : '';
}

function patlis_core_upsert_translation(string $key, string $lang, string $value): bool
{
    global $wpdb;

    $key   = patlis_core_normalize_translation_key($key);
    $lang  = patlis_core_normalize_translation_lang($lang);
    $value = (string) $value;

    if (!patlis_core_is_valid_translation_key($key) || $lang === '') {
        return false;
    }

    patlis_core_maybe_install_translations_table();

    $table_name = patlis_core_translations_table_name();
    $result = $wpdb->replace(
        $table_name,
        [
            'translation_key'   => $key,
            'lang'              => $lang,
            'translation_value' => $value,
        ],
        ['%s', '%s', '%s']
    );

    return $result !== false;
}

function patlis_core_delete_translation_key_from_db(string $key): int
{
    global $wpdb;

    $key = patlis_core_normalize_translation_key($key);

    if (!patlis_core_translation_storage_ready() || !patlis_core_is_valid_translation_key($key)) {
        return 0;
    }

    $table_name = patlis_core_translations_table_name();
    $deleted = $wpdb->delete($table_name, ['translation_key' => $key], ['%s']);

    return $deleted === false ? 0 : (int) $deleted;
}

function patlis_core_delete_translation_row(string $key, string $lang): int
{
    global $wpdb;

    $key  = patlis_core_normalize_translation_key($key);
    $lang = patlis_core_normalize_translation_lang($lang);

    if (!patlis_core_translation_storage_ready() || !patlis_core_is_valid_translation_key($key) || $lang === '') {
        return 0;
    }

    $table_name = patlis_core_translations_table_name();
    $deleted = $wpdb->delete(
        $table_name,
        [
            'translation_key' => $key,
            'lang' => $lang,
        ],
        ['%s', '%s']
    );

    return $deleted === false ? 0 : (int) $deleted;
}

add_action('plugins_loaded', 'patlis_core_maybe_install_translations_table', 5);