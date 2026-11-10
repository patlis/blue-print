<?php
/**
 * Generate a browser regression fixture:
 * php tests\languages-visibility.php > languages-visibility.html
 * Open the generated HTML in a browser. Also test --administrator and --no-visible.
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
$test_actions = [];
$test_administrator = in_array('--administrator', $argv, true);
$test_visible = in_array('--no-visible', $argv, true) ? [] : ['el', 'de'];
$test_restricted = !$test_administrator && $test_visible !== [];

final class WP_User {
    public $roles;
    public function __construct(array $roles) { $this->roles = $roles; }
}

function wp_get_current_user(): WP_User {
    return new WP_User([$GLOBALS['test_administrator'] ? 'administrator' : 'site_owner']);
}
function is_admin(): bool { return true; }
function pll_languages_list($args): array { return ['el', 'de', 'it']; }
function get_option($name, $default = []) { return $GLOBALS['test_visible']; }
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function wp_json_encode($value): string { return json_encode($value); }
function add_action($hook, $callback): void { $GLOBALS['test_actions'][$hook] = $callback; }
function add_filter(...$args): void {}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'languages-visibility.php';
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Language visibility regression checks</title></head>
<body>
<div id="wpadminbar">
    <ul class="ab-submenu">
        <li id="wp-admin-bar-el"><a href="?lang=el" lang="el">Greek</a></li>
        <li id="wp-admin-bar-it"><a href="?lang=it" lang="it-IT">Italian</a></li>
    </ul>
</div>
<table>
    <tr class="user-language-wrap">
        <td><select id="locale" name="locale">
            <option value="site-default">Site default</option>
            <option value="" lang="en">English</option>
            <option value="el" lang="el">Greek</option>
            <option value="de_DE" lang="de">German</option>
            <option value="it_IT" lang="it">Italian</option>
        </select></td>
    </tr>
    <tr id="translation-el"><td><input name="post_tr_lang[el]"></td></tr>
    <tr id="translation-it"><td><input name="post_tr_lang[it]"></td></tr>
    <tr id="biography-el"><td><div lang="el">Greek biography</div></td></tr>
    <tr id="biography-it"><td><div lang="it-IT">Italian biography</div></td></tr>
</table>
<select name="post_lang_choice">
    <option value="el">Greek</option>
    <option value="de">German</option>
    <option value="it">Italian</option>
</select>
<pre id="test-result" data-status="pending">Pending</pre>
<?php $test_actions['admin_footer'](); ?>
<script>
(async function() {
    var assertions = 0;
    var restricted = <?php echo wp_json_encode($test_restricted); ?>;
    var result = document.getElementById('test-result');

    function check(condition, message) {
        assertions++;
        if (!condition) throw new Error(message);
    }

    function hidden(selector) {
        return getComputedStyle(document.querySelector(selector)).display === 'none';
    }

    try {
        check(!hidden('.user-language-wrap'), 'Profile language row must remain visible');
        check(document.querySelector('#locale').options.length === 5, 'All UI locales remain selectable');
        check(!hidden('#translation-el'), 'Allowed content translation remains visible');
        check(hidden('#translation-it') === restricted, 'Disabled content translation is restricted');
        check(!hidden('#biography-el'), 'Allowed biography remains visible');
        check(hidden('#biography-it') === restricted, 'Disabled biography is restricted');
        check(!hidden('#wp-admin-bar-el'), 'Allowed toolbar language remains visible');
        check(hidden('#wp-admin-bar-it') === restricted, 'Disabled toolbar language is restricted');
        check(document.querySelector('select[name="post_lang_choice"]').options.length === (restricted ? 2 : 3),
            'Content language choices retain their restrictions');

        var option = document.createElement('option');
        option.value = 'fr_FR';
        option.lang = 'fr';
        option.textContent = 'French';
        document.querySelector('#locale').appendChild(option);
        var row = document.createElement('tr');
        row.innerHTML = '<td><input name="post_tr_lang[it]"></td>';
        document.querySelector('table tbody').appendChild(row);
        await new Promise(function(resolve) { setTimeout(resolve, 0); });

        check(!hidden('.user-language-wrap'), 'Observer must not hide profile language row');
        check(document.querySelector('#locale').options.length === 6, 'Observer preserves new UI locale');
        check((row.style.display === 'none') === restricted, 'Observer still filters new content translations');
        document.querySelector('#locale').value = 'it_IT';
        check(document.querySelector('#locale').value === 'it_IT', 'Non-content UI language can be selected');
        result.dataset.status = 'passed';
        result.textContent = 'Passed ' + assertions + ' assertions.';
    } catch (error) {
        result.dataset.status = 'failed';
        result.textContent = error.message;
        throw error;
    }
})();
</script>
</body>
</html>
