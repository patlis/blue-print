<?php
/** Standalone regression checks: php tests\menu-item-sorting.php */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('ABSPATH', __DIR__);
$hooks = [];
$admin = true;
$screen = (object) ['id' => 'edit-menu_item'];
$assertions = 0;
function add_action($name, $callback, $priority = 10, $args = 1): void {
    $GLOBALS['hooks'][$name][$priority][] = $callback;
}
function add_filter($name, $callback, $priority = 10, $args = 1): void {
    add_action($name, $callback, $priority, $args);
}
function run_hook($name, $value, $query) {
    $callbacks = $GLOBALS['hooks'][$name] ?? [];
    ksort($callbacks);
    foreach ($callbacks as $group) {
        foreach ($group as $callback) {
            if ($name === 'pre_get_posts') $callback($query);
            else $value = $callback($value, $query);
        }
    }
    return $value;
}
function is_admin(): bool { return $GLOBALS['admin']; }
function get_current_screen() { return $GLOBALS['screen']; }
final class WP_Query {
    private $args;
    private $main;
    public function __construct(array $args, bool $main = true) { $this->args = $args; $this->main = $main; }
    public function get($name) { return $this->args[$name] ?? ''; }
    public function set($name, $value): void { $this->args[$name] = $value; }
    public function is_main_query(): bool { return $this->main; }
}
function same($expected, $actual, $label): void {
    $GLOBALS['assertions']++;
    if ($expected !== $actual) throw new RuntimeException($label . ': ' . var_export($actual, true));
}
$wpdb = (object) ['posts' => 'wp_posts', 'postmeta' => 'wp_postmeta'];
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'menu-items.php';
foreach (['ASC', 'DESC'] as $direction) {
    $q = new WP_Query(['post_type' => 'menu_item', 'orderby' => 'pmi_sort', 'order' => $direction, 'lang' => 'de']);
    run_hook('pre_get_posts', null, $q);
    same('', $q->get('meta_key'), 'Optional display order must not create an EXISTS meta restriction');
    same('none', $q->get('orderby'), 'SQL helper handles display order');
    same('de', $q->get('lang'), 'Language selection preserved');
    same('', $q->get('suppress_filters'), 'Polylang filters not disabled');
    $join = run_hook('posts_join', '', $q);
    same(2, substr_count($join, 'LEFT JOIN'), 'Missing sort and item number metadata retained');
    same(false, strpos($join, 'INNER JOIN') !== false, 'No filtering join');
    $sql = run_hook('posts_orderby', '', $q);
    same(true, strpos($sql, "AS SIGNED) {$direction}") !== false, 'Numeric sort direction supports negative values');
    same(true, strpos($sql, "meta_value = '') ASC") !== false, 'Missing and blank order values sort last');
    same(true, strpos($sql, 'wp_posts.ID ASC') !== false, 'Stable tie breaker');
    same('DISTINCT', run_hook('posts_distinct', '', $q), 'Duplicate meta joins cannot duplicate items');
}
foreach (['title', 'pmi_itemnr', 'pmi_price'] as $orderby) {
    $q = new WP_Query(['post_type' => 'menu_item', 'orderby' => $orderby]);
    run_hook('pre_get_posts', null, $q);
    same('', $q->get('patlis_menu_admin_sort'), 'Other admin sort behavior retained: ' . $orderby);
}
foreach ([false, true] as $main) {
    $admin = !$main;
    $q = new WP_Query(['post_type' => 'menu_item', 'orderby' => 'pmi_sort'], $main);
    run_hook('pre_get_posts', null, $q);
    same('', $q->get('patlis_menu_order'), 'Frontend and secondary queries untouched');
}
$q = new WP_Query(['post_type' => 'menu_item', 'patlis_menu_order' => 1]);
run_hook('pre_get_posts', null, $q);
$sql = run_hook('posts_orderby', '', $q);
same(true, strpos($sql, 'AS UNSIGNED) ASC') !== false, 'Existing frontend helper ordering retained');
echo "PASS: {$assertions} menu sorting checks.\n";
