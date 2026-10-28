<?php
/** Standalone regression checks: php tests\design-sets.php */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('ABSPATH', __DIR__);
define('PATLIS_CORE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('PATLIS_CORE_URL', 'https://example.test/patlis-core/');
$options = [];
$filters = [];
$actions = [];
$fail_option = '';
$assertions = 0;
$thickbox_calls = 0;
function __($text, $domain = '') { return $text; }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return esc_html($value); }
function esc_url($value) { return $value; }
function esc_html_e($text, $domain = ''): void { echo esc_html($text); }
function esc_html__($text, $domain = '') { return esc_html($text); }
function admin_url($path) { return $path; }
function wp_nonce_field($action): void {}
function submit_button($text, ...$args): void { echo '<button type="submit">' . esc_html($text) . '</button>'; }
function wp_json_encode($value) { return json_encode($value); }
function add_thickbox(): void { $GLOBALS['thickbox_calls']++; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function get_option($key, $default = []) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key, $value) {
    if ($GLOBALS['fail_option'] === $key || get_option($key, null) === $value) return false;
    $GLOBALS['options'][$key] = $value;
    return true;
}
function apply_filters($hook, $value) { return $GLOBALS['filters'][$hook] ?? $value; }
function add_action(...$args): void {}
function do_action(...$args): void { $GLOBALS['actions'][] = $args; }
function allowed_section($slug) { return $slug === 'blocked' ? 0 : 1; }
function is_wp_error($value) { return $value instanceof WP_Error; }
final class WP_Error {
    private $code;
    public function __construct($code, $message) { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}
function same($expected, $actual, $label): void {
    $GLOBALS['assertions']++;
    if ($expected !== $actual) throw new RuntimeException($label . ': ' . var_export($actual, true));
}
require PATLIS_CORE_PATH . 'includes\core.php';
require PATLIS_CORE_PATH . 'includes\design-colors.php';
require PATLIS_CORE_PATH . 'includes\admin\bricks-colors.php';
require PATLIS_CORE_PATH . 'includes\admin\pages\design-options.php';
$variant = ['variants' => ['classic' => [], 'cinematic' => []]];
$filters['patlis/design_options/registry'] = [
    'hero' => $variant,
    'blocked' => $variant + ['availability' => 'blocked'],
];
$filters['patlis/design_pages/registry'] = [
    'about' => $variant,
    'private_page' => $variant + ['availability' => 'blocked'],
];
$filters['patlis/color_palettes/registry'] = [
    'test' => ['colors' => ['--body' => '#123456', '--shadow' => 'hsla(0, 0%, 0%, 0.3)']],
    'bad' => ['colors' => ['--body' => 'invalid']],
    'unknown_variable' => ['colors' => ['--unknown' => '#fff']],
];
$set = ['sections' => ['hero' => 'cinematic', 'blocked' => 'cinematic'],
    'pages' => ['about' => 'cinematic', 'private_page' => 'cinematic'], 'color_set' => 'test'];
$filters['patlis/design_sets/registry'] = ['test' => $set];
$options = [
    Patlis_Core::OPTION_DESIGN_OPTIONS => [
        'hero' => ['variant' => 'classic', 'title' => ['en' => 'Keep'], 'cinematic' => ['image' => 42]],
        'about' => ['variant' => 'classic', 'classic' => ['description' => 'Keep']],
        'other' => ['variant' => 'classic'],
    ],
    'bricks_color_palette' => [
        ['id' => 'other', 'colors' => [['light' => '#999']]],
        ['id' => 'xgdckv', 'name' => 'Keep palette', 'colors' => [
            ['id' => 'a', 'raw' => 'var(--body)', 'light' => '#fff'],
            ['id' => 'b', 'raw' => 'var(--shadow)', 'light' => '#000'],
            ['id' => 'c', 'raw' => 'var(--body)', 'parent' => 'a', 'light' => '#aaa'],
        ]],
    ],
];
$original = $options;
same(2, Patlis_Core::apply_design_set('test'), 'Section and page variant count');
same('cinematic', $options[Patlis_Core::OPTION_DESIGN_OPTIONS]['hero']['variant'], 'Section selection');
same('cinematic', $options[Patlis_Core::OPTION_DESIGN_OPTIONS]['about']['variant'], 'Page selection');
$expected = $original[Patlis_Core::OPTION_DESIGN_OPTIONS];
$expected['hero']['variant'] = $expected['about']['variant'] = 'cinematic';
same($expected, $options[Patlis_Core::OPTION_DESIGN_OPTIONS], 'Fields, other sections and restricted entries preserved');
same('#123456', $options['bricks_color_palette'][1]['colors'][0]['light'], 'Named palette color');
same('hsla(0, 0%, 0%, 0.3)', $options['bricks_color_palette'][1]['colors'][1]['light'], 'CSS function color');
same($original['bricks_color_palette'][0], $options['bricks_color_palette'][0], 'Other palettes preserved');
same($original['bricks_color_palette'][1]['colors'][2], $options['bricks_color_palette'][1]['colors'][2], 'Derived colors preserved');
same(2, Patlis_Core::apply_design_set('test'), 'Reapplying unchanged set succeeds');
foreach ([
    ['pages' => ['missing' => 'classic']],
    ['pages' => ['about' => 'default']],
    ['pages' => ['about' => []]],
    ['sections' => 'bad'],
    ['color_set' => 'missing'],
    ['color_set' => 'bad'],
    ['color_set' => 'unknown_variable'],
    ['color_set' => []],
] as $changes) {
    $options = $original;
    $actions = [];
    $filters['patlis/design_sets/registry']['test'] = array_replace($set, $changes);
    same(true, is_wp_error(Patlis_Core::apply_design_set('test')), 'Invalid set rejected');
    same($original, $options, 'Validation error leaves all options unchanged');
    same([], $actions, 'Validation error does not emit success hooks');
}
$filters['patlis/design_sets/registry']['test'] = $set;
$options = $original;
unset($options['bricks_color_palette']);
$before = $options;
same('palette_not_found', Patlis_Core::apply_design_set('test')->get_error_code(), 'Missing Bricks palette rejected');
same($before, $options, 'Missing palette leaves variants unchanged');
$options = $original;
$fail_option = 'bricks_color_palette';
same('colors_save_failed', Patlis_Core::apply_design_set('test')->get_error_code(), 'Failed color save surfaced');
same($original, $options, 'Failed color save leaves variants unchanged');
$fail_option = Patlis_Core::OPTION_DESIGN_OPTIONS;
same('design_set_save_failed', Patlis_Core::apply_design_set('test')->get_error_code(), 'Failed variant save explicitly surfaced');
$fail_option = '';
$options = $original;
$filters['patlis/design_sets/registry']['test'] = ['sections' => ['hero' => 'cinematic']];
same(1, Patlis_Core::apply_design_set('test'), 'Legacy sections-only set');
same($original['bricks_color_palette'], $options['bricks_color_palette'], 'Legacy set does not touch colors');
$filters['patlis/design_sets/registry']['test'] = ['pages' => ['about' => 'cinematic']];
same(1, Patlis_Core::apply_design_set('test'), 'Pages-only set');
$filters['patlis/design_sets/registry']['test'] = ['color_set' => 'test'];
same(0, Patlis_Core::apply_design_set('test'), 'Colors-only set');
$filters['patlis/design_sets/registry']['test'] = [];
same('empty_design_set', Patlis_Core::apply_design_set('test')->get_error_code(), 'Empty set rejected');
same('design_set_not_found', Patlis_Core::apply_design_set('missing')->get_error_code(), 'Unknown set rejected');
$filters['patlis/design_sets/registry'] = [
    'with-preview' => ['label' => 'Preview "test"', 'preview' => 'assets/design-previews/header/classic.webp'],
    'missing-preview' => ['label' => 'Missing', 'preview' => 'assets/design-previews/not-found.webp'],
    'without-preview' => ['label' => 'Without'],
];
$before_render = $options;
$render = Closure::bind(static function (): void {
    Patlis_Admin_Page_Design_Options::render_design_sets_tab();
}, null, Patlis_Admin_Page_Design_Options::class);
ob_start();
$render();
$html = ob_get_clean();
same(1, $thickbox_calls, 'WordPress ThickBox assets requested for sets');
same(1, substr_count($html, 'class="thickbox patlis-design-set-preview-link"'), 'Only existing previews are clickable');
same(true, strpos($html, 'href="' . PATLIS_CORE_URL . 'assets/design-previews/header/classic.webp"') !== false, 'Link opens original full-size preview');
same(true, strpos($html, 'title="Preview &quot;test&quot;"') !== false, 'Popup caption escaped');
same(true, strpos($html, 'alt="Preview &quot;test&quot;"') !== false, 'Preview link accessible name');
same(3, substr_count($html, 'class="patlis-apply-design-set-form"'), 'Apply forms retained separately from preview links');
same($before_render, $options, 'Rendering previews does not apply design sets');
$filters['patlis/design_sets/registry'] = [];
ob_start();
$render();
ob_end_clean();
same(1, $thickbox_calls, 'Empty sets do not request ThickBox');
echo "PASS: {$assertions} design set checks.\n";
