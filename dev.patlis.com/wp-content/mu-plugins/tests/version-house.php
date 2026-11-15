<?php
/**
 * Standalone regression checks:
 * php tests\version-house.php hotel
 * php tests\version-house.php hotel,house
 * php tests\version-house.php " GASTRO, HOTEL, HOUSE "
 * php tests\version-house.php ""
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('PATLIS_VERSION', $argv[1] ?? 'hotel,house');
$test_actions = [];
$test_admin = false;
$test_terms = [];
$test_assertions = 0;
$test_status = null;
$test_removed_redirect = false;
$test_nocache = false;

function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {
    $GLOBALS['test_actions'][$hook][] = $callback;
}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void {}
function is_admin(): bool { return $GLOBALS['test_admin']; }
function is_page(): bool { return true; }
function taxonomy_exists($taxonomy): bool { return in_array($taxonomy, ['template', 'template_bundle'], true); }
function get_queried_object_id(): int { return 1; }
function wp_get_object_terms($id, $taxonomy, $args): array { return $GLOBALS['test_terms']; }
function is_wp_error($value): bool { return false; }
function remove_action($hook, $callback): void {
    $GLOBALS['test_removed_redirect'] = $hook === 'template_redirect' && $callback === 'redirect_canonical';
}
function status_header($status): void { $GLOBALS['test_status'] = $status; }
function nocache_headers(): void { $GLOBALS['test_nocache'] = true; }
function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

class WP_Query {
    private $args;
    public $is_404 = false;
    public function __construct(array $args = []) { $this->args = $args; }
    public function is_main_query(): bool { return true; }
    public function get($key) { return $this->args[$key] ?? ''; }
    public function set($key, $value): void { $this->args[$key] = $value; }
    public function set_404(): void { $this->is_404 = true; }
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . '000-patlis-version.php';

$has_house = in_array('house', patlis_version_get_parts(), true);
$has_hotel = in_array('hotel', patlis_version_get_parts(), true);
$allowed_terms = patlis_version_get_allowed_page_template_terms();

foreach (['rooms', 'top-rooms-section'] as $slug) {
    test_same($has_hotel && !$has_house, in_array($slug, $allowed_terms, true), $slug . ' page availability');
    test_same(PATLIS_VERSION === '' || ($has_hotel && !$has_house) ? 1 : 0, allowed_section(' ' . strtoupper($slug) . ' '), $slug . ' section availability');
}
test_same(1, allowed_section('home'), 'Common section stays available');
test_same($has_hotel, in_array('booking', $allowed_terms, true), 'Booking remains available for hotel');
test_same($has_hotel, patlis_version_is_plugin_allowed('patlis-accommodation/patlis-accommodation.php'), 'Accommodation gating is unchanged');

$test_admin = true;
$existing = [['taxonomy' => 'category', 'terms' => [1]]];
$query = new WP_Query(['post_type' => 'page', 'tax_query' => $existing]);
foreach ($test_actions['pre_get_posts'] as $callback) { $callback($query); }
$tax_query = $query->get('tax_query');
test_same($existing, $tax_query[0], 'Existing query is preserved');
$page_filter = $tax_query[1];
if ($has_house) {
    test_same('AND', $page_filter['relation'], 'Explicit exclusions override allowed terms');
    test_same('NOT IN', $page_filter[1]['operator'], 'Mixed-tag pages cannot bypass exclusions');
    test_same(['rooms', 'top-rooms-section'], $page_filter[1]['terms'], 'Both exclusions apply');
    $page_filter = $page_filter[0];
}
test_same($allowed_terms, $page_filter[0]['terms'], 'Admin uses the filtered allowed terms');
test_same('NOT EXISTS', $page_filter[1]['operator'], 'Untagged pages remain available');

$test_admin = false;
foreach ([
    'untagged' => [],
    'common' => ['home'],
    'rooms' => ['rooms'],
    'top rooms' => ['top-rooms-section'],
    'mixed rooms and common' => ['rooms', 'home'],
    'mixed top rooms and booking' => ['top-rooms-section', 'booking'],
] as $label => $terms) {
    $test_terms = $terms;
    $wp_query = new WP_Query();
    $test_status = null;
    $test_removed_redirect = false;
    $test_nocache = false;
    foreach ($test_actions['wp'] as $callback) { $callback(); }
    $blocked = $has_house && array_intersect($terms, ['rooms', 'top-rooms-section']) !== [];
    $expected_404 = $blocked || ($terms !== [] && array_intersect($terms, $allowed_terms) === []);
    test_same($expected_404, $wp_query->is_404, $label . ' frontend availability');
    test_same($expected_404 ? 404 : null, $test_status, $label . ' HTTP status');
    test_same($expected_404, $test_removed_redirect, $label . ' canonical redirect');
    test_same($expected_404, $test_nocache, $label . ' cache headers');
}

echo 'Passed ' . $test_assertions . " assertions for PATLIS_VERSION=" . PATLIS_VERSION . ".\n";
