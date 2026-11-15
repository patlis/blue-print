<?php
/**
 * Standalone regression checks: php tests\top-packages-count.php
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
$test_packages = [];
$test_assertions = 0;

function add_shortcode($tag, $callback): void {}
function patlis_fallback_posts_query(array $args): array {
    $args['lang'] = 'en';
    return $args;
}
function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

class WP_Query {
    public $found_posts = 0;

    public function __construct(array $args) {
        foreach ($GLOBALS['test_packages'] as $package) {
            if ($package['post_type'] !== $args['post_type'] || $package['lang'] !== $args['lang']) {
                continue;
            }
            foreach ($args['meta_query'] as $filter) {
                $key = $filter['key'];
                if ($filter['compare'] === 'EXISTS' && !array_key_exists($key, $package)) {
                    continue 2;
                }
                if ($filter['compare'] === '=' && ($package[$key] ?? null) !== $filter['value']) {
                    continue 2;
                }
            }
            $this->found_posts++;
        }
    }
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'settings.php';

$enabled = ['post_type' => 'rates', 'lang' => 'en', 'package_enabled' => '1'];
$test_packages = [$enabled];
test_same(1, patlis_acc_top_packages_count(), 'Enabled offer without a featured image is counted');

$test_packages[] = $enabled + ['_thumbnail_id' => 10];
$test_packages[] = array_replace($enabled, ['package_enabled' => '0']);
$test_packages[] = array_replace($enabled, ['lang' => 'de']);
test_same(2, patlis_acc_top_packages_count(), 'Offers with and without images count, while disabled and other-language offers do not');

$test_packages = [array_replace($enabled, ['package_enabled' => '0'])];
test_same(0, patlis_acc_top_packages_count(), 'Only disabled offers leave the section count at zero');

echo 'Passed ' . $test_assertions . " assertions.\n";
