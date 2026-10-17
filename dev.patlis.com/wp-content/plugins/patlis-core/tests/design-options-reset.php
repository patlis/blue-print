<?php
/**
 * Standalone regression checks: php tests\design-options-reset.php
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('PATLIS_CORE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

$test_options = [];
$test_registry_additions = [];
$test_allowed = true;
$test_nonce_valid = true;
$test_save_fails = false;
$test_writes = [];
$test_assertions = 0;
$test_posts = [];
$test_deleted_posts = [];
$test_post_delete_fails = false;
$test_term_delete_fails = false;
$test_home_has_images = true;

function get_option($name, $default = []) {
    return $GLOBALS['test_options'][$name] ?? $default;
}

function update_option($name, $value): bool {
    $GLOBALS['test_writes'][] = $name;
    if ($GLOBALS['test_save_fails']) return false;
    if (get_option($name, null) === $value) return false;
    $GLOBALS['test_options'][$name] = $value;
    return true;
}

function sanitize_key($value): string {
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}

function sanitize_text_field($value): string {
    return trim(strip_tags((string) $value));
}

function sanitize_textarea_field($value): string {
    return trim(strip_tags((string) $value));
}

function sanitize_hex_color($value): string {
    return preg_match('/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $value) ? $value : '';
}

function __($text, $domain = ''): string { return $text; }
function esc_html($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value): string { return esc_html($value); }
function esc_url($value): string { return $value; }
function admin_url($value): string { return $value; }
function wp_json_encode($value): string { return json_encode($value); }
function wp_create_nonce($action): string { return 'test-nonce'; }
function current_user_can($capability): bool { return $GLOBALS['test_allowed']; }
function pll_default_language($field = 'slug'): string { return 'en'; }
function pll_languages_list($args = []): array { return ['en', 'el']; }
function patlis_sliders_option_name(): string { return 'patlis_sliders'; }
function add_action(...$args): void {}
function get_posts($args): array { return $GLOBALS['test_posts'][$args['post_type']] ?? []; }
function wp_delete_post($id, $force) {
    $GLOBALS['test_deleted_posts'][] = $id;
    return $GLOBALS['test_post_delete_fails'] ? false : (object) ['ID' => $id];
}
function wp_delete_term($id, $taxonomy) { return $GLOBALS['test_term_delete_fails'] ? false : ['term_id' => $id]; }
function is_wp_error($value): bool { return false; }
function patlis_gallery_get_home_id(): int { return 1; }
function patlis_gallery_meta_key(): string { return 'patlis_gallery_image_ids'; }
function metadata_exists($type, $id, $key): bool { return $GLOBALS['test_home_has_images']; }
function delete_post_meta($id, $key): bool { $GLOBALS['test_home_has_images'] = false; return true; }

function apply_filters($hook, $value) {
    return array_merge($value, $GLOBALS['test_registry_additions'][$hook] ?? []);
}

final class Test_Json_Response extends RuntimeException {
    public $success;
    public $data;

    public function __construct(bool $success, array $data, int $status = 200) {
        parent::__construct($data['message'], $status);
        $this->success = $success;
        $this->data = $data;
    }
}

function wp_send_json_error($data, $status = 200): void {
    throw new Test_Json_Response(false, $data, $status);
}

function wp_send_json_success($data): void {
    throw new Test_Json_Response(true, $data);
}

function check_ajax_referer($action, $field): void {
    if (!$GLOBALS['test_nonce_valid']) wp_send_json_error(['message' => 'Invalid nonce.'], 403);
}

function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function test_reset(string $action = 'reset_design_options'): Test_Json_Response {
    $_POST['tool_action'] = $action;
    try {
        Patlis_Admin_Page_Instance_Tools::handle_ajax();
    } catch (Test_Json_Response $response) {
        return $response;
    }
    throw new RuntimeException('Missing JSON response');
}

require_once PATLIS_CORE_PATH . 'includes' . DIRECTORY_SEPARATOR . 'core.php';
require_once PATLIS_CORE_PATH . 'includes' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'design-options.php';
require_once PATLIS_CORE_PATH . 'includes' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'instance-tools.php';

$registry = array_merge(Patlis_Core::get_design_registry(), Patlis_Core::get_design_pages_registry());
$defaults = Patlis_Admin_Page_Design_Options::defaults();
test_same(count($registry), count($defaults), 'All sections from both real registries');
foreach ($registry as $section_key => $section) {
    test_same($section['default_variant'] ?? 'default', $defaults[$section_key]['variant'], 'Default variant: ' . $section_key);
    $groups = ['' => $section['fields'] ?? []];
    foreach ($section['variants'] ?? [] as $variant_key => $variant) {
        $groups[$variant_key] = $variant['fields'] ?? [];
    }
    foreach ($groups as $variant_key => $fields) {
        foreach ($fields as $field_key => $field) {
            $option_key = $field['option_key'] ?? $field_key;
            $values = $variant_key === '' ? $defaults[$section_key] : $defaults[$section_key][$variant_key];
            test_same(true, array_key_exists($option_key, $values), 'Included field: ' . $section_key . ':' . $variant_key . ':' . $option_key);
            if (isset($field['default']) && is_numeric($field['default']) && in_array($field['type'], ['number', 'range'], true)) {
                test_same((float) $field['default'], $values[$option_key], 'Numeric registry default');
            }
        }
    }
}

$test_registry_additions = [
    'patlis/design_options/registry' => [
        'future_section' => [
            'default_variant' => 'classic',
            'fields' => [
                'title' => ['type' => 'text'],
                'caption' => ['type' => 'text', 'option_key' => 'custom_caption', 'default' => 'Initial'],
                'mode' => ['type' => 'dropdown', 'options' => ['first' => 'First', 'second' => 'Second'], 'default' => 'second'],
                'choice' => ['type' => 'dropdown', 'options' => ['first' => 'First', 'second' => 'Second']],
                'flag' => ['type' => 'checkbox', 'default' => true],
                'color' => ['type' => 'color', 'default' => '#abc'],
                'amount' => ['type' => 'number'],
            ],
            'variants' => [
                'classic' => ['fields' => [
                    'image' => ['type' => 'image'],
                    'opacity' => ['type' => 'range', 'default' => 0.5],
                ]],
                'inactive' => ['fields' => [
                    'body' => ['type' => 'textarea'],
                    'size' => ['type' => 'number', 'default' => 12],
                ]],
            ],
        ],
    ],
    'patlis/design_pages/registry' => [
        'future_page' => [
            'default_variant' => 'editorial',
            'variants' => ['editorial' => ['fields' => ['new_field' => ['type' => 'text']]]],
        ],
    ],
];
$old = [
    'future_section' => [
        'variant' => 'inactive',
        'title' => ['en' => 'Old English', 'el' => 'Old Greek', 'de' => 'Hidden German'],
        'mode' => 'first',
        'choice' => 'second',
        'inactive' => ['body' => ['el' => 'Old body'], 'size' => 900],
        'classic' => ['image' => 1234, 'opacity' => 0.9],
    ],
    'obsolete_section' => ['variant' => 'removed', 'obsolete' => 'Old'],
];
$test_options = [
    Patlis_Core::OPTION_DESIGN_OPTIONS => $old,
    Patlis_Core::OPTION_BASIC => ['company_name' => 'Keep', 'logo_image_id' => 1234],
    'bricks_color_palette' => ['keep'],
    'bricks_global_variables' => ['keep'],
    'test_media' => [1234 => 'keep.webp'],
];
$unrelated = $test_options;
unset($unrelated[Patlis_Core::OPTION_DESIGN_OPTIONS]);
$defaults = Patlis_Admin_Page_Design_Options::defaults();
test_same($old, get_option(Patlis_Core::OPTION_DESIGN_OPTIONS), 'Building defaults must not change stored settings');
test_same('classic', $defaults['future_section']['variant'], 'Future section default variant');
test_same(['en' => ''], $defaults['future_section']['title'], 'Old values in all languages cleared');
test_same(['en' => 'Initial'], $defaults['future_section']['custom_caption'], 'Field option_key and default text');
test_same('second', $defaults['future_section']['mode'], 'Dropdown explicit default');
test_same('first', $defaults['future_section']['choice'], 'Dropdown initial valid choice');
test_same(1, $defaults['future_section']['flag'], 'Checkbox default');
test_same('#abc', $defaults['future_section']['color'], 'Color default');
test_same(0, $defaults['future_section']['amount'], 'Number without default');
test_same(0, $defaults['future_section']['classic']['image'], 'Image reference reset');
test_same(0.5, $defaults['future_section']['classic']['opacity'], 'Range default preserved');
test_same(['en' => ''], $defaults['future_section']['inactive']['body'], 'Inactive variant translations cleared');
test_same(12.0, $defaults['future_section']['inactive']['size'], 'Inactive variant field default');
test_same('editorial', $defaults['future_page']['variant'], 'Future page registry default');
test_same(['en' => ''], $defaults['future_page']['editorial']['new_field'], 'Future page field discovered');
test_same(false, isset($defaults['obsolete_section']), 'Obsolete settings excluded');

$saved_form = Patlis_Admin_Page_Design_Options::sanitize([
    'future_section' => ['variant' => 'inactive', 'title' => ['en' => 'Updated'], 'mode' => 'first'],
]);
test_same('inactive', $saved_form['future_section']['variant'], 'Normal saving keeps selected variant');
test_same(['en' => 'Updated', 'el' => 'Old Greek', 'de' => 'Hidden German'], $saved_form['future_section']['title'], 'Normal saving preserves other translations');
test_same('first', $saved_form['future_section']['mode'], 'Normal saving keeps submitted dropdown choice');
test_same($old, get_option(Patlis_Core::OPTION_DESIGN_OPTIONS), 'Sanitizing does not perform a reset');

$response = test_reset();
test_same(true, $response->success, 'AJAX reset success');
test_same($defaults, get_option(Patlis_Core::OPTION_DESIGN_OPTIONS), 'Defaults persisted');
$remaining = $test_options;
unset($remaining[Patlis_Core::OPTION_DESIGN_OPTIONS]);
test_same($unrelated, $remaining, 'Other settings and media untouched');
test_same([Patlis_Core::OPTION_DESIGN_OPTIONS], $test_writes, 'Only design option updated');
test_same(true, test_reset()->success, 'Already reset is success');
test_same(1, count($test_writes), 'Already reset needs no write');

$test_options[Patlis_Core::OPTION_DESIGN_OPTIONS] = $old;
$test_save_fails = true;
test_same(500, test_reset()->getCode(), 'Save failure reported');
test_same($old, get_option(Patlis_Core::OPTION_DESIGN_OPTIONS), 'Save failure retains old settings');
$test_save_fails = false;
$writes = count($test_writes);
$test_allowed = false;
test_same(403, test_reset()->getCode(), 'Admin permission required');
$test_allowed = true;
$test_nonce_valid = false;
test_same(403, test_reset()->getCode(), 'Valid nonce required');
$test_nonce_valid = true;
test_same($writes, count($test_writes), 'Unauthorized calls do not write');
unset($test_options[Patlis_Core::OPTION_DESIGN_OPTIONS]);
test_same(true, test_reset()->success, 'Reset with no saved design settings');
test_same($defaults, get_option(Patlis_Core::OPTION_DESIGN_OPTIONS), 'Fresh site persists defaults');

$wpdb = new class {
    public $posts = 'test_posts';
    public $term_taxonomy = 'test_term_taxonomy';
    public $prefix = 'test_';
    public $table_exists = false;
    public $delete_fails = false;
    public $term_ids = [];
    public function prepare($sql, ...$args) { return $sql; }
    public function get_var($sql) {
        if (strpos($sql, 'SHOW TABLES') === 0) return $this->table_exists ? 'test_patlis_leads' : null;
        return 0;
    }
    public function query($sql) { return $this->delete_fails ? false : 0; }
    public function get_col($sql) { return $this->term_ids; }
};
ob_start();
Patlis_Admin_Page_Instance_Tools::render();
$html = ob_get_clean();
test_same(true, preg_match('/data-action="reset_design_options"[^>]*data-label="Reset Design Options"[^>]*data-count="' . count($defaults) . '"/s', $html) === 1, 'Button with automatic section count');
test_same(true, strpos($html, 'This will reset the design settings for') !== false, 'Reset-specific confirmation');
test_same(true, strpos($html, '<h2>Content</h2>') < strpos($html, 'data-action="reset_design_options"'), 'Reset button under Content');
test_same(true, strpos($html, 'id="patlis-instance-tools-run-all"') !== false, 'Run All button rendered');
$definition = new ReflectionMethod(Patlis_Admin_Page_Instance_Tools::class, 'definition');
$tool_count = array_sum(array_map('count', $definition->invoke(null))) + 1;
test_same($tool_count, preg_match_all('/data-action="/', $html), 'All registered cleanup tools and SEO rendered');

$test_posts = ['patlis_gallery' => [1, 2], 'gallery' => [3]];
test_same(true, test_reset('delete_gallery')->success, 'Gallery cleanup succeeds with protected home');
test_same([2, 3], $test_deleted_posts, 'Home gallery is not passed to deletion');
test_same(false, $test_home_has_images, 'Home images cleared');
$test_posts = ['services' => [10]];
$test_post_delete_fails = true;
test_same(500, test_reset('delete_services')->getCode(), 'Post deletion failure reported');
$test_post_delete_fails = false;
$wpdb->term_ids = [20];
$test_term_delete_fails = true;
test_same(500, test_reset('delete_menu_categories')->getCode(), 'Term deletion failure reported');
$test_term_delete_fails = false;
$wpdb->term_ids = null;
test_same(500, test_reset('delete_menu_categories')->getCode(), 'Term query failure reported');
$wpdb->term_ids = [];
$wpdb->table_exists = true;
$wpdb->delete_fails = true;
test_same(500, test_reset('delete_table_patlis_leads')->getCode(), 'Table deletion failure reported');
$wpdb->delete_fails = false;
$wpdb->table_exists = false;
test_same(true, test_reset('delete_table_patlis_leads')->success, 'Missing optional plugin table is skipped');
test_same(400, test_reset('set_page_language_indexing')->getCode(), 'Missing SEO prerequisite reported as failure');

if (in_array('--render', $argv, true)) {
    echo $html;
} else {
    echo "PASS: {$test_assertions} design reset and cleanup checks.\n";
}
