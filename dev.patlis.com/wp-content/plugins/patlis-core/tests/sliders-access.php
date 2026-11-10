<?php
/**
 * Slides use edit_posts, like the default Gallery post type, not manage_options.
 * Standalone regression checks: php tests\sliders-access.php
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);

$test_actions = [];
$test_caps = [];
$test_menu = [];
$test_options = ['home' => ['slides' => [['id' => 'existing']]]];
$test_nonce_checks = 0;
$test_updates = 0;
$test_assertions = 0;

function add_action($hook, $callback): void { $GLOBALS['test_actions'][$hook] = $callback; }
function add_menu_page(...$args): void { $GLOBALS['test_menu'][$args[3]] = $args; }
function current_user_can($capability): bool { return !empty($GLOBALS['test_caps'][$capability]); }
function __($text, $domain = ''): string { return $text; }
function esc_html__($text, $domain = ''): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function wp_die($message): void { throw new RuntimeException($message); }
function patlis_sliders_get_layouts(): array { return []; }
function patlis_sliders_get_registry(): array { return ['default_layout' => 'slider-floating-box', 'layouts' => []]; }
function patlis_sliders_get_languages(): array { return ['en' => 'English']; }
function patlis_sliders_option_name(): string { return 'patlis_sliders'; }
function get_option($name, $default = []) { return $GLOBALS['test_options']; }
function update_option($name, $value, $autoload): void {
    $GLOBALS['test_options'] = $value;
    $GLOBALS['test_updates']++;
}
function check_admin_referer($action, $field): void {
    test_same('patlis_save_sliders', $action, 'Save nonce action');
    test_same('patlis_sliders_nonce', $field, 'Save nonce field');
    $GLOBALS['test_nonce_checks']++;
}
function admin_url($path): string { return $path; }
function add_query_arg($args, $url): string { return $url . '?' . http_build_query($args); }

final class Test_Sliders_Redirect extends RuntimeException {}

function wp_safe_redirect($url): void { throw new Test_Sliders_Redirect($url); }

function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'sliders' . DIRECTORY_SEPARATOR . 'sliders-admin.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'sliders' . DIRECTORY_SEPARATOR . 'sliders-save.php';

$test_actions['admin_menu']();
test_same('edit_posts', $test_menu['patlis-slides'][2], 'Menu uses Gallery default capability');

$_POST = [];
$saved_options = $test_options;

foreach ([
    'site owner' => ['edit_posts' => true, 'patlis_manage' => true],
    'administrator' => ['edit_posts' => true, 'manage_options' => true],
    'content user' => ['edit_posts' => true],
] as $label => $caps) {
    $test_caps = $caps;
    test_same(true, current_user_can($test_menu['patlis-slides'][2]), $label . ' sees menu');
    $redirect = '';
    try {
        $test_actions['admin_post_patlis_save_sliders']();
    } catch (Test_Sliders_Redirect $error) {
        $redirect = $error->getMessage();
    }
    test_same('admin.php?page=patlis-slides&updated=1&lang=all', $redirect, $label . ' can save');
    test_same($saved_options, $test_options, $label . ' preserves unposted groups');
}

test_same(3, $test_nonce_checks, 'Authorized saves still check nonce');
test_same(3, $test_updates, 'Authorized saves update options');

foreach ([
    'subscriber' => ['read' => true],
    'anonymous' => [],
    'Patlis-only user' => ['patlis_manage' => true],
] as $label => $caps) {
    $test_caps = $caps;
    test_same(false, current_user_can($test_menu['patlis-slides'][2]), $label . ' cannot see menu');
    foreach ([
        'render' => 'patlis_render_slides_admin',
        'save' => $test_actions['admin_post_patlis_save_sliders'],
    ] as $action => $callback) {
        $message = '';
        try {
            $callback();
        } catch (RuntimeException $error) {
            $message = $error->getMessage();
        }
        test_same('You are not allowed to perform this action.', $message, $label . ' cannot ' . $action);
    }
}

test_same(3, $test_nonce_checks, 'Unauthorized requests stop before nonce check');
test_same(3, $test_updates, 'Unauthorized requests cannot update options');
echo 'Passed ' . $test_assertions . " assertions.\n";
