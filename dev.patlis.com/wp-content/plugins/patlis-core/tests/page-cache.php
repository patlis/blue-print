<?php
/**
 * Standalone regression checks: php tests\page-cache.php
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);

$test_actions = [];
$test_caps = [];
$test_nonce_valid = true;
$test_nonce_checks = 0;
$test_assertions = 0;

function add_action($hook, $callback, $priority = 10): void { $GLOBALS['test_actions'][$hook] = $callback; }
function current_user_can($capability): bool { return !empty($GLOBALS['test_caps'][$capability]); }
function esc_html__($text, $domain = ''): string { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_html_e($text, $domain = ''): void { echo esc_html__($text, $domain); }
function admin_url($path): string { return 'https://example.test/wp-admin/' . $path; }
function add_query_arg($key, $value, $url): string {
    return $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query([$key => $value]);
}
function wp_nonce_url($url, $action): string {
    test_same('patlis_purge_page_cache', $action, 'Toolbar nonce action');
    return add_query_arg('_wpnonce', 'valid-nonce', $url);
}
function check_admin_referer($action): void {
    test_same('patlis_purge_page_cache', $action, 'Handler nonce action');
    $GLOBALS['test_nonce_checks']++;
    if (!$GLOBALS['test_nonce_valid']) wp_die('Invalid nonce.', '', ['response' => 403]);
}

final class Test_Cache_Die extends RuntimeException {}
final class Test_Cache_Redirect extends RuntimeException {}

function wp_die($message, $title = '', $args = []): void {
    throw new Test_Cache_Die($message, $args['response'] ?? 500);
}
function wp_safe_redirect($url): void { throw new Test_Cache_Redirect($url); }

final class WP_Admin_Bar {
    public $nodes = [];
    public function add_node($node): void { $this->nodes[$node['id']] = $node; }
}

function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function test_cache_menu(): array {
    $bar = new WP_Admin_Bar();
    $GLOBALS['test_actions']['admin_bar_menu']($bar);
    return $bar->nodes;
}

function test_cache_failure(int $status, string $message): void {
    $caught = false;
    try {
        $GLOBALS['test_actions']['admin_post_patlis_purge_page_cache']();
    } catch (Test_Cache_Die $error) {
        $caught = true;
        test_same($status, $error->getCode(), 'Failure HTTP status');
        test_same($message, $error->getMessage(), 'Explicit failure message');
    }
    test_same(true, $caught, 'Failure stops request');
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'page-cache.php';

$test_caps = ['edit_posts' => true];
test_same(null, patlis_get_page_cache(), 'Inactive WP-Optimize is safe');
test_same([], test_cache_menu(), 'No toolbar link without WP-Optimize');
test_cache_failure(503, 'WP-Optimize page caching is not available or is disabled.');

// Declare the optional API only after exercising the missing-plugin path.
if (!function_exists('WP_Optimize')) {
    function WP_Optimize() { return $GLOBALS['test_optimize']; }
}

final class WPO_Page_Cache {
    public $enabled = true;
    public $result = true;
    public $purges = 0;
    public function is_enabled(): bool { return $this->enabled; }
    public function purge(): bool { $this->purges++; return $this->result; }
}

$test_cache = new WPO_Page_Cache();
$test_optimize = new class($test_cache) {
    private $cache;
    public function __construct($cache) { $this->cache = $cache; }
    public function get_page_cache() { return $this->cache; }
};

$test_cache->enabled = false;
test_same([], test_cache_menu(), 'No toolbar link for disabled page cache');
test_cache_failure(503, 'WP-Optimize page caching is not available or is disabled.');
test_same(0, $test_cache->purges, 'Disabled cache is not purged');
$test_cache->enabled = true;

foreach ([
    'site owner' => ['edit_posts' => true, 'patlis_manage' => true],
    'administrator' => ['edit_posts' => true, 'manage_options' => true],
    'content user' => ['edit_posts' => true],
] as $label => $caps) {
    $test_caps = $caps;
    $nodes = test_cache_menu();
    test_same(['patlis-purge-page-cache'], array_keys($nodes), $label . ' gets only purge control');
    test_same(
        '<span class="ab-icon dashicons dashicons-update" aria-hidden="true"></span><span class="ab-label">Clear cache</span>',
        $nodes['patlis-purge-page-cache']['title'],
        $label . ' gets decorative refresh icon and visible label'
    );
    test_same(
        'https://example.test/wp-admin/admin-post.php?action=patlis_purge_page_cache&_wpnonce=valid-nonce',
        $nodes['patlis-purge-page-cache']['href'],
        $label . ' uses authenticated nonce-protected endpoint'
    );
    $redirect = '';
    try {
        $test_actions['admin_post_patlis_purge_page_cache']();
    } catch (Test_Cache_Redirect $error) {
        $redirect = $error->getMessage();
    }
    test_same('https://example.test/wp-admin/index.php?patlis_page_cache_cleared=1', $redirect, $label . ' gets success redirect');
    test_same($caps, $test_caps, $label . ' capabilities unchanged');
}
test_same(3, $test_cache->purges, 'Each authorized request purges once');

$nonce_checks = $test_nonce_checks;
foreach ([
    'subscriber' => ['read' => true],
    'anonymous' => [],
    'Patlis-only user' => ['patlis_manage' => true],
] as $label => $caps) {
    $test_caps = $caps;
    test_same([], test_cache_menu(), $label . ' has no toolbar link');
    test_cache_failure(403, 'You are not allowed to perform this action.');
}
test_same($nonce_checks, $test_nonce_checks, 'Unauthorized requests stop before nonce checks');
test_same(false, isset($test_actions['admin_post_nopriv_patlis_purge_page_cache']), 'No unauthenticated endpoint');
test_same(3, $test_cache->purges, 'Unauthorized requests do not purge');

$test_caps = ['edit_posts' => true];
$test_nonce_valid = false;
test_cache_failure(403, 'Invalid nonce.');
test_same(3, $test_cache->purges, 'Forged request does not purge');
$test_nonce_valid = true;
$test_cache->result = false;
test_cache_failure(500, 'Cache could not be cleared. Please contact your administrator.');
test_same(4, $test_cache->purges, 'Failed purge attempted once without success redirect');

foreach ([
    '1' => true,
    '0' => false,
    'invalid' => false,
    'array' => false,
] as $value => $expected) {
    $_GET = ['patlis_page_cache_cleared' => $value === 'array' ? ['1'] : (string) $value];
    ob_start();
    $test_actions['admin_notices']();
    $notice = ob_get_clean();
    test_same($expected, strpos($notice, 'Cache cleared.') !== false, 'Notice for ' . $value);
}
$_GET = [];
ob_start();
$test_actions['admin_notices']();
test_same('', ob_get_clean(), 'No notice without success parameter');
$test_caps = [];
$_GET = ['patlis_page_cache_cleared' => '1'];
ob_start();
$test_actions['admin_notices']();
test_same('', ob_get_clean(), 'No notice for unauthorized users');

echo 'Passed ' . $test_assertions . " assertions.\n";
