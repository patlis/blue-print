<?php
/**
 * Standalone regression checks: php tests\translations-access.php
 * Non-admins see translation groups without technical keys; form keys stay intact.
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
$test_roles = ['site_owner'];
$test_caps = ['patlis_manage' => true];
$test_keys = ['patlis_404_gohome', 'patlis_home'];
$test_translations = [
    'patlis_404_gohome' => ['en' => 'Go to Home Page', 'el' => 'Home EL', 'de' => 'Zur Startseite'],
    'patlis_home' => ['en' => 'Go to Home page', 'el' => 'Home EL', 'de' => 'Zur Startseite'],
];
$test_writes = [];
$test_assertions = 0;

final class WP_User {
    public $roles;
    public function __construct(array $roles) { $this->roles = $roles; }
}
final class Test_Translation_Redirect extends RuntimeException {}

function wp_get_current_user(): WP_User { return new WP_User($GLOBALS['test_roles']); }
function current_user_can($capability): bool { return !empty($GLOBALS['test_caps'][$capability]); }
function __($text, $domain = ''): string { return $text; }
function esc_html($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value): string { return esc_html($value); }
function esc_textarea($value): string { return esc_html($value); }
function esc_url($value): string { return esc_html($value); }
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function sanitize_text_field($value): string { return trim(strip_tags((string) $value)); }
function wp_kses_post($value): string { return strip_tags($value, '<strong><em>'); }
function wp_nonce_field($action, $name): void { echo '<input type="hidden" name="' . esc_attr($name) . '" value="valid">'; }
function wp_verify_nonce($nonce, $action): bool { return $nonce === 'valid'; }
function wp_die($message): void { throw new RuntimeException($message); }
function admin_url($path): string { return $path; }
function add_query_arg($key, $value, $url): string {
    return $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query([$key => $value]);
}
function wp_safe_redirect($url): void { throw new Test_Translation_Redirect($url); }
function add_action(...$args): void {}
function add_filter(...$args): void {}
function get_option($name, $default = []) { return ['en', 'de']; }
function pll_languages_list($args): array { return ['en', 'el', 'de']; }
function patlis_get_default_language(): string { return 'en'; }
function patlis_get_manual_translation_keys(): array { return $GLOBALS['test_keys']; }
function patlis_get_translations(): array { return $GLOBALS['test_translations']; }
function patlis_core_upsert_translation($key, $lang, $value): bool {
    $GLOBALS['test_writes'][$key][$lang] = $value;
    return true;
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'languages-visibility.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'translations.php';

function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function test_render(): DOMXPath {
    ob_start();
    Patlis_Admin_Page_Translations::render();
    $html = ob_get_clean();
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    return new DOMXPath($dom);
}

$_GET = [];
$_POST = [];
$table = '//table[contains(@class, "patlis-translations-table")]';

foreach ([
    'site owner' => [['site_owner'], ['patlis_manage' => true], false, 2],
    'administrator' => [['administrator'], ['patlis_manage' => true, 'manage_options' => true], true, 3],
    'custom manager' => [['custom_manager'], ['patlis_manage' => true, 'manage_options' => true], false, 3],
] as $label => [$roles, $caps, $show_keys, $language_count]) {
    $test_roles = $roles;
    $test_caps = $caps;
    $xpath = test_render();
    $headers = [];
    foreach ($xpath->query($table . '/thead/tr/th') as $header) {
        $headers[] = trim($header->textContent);
    }
    test_same($show_keys ? ['Key', 'Language', 'Translation'] : ['Language', 'Translation'], $headers, $label . ' headers align');
    test_same($show_keys ? 2 : 0, $xpath->query($table . '//code')->length, $label . ' key visibility');
    test_same($show_keys ? 0 : 1, $xpath->query($table . '//tr[@class="patlis-translation-group-start"]')->length, $label . ' group separators');
    test_same(2 * $language_count, $xpath->query($table . '//textarea')->length, $label . ' language access preserved');
    test_same(1, $xpath->query('//textarea[@name="patlis_translations[patlis_home][de]"]')->length, $label . ' form identity preserved');
    test_same(2, $xpath->query($table . '//td/strong[text()="EN"]')->length, $label . ' default language first');
    if (!$show_keys) {
        test_same(false, strpos($xpath->query($table)->item(0)->textContent, 'patlis_') !== false, $label . ' no technical key in table text');
        test_same('Search translations...', $xpath->query('//input[@name="s"]')->item(0)->getAttribute('placeholder'), $label . ' nontechnical search label');
    }
}

$test_roles = ['site_owner'];
$test_caps = ['patlis_manage' => true];
$_GET = ['s' => 'Startseite'];
$xpath = test_render();
test_same(4, $xpath->query($table . '//textarea')->length, 'Translated text search keeps both groups');
test_same(1, $xpath->query($table . '//tr[@class="patlis-translation-group-start"]')->length, 'Filtered groups remain separated');

foreach ([false, true] as $admin) {
    $test_roles = [$admin ? 'administrator' : 'site_owner'];
    $test_caps = ['patlis_manage' => true, 'manage_options' => $admin];
    $_GET = ['s' => 'no-match'];
    $xpath = test_render();
    test_same($admin ? '3' : '2', $xpath->query($table . '/tbody/tr/td')->item(0)->getAttribute('colspan'), 'Empty state spans visible columns');
    test_same($admin ? 'No keys found.' : 'No translations found.', trim($xpath->query($table . '/tbody/tr/td')->item(0)->textContent), 'Empty state uses role-appropriate wording');
}

$test_roles = ['site_owner'];
$test_caps = ['patlis_manage' => true];
$_GET = ['paged' => 2];
$test_keys = [];
for ($i = 1; $i <= 22; $i++) $test_keys[] = 'patlis_entry_' . $i;
$xpath = test_render();
test_same(4, $xpath->query($table . '//textarea')->length, 'Pagination preserves page size');
test_same(1, $xpath->query('//textarea[@name="patlis_translations[patlis_entry_21][en]"]')->length, 'Second page starts at correct entry');
test_same(1, $xpath->query($table . '//tr[@class="patlis-translation-group-start"]')->length, 'Second page group separator');

$test_keys = ['patlis_404_gohome', 'patlis_home'];
$_GET = [];
$_POST = [
    'patlis_translations_nonce' => 'valid',
    '_search' => 'Startseite',
    '_paged' => 1,
    'patlis_translations' => [
        'patlis_404_gohome' => ['en' => 'Updated 404', 'de' => 'Updated DE'],
        'patlis_home' => ['en' => 'Updated home', 'de' => 'Updated home DE', 'el' => 'Not allowed'],
    ],
];
$redirect = '';
try {
    Patlis_Admin_Page_Translations::render();
} catch (Test_Translation_Redirect $error) {
    $redirect = $error->getMessage();
}
test_same('admin.php?page=patlis-translations&patlis_saved=1&s=Startseite', $redirect, 'Save preserves search state');
test_same([
    'patlis_404_gohome' => ['en' => 'Updated 404', 'de' => 'Updated DE'],
    'patlis_home' => ['en' => 'Updated home', 'de' => 'Updated home DE'],
], $test_writes, 'Save still uses original keys and allowed languages only');

echo 'Passed ' . $test_assertions . " assertions.\n";
