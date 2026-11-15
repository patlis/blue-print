<?php
/**
 * Bricks toolbar links are administrator-only on both frontend and backend.
 * Standalone regression checks: php tests\bricks-admin-bar.php
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);

$test_actions = [];
$test_caps = [];
$test_assertions = 0;

function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {
    $GLOBALS['test_actions'][$hook][$priority][] = $callback;
}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void {}
function current_user_can($capability): bool {
    return !empty($GLOBALS['test_caps'][$capability]);
}
function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

final class Test_Admin_Bar {
    private $nodes = [];

    public function __construct(array $ids) {
        foreach ($ids as $id) {
            $this->nodes[$id] = (object) ['id' => $id];
        }
    }

    public function get_nodes(): array { return $this->nodes; }
    public function remove_node($id): void { unset($this->nodes[$id]); }
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'patlis-protect.php';

$bricks_nodes = [
    'edit_with_bricks',
    'delete_bricks_data',
    'editor_mode',
    'bricks',
    'bricks_edit',
    'bricks_delete',
    'bricks_rendered',
    'bricks-template',
];
$other_nodes = ['site-name', 'edit', 'new-content', 'patlis-clear-cache', 'rank-math'];
$all_nodes = array_merge($other_nodes, $bricks_nodes);

foreach ([
    'site owner' => ['patlis_manage' => true, 'manage_options' => true, 'edit_posts' => true],
    'editor' => ['edit_posts' => true],
    'subscriber' => ['read' => true],
    'anonymous' => [],
    'administrator' => ['administrator' => true, 'manage_options' => true],
] as $label => $caps) {
    $test_caps = $caps;
    $wp_admin_bar = new Test_Admin_Bar($all_nodes);
    $actions = $test_actions['wp_before_admin_bar_render'];
    ksort($actions);
    foreach ($actions as $callbacks) {
        foreach ($callbacks as $callback) {
            $callback();
        }
    }
    test_same(
        !empty($caps['administrator']) ? $all_nodes : $other_nodes,
        array_keys($wp_admin_bar->get_nodes()),
        $label . ' toolbar visibility'
    );
}

$test_caps = ['read' => true];
$wp_admin_bar = new Test_Admin_Bar($other_nodes);
foreach ($actions as $callbacks) {
    foreach ($callbacks as $callback) {
        $callback();
    }
}
test_same($other_nodes, array_keys($wp_admin_bar->get_nodes()), 'Toolbar without Bricks is unchanged');

echo 'Passed ' . $test_assertions . " assertions.\n";
