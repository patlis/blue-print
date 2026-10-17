<?php
/**
 * Standalone regression checks: php tests\homepage-settings.php
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('PATLIS_CORE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

$test_options = [];
$test_filters = [];
$test_assertions = 0;
$test_allowed = true;
$test_actions = [];
$test_menu = [];
$test_blocked_sections = [];

function get_option($name, $default = []) { return $GLOBALS['test_options'][$name] ?? $default; }
function update_option($name, $value): bool { $GLOBALS['test_options'][$name] = $value; return true; }
function current_user_can($capability): bool { return $GLOBALS['test_allowed']; }
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function __($text, $domain = ''): string { return $text; }
function esc_html($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value): string { return esc_html($value); }
function esc_html_e($text, $domain = ''): void { echo esc_html($text); }
function esc_js($value): string { return $value; }
function esc_url($value): string { return $value; }
function esc_url_raw($value): string { return $value; }
function wp_unslash($value) { return is_array($value) ? array_map('wp_unslash', $value) : stripslashes($value); }
function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function admin_url($path): string { return $path; }
function add_query_arg($args, $url): string { return $url . '?' . http_build_query($args); }
function wp_nonce_field($action): void {}
function check_admin_referer($action): void {}
function wp_enqueue_media(): void {}
function wp_enqueue_script(...$args): void {}
function wp_json_encode($value): string { return json_encode($value); }
function checked($value, $expected = true, $echo = true): string {
    $result = $value == $expected ? ' checked="checked"' : '';
    if ($echo) echo $result;
    return $result;
}
function selected($value, $expected, $echo = true): string {
    $result = $value === $expected ? ' selected="selected"' : '';
    if ($echo) echo $result;
    return $result;
}
function submit_button($text): void { echo '<button type="submit">' . esc_html($text) . '</button>'; }
function add_action($hook, $callback, ...$args): void { $GLOBALS['test_actions'][$hook] = $callback; }
function do_action(...$args): void {}
function add_menu_page(...$args): void { $GLOBALS['test_menu'][] = $args[3]; }
function add_submenu_page(...$args): void { $GLOBALS['test_menu'][] = $args[4]; }
function allowed_section($slug): int { return in_array($slug, $GLOBALS['test_blocked_sections'], true) ? 0 : 1; }
function is_wp_error($value): bool { return $value instanceof WP_Error; }
final class WP_Error {
    private $code;
    private $message;
    public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void {
    $GLOBALS['test_filters'][$hook][$priority][] = [$callback, $accepted_args];
}
function apply_filters($hook, $value, ...$args) {
    $callbacks = $GLOBALS['test_filters'][$hook] ?? [];
    ksort($callbacks);
    foreach ($callbacks as $group) {
        foreach ($group as [$callback, $accepted_args]) {
            $value = $callback(...array_slice([$value, ...$args], 0, $accepted_args));
        }
    }
    return $value;
}
function wp_die($message): void { throw new RuntimeException($message); }

final class Test_Homepage_Redirect extends RuntimeException {}

function wp_safe_redirect($url): void {
    throw new Test_Homepage_Redirect($url);
}

function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

foreach ([
    ['core.php'],
    ['helpers.php'],
    ['bricks-tags.php'],
    ['admin', 'bricks-variables.php'],
    ['admin', 'settings.php'],
    ['admin', 'menu.php'],
    ['admin', 'pages', 'design-options.php'],
] as $parts) {
    require_once PATLIS_CORE_PATH . 'includes' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
}

$variable_input = [];
$variable_data = [];
foreach (Patlis_Bricks_Variables::get_variable_definitions() as $name => $definition) {
    $variable_input[$name] = $definition['default'];
    $variable_data[] = ['name' => $name, 'value' => $definition['default'] . 'px'];
}
foreach (['font-family', 'headers-font-family'] as $name) {
    $variable_input[$name] = Patlis_Bricks_Variables::get_font_options()[0];
    $variable_data[] = ['name' => $name, 'value' => '"' . $variable_input[$name] . '"'];
}
$test_options = [
    Patlis_Core::OPTION_HOMEPAGE => [
        'cta_bg_image_id' => 99,
        'welcome_video_url' => 'https://example.test/welcome.mp4',
        'sections_order' => ['cta', 'welcome'],
        'sections_visibility' => ['cta' => 1, 'welcome' => 0],
    ],
    Patlis_Core::OPTION_DESIGN_OPTIONS => ['cta' => ['variant' => 'classic', 'classic' => ['image' => 77]]],
    'test_media' => [99 => 'old-background.webp'],
    'bricks_global_variables' => $variable_data,
];
$original = $test_options;

ob_start();
Patlis_Admin_Page_Design_Options::render();
$html = ob_get_clean();
test_same(false, strpos($html, 'CTA background') !== false, 'Legacy CTA section removed');
test_same(false, strpos($html, 'patlis_cta_bg') !== false, 'Legacy CTA inputs and picker removed');
test_same(true, strpos($html, 'Welcome section video') !== false, 'Welcome video section retained');
test_same(true, strpos($html, 'value="https://example.test/welcome.mp4"') !== false, 'Welcome URL still rendered');
test_same(true, strpos($html, "library: { type: 'video' }") !== false, 'Welcome video picker retained');
test_same(true, strpos($html, 'value="patlis_save_design_global"') !== false, 'Video uses Global & Home page form');
test_same(true, strpos($html, 'Welcome section video') < strpos($html, 'Home page display order'), 'Video and display-order headings remain distinct');
preg_match_all('/class="patlis-section-order-input"[^>]*value="([^"]+)"/', $html, $order_matches);
$expected_order = array_merge(['cta', 'welcome'], array_values(array_diff(Patlis_Core::HOME_SECTIONS, ['cta', 'welcome'])));
test_same($expected_order, $order_matches[1], 'Homepage order retained without old page class');
test_same($original, $test_options, 'Rendering does not alter options or delete media');
test_same(false, class_exists('Patlis_Admin_Page_Homepage'), 'Old admin page class removed');
test_same(false, file_exists(PATLIS_CORE_PATH . 'includes' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'homepage.php'), 'Old page file removed');
test_same(false, method_exists(Patlis_Admin_Settings::class, 'handle_save_homepage'), 'Old save handler removed');
Patlis_Admin_Settings::init();
test_same(false, isset($test_actions['admin_post_patlis_save_homepage']), 'Old save action removed');
test_same(true, isset($test_actions['admin_post_patlis_save_design_global']), 'Global save action registered');
Patlis_Admin_Menu::register();
test_same(false, in_array('patlis-homepage', $test_menu, true), 'Old page menu removed');
test_same(true, in_array('patlis-design-options', $test_menu, true), 'Design menu retained');
test_same(false, function_exists('patlis_get_cta_bg_image_ai_status'), 'Legacy CTA helper removed');
test_same(true, function_exists('patlis_get_center_image_ai_status'), 'Center Popup helper retained');

$tags = array_column(apply_filters('bricks/dynamic_tags_list', []), 'name');
foreach (['patlis_cta_bg_image_id', 'patlis_cta_bg_image_url', 'patlis_cta_bg_image_url_ai_status'] as $tag) {
    test_same(false, in_array('{' . $tag . '}', $tags, true), 'Removed tag not registered: ' . $tag);
}
test_same(true, in_array('{patlis_home_video_url}', $tags, true), 'Welcome video tag retained');
test_same('https://example.test/welcome.mp4', patlis_get_welcome_video_url(), 'Welcome URL helper retained');
test_same('https://example.test/welcome.mp4', patlis_render_dynamic_tags_in_content('{patlis_home_video_url}'), 'Welcome content tag retained');
test_same('https://example.test/welcome.mp4', apply_filters('bricks/dynamic_data/render_tag', '{patlis_home_video_url}', null), 'Welcome direct tag retained');

$_POST = [
    'patlis_welcome_video_url' => 'https://example.test/new-welcome.mp4',
    'patlis_cta_bg_image_id' => 100,
    'patlis_bricks_variables' => $variable_input,
    'patlis_sections_order' => ['cta', 'welcome'],
];
try {
    Patlis_Admin_Settings::handle_save_design_global();
    throw new RuntimeException('Expected save redirect');
} catch (Test_Homepage_Redirect $redirect) {
    test_same('admin.php?page=patlis-design-options&design_type=global&patlis_saved=1', $redirect->getMessage(), 'Save returns to Global & Home page');
}
$expected = $original;
unset($expected[Patlis_Core::OPTION_HOMEPAGE]['cta_bg_image_id']);
$expected[Patlis_Core::OPTION_HOMEPAGE]['welcome_video_url'] = 'https://example.test/new-welcome.mp4';
$expected[Patlis_Core::OPTION_HOMEPAGE]['sections_order'] = $expected_order;
$expected[Patlis_Core::OPTION_HOMEPAGE]['sections_visibility'] = array_fill_keys(Patlis_Core::HOME_SECTIONS, 1);
$expected[Patlis_Core::OPTION_HOMEPAGE]['sections_visibility']['welcome'] = 0;
test_same($expected, $test_options, 'Save discards legacy CTA value and ignores old submitted input without touching design CTA or media');
test_same('https://example.test/new-welcome.mp4', patlis_get_welcome_video_url(), 'Updated welcome URL returned');
test_same('1', patlis_home_video_has_url(), 'Welcome video availability helper retained');

unset($_POST['patlis_welcome_video_url']);
try {
    Patlis_Admin_Settings::handle_save_design_global();
} catch (Test_Homepage_Redirect $redirect) {}
test_same('https://example.test/new-welcome.mp4', patlis_get_welcome_video_url(), 'Missing video input preserves existing value');
$_POST['patlis_welcome_video_url'] = '';
try {
    Patlis_Admin_Settings::handle_save_design_global();
} catch (Test_Homepage_Redirect $redirect) {}
test_same('', patlis_get_welcome_video_url(), 'Empty video input clears value');
test_same('0', patlis_home_video_has_url(), 'Empty video disables video availability');

$test_blocked_sections = ['top-rooms-section'];
ob_start();
Patlis_Admin_Page_Design_Options::render();
$restricted_html = ob_get_clean();
test_same(false, strpos($restricted_html, 'data-slug="rooms"') !== false, 'Version-restricted homepage section remains hidden');
test_same(true, strpos($restricted_html, 'data-slug="welcome"') !== false, 'Common homepage section retained');
$test_blocked_sections = [];

$test_allowed = false;
ob_start();
Patlis_Admin_Page_Design_Options::render();
test_same('', ob_get_clean(), 'Unauthorized user cannot render settings');
$before = $test_options;
try {
    Patlis_Admin_Settings::handle_save_design_global();
    throw new RuntimeException('Unauthorized save should fail');
} catch (RuntimeException $error) {
    test_same('Not allowed.', $error->getMessage(), 'Save capability retained');
}
test_same($before, $test_options, 'Unauthorized save does not alter options');

if (in_array('--render', $argv, true)) {
    echo $html;
} else {
    echo "PASS: {$test_assertions} homepage settings checks.\n";
}
