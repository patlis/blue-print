<?php
/** Standalone regression checks: php tests\category-query-isolation.php */
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
define('ABSPATH', __DIR__);
$hooks = [];
$admin = true;
$assertions = 0;
function add_action($name, $callback, ...$args): void { $GLOBALS['hooks'][$name][] = $callback; }
function add_filter($name, $callback, ...$args): void { add_action($name, $callback); }
function is_admin(): bool { return $GLOBALS['admin']; }
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
final class WP_Meta_Query {
    public $parsed = [];
    public function parse_query_vars($args): void { $this->parsed = $args; }
}
final class WP_Term_Query {
    public $query_vars;
    public $meta_query;
    public function __construct(array $args) {
        $this->query_vars = $args;
        $this->meta_query = new WP_Meta_Query();
    }
}
function same($expected, $actual, $label): void {
    $GLOBALS['assertions']++;
    if ($expected !== $actual) throw new RuntimeException($label . ': ' . var_export($actual, true));
}
function run_query(array $args): WP_Term_Query {
    $query = new WP_Term_Query($args);
    foreach ($GLOBALS['hooks']['pre_get_terms'] as $callback) $callback($query);
    return $query;
}
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'categories.php';
foreach (['', 'title', 'pmc_sort'] as $orderby) {
    $_GET = $orderby === '' ? [] : ['orderby' => $orderby];
    foreach ([
        ['menu_section', 'language', 'post_translations'],
        ['language', 'menu_section'],
        ['language'],
        ['post_translations'],
        [],
    ] as $taxonomies) {
        $args = ['taxonomy' => $taxonomies, 'orderby' => 'name', 'object_ids' => [17787]];
        $query = run_query($args);
        same($args, $query->query_vars, 'Polylang/object cache query is untouched: ' . implode(',', $taxonomies));
        same([], $query->meta_query->parsed, 'No category meta restrictions on language terms');
        unset($args['object_ids']);
        same($args, run_query($args)->query_vars, 'Mixed taxonomy queries without object IDs also untouched');
    }
    $args = ['taxonomy' => ['menu_section'], 'object_ids' => [17787], 'orderby' => 'name'];
    same($args, run_query($args)->query_vars, 'Category assignments remain unfiltered');
}
foreach (['menu_section', ['menu_section']] as $taxonomy) {
    foreach (['', 'pmc_sort'] as $orderby) {
        $_GET = $orderby === '' ? [] : ['orderby' => $orderby];
        $query = run_query(['taxonomy' => $taxonomy, 'orderby' => 'name']);
        same('pmc_sort', $query->query_vars['meta_key'], 'Dedicated category sorting retained');
        same('meta_value_num', $query->query_vars['orderby'], 'Category ordering remains numeric');
        same($query->query_vars, $query->meta_query->parsed, 'Meta query rebuilt for category list');
    }
}
$_GET = ['orderby' => 'name'];
$args = ['taxonomy' => ['menu_section'], 'orderby' => 'name'];
same($args, run_query($args)->query_vars, 'Explicit category name sorting retained');
$_GET = [];
$admin = false;
same($args, run_query($args)->query_vars, 'Frontend query unchanged');
echo "PASS: {$assertions} category query isolation checks.\n";
