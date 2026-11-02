<?php
/** Standalone regression checks: php tests\item-number-padding.php */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('ABSPATH', __DIR__);
$meta = [];
$assertions = 0;
function add_action(...$args): void {}
function add_filter(...$args): void {}
function current_user_can(...$args): bool { return true; }
function wp_verify_nonce(...$args): bool { return true; }
function sanitize_text_field($value): string {
    return trim(preg_replace('/[\r\n\t ]+/', ' ', strip_tags((string) $value)));
}
function sanitize_textarea_field($value): string { return trim(strip_tags((string) $value)); }
function wp_kses($value, $allowed): string { return strip_tags($value); }
function wp_slash($value) {
    return is_array($value) ? array_map('wp_slash', $value) : addslashes($value);
}
function wp_unslash($value) {
    return is_array($value) ? array_map('wp_unslash', $value) : stripslashes($value);
}
function update_post_meta($id, $key, $value): void { $GLOBALS['meta'][$id][$key] = wp_unslash($value); }
function delete_post_meta($id, $key): void { unset($GLOBALS['meta'][$id][$key]); }
function get_post_type($id): string { return 'menu_item'; }
function wp_update_post($args, $error) { return $args['ID']; }
function wp_insert_post($args, $error) { return 2; }
function wp_set_object_terms(...$args): void {}
function term_exists(...$args): bool { return true; }
function is_wp_error($value): bool { return false; }
final class WP_Post {}
function same($expected, $actual, $label): void {
    $GLOBALS['assertions']++;
    if ($expected !== $actual) throw new RuntimeException($label . ': ' . var_export($actual, true));
}
$includes = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
require $includes . 'menu-items.php';
require $includes . 'admin' . DIRECTORY_SEPARATOR . 'bulk-edit.php';
require $includes . 'admin' . DIRECTORY_SEPARATOR . 'bulk-import.php';
$bulk_save = new ReflectionMethod(Patlis_Menu_Admin_Bulk_Edit::class, 'handle_save');
$import = new ReflectionMethod(Patlis_Menu_Admin_Import::class, 'handle_import');
$csv = tempnam(sys_get_temp_dir(), 'patlis-item-padding-');
try {
    foreach ([
        [' 9', ' 9'],
        ['   9', '   9'],
        ['10', '10'],
        [' M 01', ' M 01'],
        [' 9  ', ' 9'],
        [' <b>9</b>', ' 9'],
        [' ', ''],
        ['', ''],
        [" A\\9's", " A\\9's"],
    ] as [$input, $expected]) {
        same($expected, patlis_menu_sanitize_item_number($input), 'Sanitizer preserves only intentional left padding');
        $_POST = ['patlis_menu_item_nonce' => 'valid', 'pmi_itemnr' => wp_slash($input)];
        patlis_menu_items_save(1, new WP_Post());
        same($expected, $meta[1]['pmi_itemnr'], 'Single item save round trip');
        $row = array_fill_keys(['sort', 'size1', 'price', 'size2', 'price2', 'size3', 'price3'], '');
        $row += ['post_id' => '1', 'itemnr' => $input, 'name' => 'Test dish'];
        $_POST = [
            'patlis_menu_bulk_edit_nonce' => 'valid',
            'patlis_menu_bulk_edit_complete' => '1',
            'patlis_menu_bulk_edit_payload' => wp_slash(json_encode([$row])),
        ];
        same(1, $bulk_save->invoke(null)['updated'], 'Bulk save succeeds');
        same($expected, $meta[1]['pmi_itemnr'], 'Bulk edit round trip');
        $header = 'category-id;item-nr;name;description;allergies;price;price-2;price-3;size-1;size-2;size-3';
        file_put_contents($csv, $header . "\n1;" . $input . ";Dish;Description;;10;;;;;\n");
        $_POST = ['patlis_menu_import_nonce' => 'valid'];
        $_FILES = ['patlis_menu_import_file' => ['error' => UPLOAD_ERR_OK, 'tmp_name' => $csv]];
        same(1, $import->invoke(null)['imported'], 'CSV import succeeds');
        same($expected, $meta[2]['pmi_itemnr'], 'CSV import round trip');
    }
} finally {
    unlink($csv);
}
echo "PASS: {$assertions} item number padding checks.\n";
