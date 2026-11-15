<?php
/**
 * Standalone regression checks:
 * php tests\room-limits.php hotel,house
 * php tests\room-limits.php hotel
 * php tests\room-limits.php hotel,house monolingual
 * php tests\room-limits.php hotel,house no-polylang
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('DB_NAME', 'patlis_test');
define('PATLIS_VERSION', $argv[1] ?? 'hotel,house');
$test_multilingual = !in_array($argv[2] ?? '', ['monolingual', 'no-polylang'], true);
$test_filters = [];
$test_posts = [];
$test_language = 'en';
$test_assertions = 0;
$test_queries = 0;

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void {
    $GLOBALS['test_filters'][$hook][$priority][] = [$callback, $accepted_args];
}
function add_action($hook, $callback, $priority = 10, $accepted_args = 1): void {
    add_filter($hook, $callback, $priority, $accepted_args);
}
function apply_filters($hook, $value, ...$args) {
    $priorities = $GLOBALS['test_filters'][$hook] ?? [];
    ksort($priorities);
    foreach ($priorities as $callbacks) {
        foreach ($callbacks as [$callback, $accepted_args]) {
            $value = $callback(...array_slice([$value, ...$args], 0, $accepted_args));
        }
    }
    return $value;
}
function do_action($hook, ...$args): void {
    $priorities = $GLOBALS['test_filters'][$hook] ?? [];
    ksort($priorities);
    foreach ($priorities as $callbacks) {
        foreach ($callbacks as [$callback, $accepted_args]) {
            $callback(...array_slice($args, 0, $accepted_args));
        }
    }
}
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function wp_unslash($value) { return $value; }
if (($argv[2] ?? '') !== 'no-polylang') {
    function pll_is_translated_post_type($type): bool { return $GLOBALS['test_multilingual']; }
    function pll_languages_list($args): array { return ['en', 'de', 'fr']; }
    function pll_current_language($field): string { return $GLOBALS['test_language']; }
    function pll_default_language($field): string { return 'en'; }
    function pll_get_post_language($id, $field) { return $GLOBALS['test_posts'][$id]->language ?? false; }
}
function get_post($id) { return $GLOBALS['test_posts'][$id] ?? null; }
function get_post_type($id) { $post = get_post($id); return $post ? $post->post_type : false; }
function get_post_stati(): array { return ['publish', 'draft', 'pending', 'private', 'future', 'trash', 'auto-draft', 'inherit']; }
function get_term($id, $taxonomy) { return get_term_by('term_taxonomy_id', $id, $taxonomy); }
function get_term_by($field, $id, $taxonomy) {
    $languages = [1 => 'en', 2 => 'de', 3 => 'fr'];
    return isset($languages[$id]) ? (object) ['slug' => $languages[$id]] : false;
}
function get_posts($args): array {
    $GLOBALS['test_queries']++;
    $ids = [];
    foreach ($GLOBALS['test_posts'] as $id => $post) {
        if ($post->post_type === $args['post_type'] && in_array($post->post_status, $args['post_status'], true)
            && !in_array($id, $args['post__not_in'], true)) {
            $ids[] = $id;
        }
    }
    return $ids;
}
class WP_Error {
    private $code;
    private $message;
    private $data;
    public function __construct($code, $message, $data) { $this->code = $code; $this->message = $message; $this->data = $data; }
    public function get_error_code() { return $this->code; }
    public function get_error_data() { return $this->data; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($value): bool { return $value instanceof WP_Error; }
class Test_Room_Limit_Error extends RuntimeException {
    public $error;
    public function __construct(WP_Error $error) { parent::__construct($error->get_error_message()); $this->error = $error; }
}
function wp_die($error, $title, $args): void { throw new Test_Room_Limit_Error($error); }
class WP_Post {
    public $ID;
    public $post_type = 'patlis_room';
    public $post_status;
    public $language;
    public function __construct(int $id, string $status, string $language) {
        $this->ID = $id; $this->post_status = $status; $this->language = $language;
    }
}
class WP_REST_Request {
    private $params;
    public function __construct(array $params) { $this->params = $params; }
    public function get_param($key) { return $this->params[$key] ?? null; }
}
class Test_Room_DB {
    public $prefix = 'wp_test_';
    public $last_error = '';
    public $lock_result = '1';
    public $lock_calls = 0;
    public function prepare($query, ...$args): string { return $query; }
    public function get_var($query) {
        if (strpos($query, 'GET_LOCK') !== false) {
            $this->lock_calls++;
            return $this->lock_result;
        }
        return '1';
    }
}
$wpdb = new Test_Room_DB();

function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($expected !== $actual) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}
function test_check($result, bool $allowed, string $label): void {
    test_same($allowed, $result === true, $label);
    if (!$allowed) {
        test_same('patlis_room_limit_reached', $result->get_error_code(), $label . ' error');
        test_same(409, $result->get_error_data()['status'], $label . ' status');
    }
}
function test_dies(callable $callback, bool $expected, string $label): void {
    $error = null;
    try {
        $callback();
    } catch (Test_Room_Limit_Error $caught) {
        $error = $caught->error;
    }
    test_same($expected, $error !== null, $label);
    if ($error) {
        test_same('patlis_room_limit_reached', $error->get_error_code(), $label . ' error');
    }
}

require dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'mu-plugins' . DIRECTORY_SEPARATOR . '000-patlis-version.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'room-limits.php';

$limited = in_array('house', patlis_version_get_parts(), true);
$en = $test_multilingual ? 'en' : '';
$de = $test_multilingual ? 'de' : '';
test_same($limited ? 1 : 0, patlis_acc_room_limit(), 'Only House has a room limit');
test_check(patlis_acc_room_limit_check(0, $en, 'draft'), true, 'First room is allowed');

foreach (['publish', 'draft', 'pending', 'private', 'future'] as $status) {
    $test_posts = [1 => new WP_Post(1, $status, 'en')];
    test_check(patlis_acc_room_limit_check(0, $en, 'draft'), !$limited, $status . ' occupies the language slot');
    test_check(patlis_acc_room_limit_check(1, $en, 'publish'), true, $status . ' can still be edited');
}
foreach (['trash', 'auto-draft', 'inherit'] as $status) {
    $test_posts = [1 => new WP_Post(1, $status, 'en')];
    test_check(patlis_acc_room_limit_check(0, $en, 'draft'), true, $status . ' does not occupy a slot');
}

$test_posts = [1 => new WP_Post(1, 'publish', 'en')];
test_check(patlis_acc_room_limit_check(0, $de, 'draft'), !$limited || $test_multilingual, 'Another language has its own slot');
$test_posts[2] = new WP_Post(2, 'draft', 'de');
test_check(patlis_acc_room_limit_check(0, $de, 'publish'), !$limited, 'Second room in the other language is blocked');
test_check(patlis_acc_room_limit_check(1, $de, 'publish'), !$limited || !$test_multilingual, 'Changing to an occupied language is blocked');
test_check(patlis_acc_room_limit_check(1, $en, 'trash'), true, 'Trashing a room is allowed');
test_check(patlis_acc_room_limit_check(0, $en, 'auto-draft'), true, 'Autosave placeholders are allowed');

$test_posts[3] = new WP_Post(3, 'trash', 'en');
test_dies(static function (): void {
    apply_filters('pre_untrash_post', null, get_post(3));
}, $limited, 'Restoring a room into an occupied language is blocked');

$post_data = ['post_type' => 'patlis_room', 'post_status' => 'draft'];
$_POST = ['post_lang_choice' => 'en'];
test_dies(static function () use ($post_data): void {
    apply_filters('wp_insert_post_data', $post_data, []);
}, $limited, 'Saving a second room through WordPress is blocked');
test_dies(static function (): void {
    do_action('add_term_relationship', 1, 2, 'language');
}, $limited, 'Polylang language changes cannot bypass the limit');
$_POST = [];

$_GET = ['lang' => 'de'];
test_dies(static function () use ($post_data): void {
    apply_filters('wp_insert_post_data', $post_data, ['ID' => 1]);
}, false, 'A list language filter does not change the language of an existing room');
$_GET = [];

$test_posts[4] = new WP_Post(4, 'publish', 'en');
$test_posts[4]->post_type = 'page';
test_dies(static function () use ($post_data): void {
    apply_filters('wp_insert_post_data', $post_data, ['ID' => 4]);
}, $limited, 'Converting another post type to a room cannot bypass the limit');
unset($test_posts[4]);

$rest = apply_filters('rest_pre_insert_patlis_room', (object) ['post_status' => 'draft'], new WP_REST_Request(['lang' => 'de']));
test_same($limited, is_wp_error($rest), 'REST returns an error for an occupied language');
$rest = apply_filters('rest_pre_insert_patlis_room', (object) ['post_status' => 'publish'], new WP_REST_Request(['id' => 1, 'lang' => 'en']));
test_same(false, is_wp_error($rest), 'REST can edit an existing room');

$test_posts = [1 => new WP_Post(1, 'publish', 'en')];
$rest = apply_filters('rest_pre_insert_patlis_room', (object) ['post_status' => 'draft'], new WP_REST_Request(['lang' => 'de']));
test_same($limited && !$test_multilingual, is_wp_error($rest), 'REST can create a translation in a free language');
if ($limited && $test_multilingual) {
    test_same('de', $rest->patlis_room_limit_language, 'REST passes the target language to the WordPress save guard');
}

$_GET = ['post_type' => 'patlis_room', 'new_lang' => 'en'];
test_dies(static function (): void { do_action('load-post-new.php'); }, $limited, 'Add New cannot open an occupied language');
$_GET['new_lang'] = 'de';
test_dies(static function (): void { do_action('load-post-new.php'); }, $limited && !$test_multilingual, 'Translation editor can open a free language');
$_GET = [];

if ($limited && $test_multilingual) {
    $invalid = apply_filters('rest_pre_insert_patlis_room', (object) [], new WP_REST_Request(['lang' => 'invalid']));
    test_same('patlis_room_language_invalid', $invalid->get_error_code(), 'Invalid languages fail explicitly');
    $test_posts = [1 => new WP_Post(1, 'publish', '')];
    test_check(patlis_acc_room_limit_check(0, 'en', 'draft'), false, 'Unassigned legacy rooms occupy the default language');
    $wpdb->lock_result = '0';
    $busy = patlis_acc_room_limit_check(0, 'fr', 'draft');
    test_same('patlis_room_limit_busy', $busy->get_error_code(), 'Lock contention fails explicitly instead of allowing a duplicate');
    $wpdb->lock_result = '1';
    $wpdb->last_error = 'test database failure';
    $failed = patlis_acc_room_limit_check(0, 'en', 'draft');
    test_same('patlis_room_limit_query_failed', $failed->get_error_code(), 'Database failures do not bypass the limit');
    $wpdb->last_error = '';
}
if (!$limited) {
    test_same(0, $test_queries, 'Hotel performs no limit queries');
    test_same(0, $wpdb->lock_calls, 'Hotel acquires no limit locks');
}
do_action('shutdown');
echo 'Passed ' . $test_assertions . ' assertions for PATLIS_VERSION=' . PATLIS_VERSION
    . ', mode=' . ($argv[2] ?? 'multilingual') . ".\n";
