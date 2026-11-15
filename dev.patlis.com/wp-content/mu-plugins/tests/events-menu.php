<?php
/**
 * Standalone regression checks:
 * php tests\events-menu.php hotel
 * php tests\events-menu.php hotel,house
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('PATLIS_VERSION', $argv[1] ?? 'hotel,house');
$test_actions = [];
$test_menu = ['index.php', 'edit.php?post_type=events', 'edit.php?post_type=reviews'];
$test_assertions = 0;

function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {
    $GLOBALS['test_actions'][$hook][$priority][] = $callback;
}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void {}
function remove_menu_page($slug): void {
    $GLOBALS['test_menu'] = array_values(array_diff($GLOBALS['test_menu'], [$slug]));
}
function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . '000-patlis-version.php';

$actions = $test_actions['admin_menu'];
ksort($actions);
foreach ($actions as $callbacks) {
    foreach ($callbacks as $callback) {
        $callback();
    }
}

$has_house = in_array('house', patlis_version_get_parts(), true);
test_same(!$has_house, in_array('edit.php?post_type=events', $test_menu, true), 'Events menu follows version exclusions');
test_same(true, in_array('index.php', $test_menu, true), 'Dashboard remains visible');
test_same(true, in_array('edit.php?post_type=reviews', $test_menu, true), 'Other CPT menus remain visible');

echo 'Passed ' . $test_assertions . " assertions for PATLIS_VERSION=" . PATLIS_VERSION . ".\n";
