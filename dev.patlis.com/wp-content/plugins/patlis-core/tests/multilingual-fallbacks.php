<?php
/**
 * Standalone regression checks: php tests\multilingual-fallbacks.php
 * Also run with --without-polylang to check language-independent installations.
 */

error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): void {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('PATLIS_CORE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

$test_options = [];
$test_filters = [];
$test_translations = [];
$test_mail = [];
$test_current_language = 'en';
$test_default_language = 'en';
$test_assertions = 0;

if (!in_array('--without-polylang', $argv, true)) {
    function pll_current_language($field = 'slug') {
        return $GLOBALS['test_current_language'];
    }

    function pll_default_language($field = 'slug') {
        return $GLOBALS['test_default_language'];
    }
}

function get_option($name, $default = []) {
    return $GLOBALS['test_options'][$name] ?? $default;
}

function sanitize_key($value): string {
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}

function absint($value): int {
    return abs((int) $value);
}

function wp_get_attachment_image_url($id, $size = 'thumbnail') {
    return $id > 0 ? 'https://example.test/image-' . $id . '.jpg' : false;
}

function __($text, $domain = ''): string {
    return $text;
}

function esc_html($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function esc_attr($value): string {
    return esc_html($value);
}

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1): void {
    $GLOBALS['test_filters'][$hook][$priority][] = [$callback, $accepted_args];
}

function apply_filters($hook, $value, ...$args) {
    $callbacks = $GLOBALS['test_filters'][$hook] ?? [];
    ksort($callbacks);
    foreach ($callbacks as $group) {
        foreach ($group as [$callback, $accepted_args]) {
            $value = $callback(...array_slice([$value, ...$args], 0, $accepted_args));
        }
    }
    return $value;
}

function add_action(...$args): void {}
function add_shortcode(...$args): void {}

function patlis_core_get_translations_from_db(): array {
    return $GLOBALS['test_translations'];
}

function wp_mail($to, $subject, $body, $headers): bool {
    $GLOBALS['test_mail'][] = compact('to', 'subject', 'body', 'headers');
    return true;
}

function test_same($expected, $actual, string $label): void {
    $GLOBALS['test_assertions']++;
    if ($actual !== $expected) {
        throw new RuntimeException($label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

foreach ([
    ['core.php'],
    ['helpers.php'],
    ['translations-helpers.php'],
    ['bricks-tags.php'],
    ['sliders', 'sliders-helpers.php'],
    ['admin', 'pages', 'opening-times.php'],
    ['admin', 'pages', 'notification-bar.php'],
    ['form-handlers.php'],
] as $parts) {
    require_once PATLIS_CORE_PATH . 'includes' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
}

if (!function_exists('pll_current_language')) {
    test_same('Legacy', patlis_core_get_multilang_value('Legacy'), 'Legacy string without Polylang');
    test_same('Default', patlis_core_get_multilang_value(['default' => 'Default', 'el' => 'Other']), 'Default slot without Polylang');
    test_same('', patlis_core_get_multilang_value(['default' => '', 'el' => 'Other']), 'Empty default without Polylang');
    test_same('', patlis_core_get_multilang_value(['el' => 'Other']), 'No arbitrary language without Polylang');
    test_same('Default', patlis_sliders_get_translated_value(['default' => 'Default', 'el' => 'Other']), 'Slider default without Polylang');
    test_same('Legacy', patlis_sliders_get_translated_value('Legacy'), 'Legacy slider without Polylang');
    $test_translations = ['patlis_empty' => ['default' => '', 'el' => 'Other']];
    test_same('', patlis_transl('patlis_empty'), 'Empty translation without Polylang');
    test_same('', patlis_transl('patlis_missing'), 'Missing translation without Polylang');
    echo "PASS: {$test_assertions} checks without Polylang.\n";
    exit(0);
}

$cases = [
    ['en', ['en' => '', 'el' => 'Greek'], ''],
    ['el', ['en' => 'English', 'el' => ''], 'English'],
    ['el', ['en' => 'English', 'el' => 'Greek'], 'Greek'],
    ['el', ['en' => '', 'el' => '', 'de' => 'German'], ''],
    ['el', ['de' => 'German'], ''],
    ['en', ['el' => 'Greek'], ''],
    ['el', ['en' => 'English'], 'English'],
    ['de', ['en' => 'English', 'el' => 'Greek'], 'English'],
    ['el', ['en' => 'English', 'el' => '0'], '0'],
    ['el', ['en' => '0', 'el' => ''], '0'],
    ['', ['en' => 'English', 'el' => 'Greek'], 'English'],
    ['el', [], ''],
    ['el', 'Legacy', 'Legacy'],
    ['el', ['en' => 'English', 'el' => null], 'English'],
    ['el', ['en' => 'English', 'el' => []], 'English'],
    ['el', ['en' => null, 'el' => '', 'de' => 'German'], ''],
];

foreach ($cases as $index => [$language, $values, $expected]) {
    $test_translations['patlis_case_' . $index] = $values;
}
$test_translations['patlis_form_your_name'] = ['en' => '', 'el' => 'Greek name'];
$test_translations['patlis_form_your_phone'] = ['en' => 'Phone', 'el' => 'Greek phone'];

add_filter('patlis/design_options/registry', static function ($registry): array {
    return [
        'test' => [
            'fields' => ['title' => ['type' => 'text', 'option_key' => 'title']],
            'default_variant' => 'classic',
            'variants' => ['classic' => ['fields' => ['content' => ['type' => 'textarea', 'option_key' => 'content']]]],
        ],
    ];
});

foreach ($cases as $index => [$language, $values, $expected]) {
    $test_current_language = $language;
    $test_options = [
        Patlis_Core::OPTION_BASIC => [
            'slogan' => $values,
            'contact_confirm_subject' => $values,
            'contact_confirm_body' => $values,
        ],
        Patlis_Core::OPTION_DESIGN_OPTIONS => [
            'global' => ['heading' => $values],
            'test' => ['title' => $values, 'classic' => ['content' => $values]],
        ],
        Patlis_Core::OPTION_OPENING => ['text' => $values],
        Patlis_Core::OPTION_NOTIFICATION_BAR => ['text' => $values],
        Patlis_Core::OPTION_CENTER_POPUP => array_fill_keys(['title', 'code', 'html', 'link_url'], $values),
        'patlis_sliders' => ['test' => ['slides' => [
            array_merge(['enabled' => 1, 'sort' => 1], array_fill_keys(['small_title', 'header', 'content', 'cta1_text', 'cta1_url', 'cta2_text', 'cta2_url'], $values)),
        ]]],
    ];
    $label = 'Case ' . $index;
    test_same($expected, patlis_core_get_multilang_value($values), $label . ' shared resolver');
    test_same($expected, Patlis_Core::get_basic_text('slogan'), $label . ' basic');
    test_same($expected, Patlis_Core::get_global_design_text('heading'), $label . ' global design');
    test_same($expected, Patlis_Core::get_design_field('test', 'title')['value'], $label . ' section design');
    test_same($expected, Patlis_Core::get_design_field('test', 'content')['value'], $label . ' variant design');
    test_same($expected, patlis_get_contact_confirm_subject(), $label . ' confirmation subject');
    test_same($expected, patlis_get_contact_confirm_body(), $label . ' confirmation body');
    test_same($expected, Patlis_Admin_Page_Opening::text(), $label . ' opening getter');
    test_same($expected, Patlis_Admin_Page_Notification_Bar::text(), $label . ' bar getter');
    test_same($expected, patlis_sliders_get_translated_value($values), $label . ' slider');
    test_same(is_array($values) ? $expected : '', patlis_transl('patlis_case_' . $index), $label . ' translation');

    foreach (['small_title', 'header', 'content', 'cta1_text', 'cta1_url', 'cta2_text', 'cta2_url'] as $field) {
        test_same($expected, patlis_sliders_get_group('test')['slides'][0][$field], $label . ' slide ' . $field);
    }

    foreach (['patlis_slogan', 'patlis_opening_text', 'patlis_bar_text', 'patlis_center_title', 'patlis_center_code', 'patlis_center_html', 'patlis_center_link_url', 'patlis_design:test:title', 'patlis_design:test:content', 'patlis_design:test:classic:content'] as $tag) {
        $input = '{' . $tag . '}';
        test_same($expected, patlis_render_dynamic_tags_in_content($input), $label . ' content ' . $tag);
        test_same($expected, apply_filters('bricks/dynamic_data/render_content', $input, null), $label . ' Bricks content ' . $tag);
        test_same($expected, apply_filters('bricks/frontend/render_data', $input, null), $label . ' frontend ' . $tag);
    }
    foreach (['patlis_slogan', 'patlis_center_title', 'patlis_design:test:title', 'patlis_design:test:content', 'patlis_design:test:classic:content'] as $tag) {
        test_same($expected, apply_filters('bricks/dynamic_data/render_tag', '{' . $tag . '}', null), $label . ' direct tag ' . $tag);
    }
}

add_filter('patlis/design_options/registry', static function ($registry): array {
    $registry['top_events'] = [
        'fields' => ['heading' => ['type' => 'text', 'default' => 'Shared heading']],
        'default_variant' => 'classic',
        'variants' => [
            'classic' => ['fields' => ['description' => ['type' => 'textarea']]],
            'cinematic' => ['fields' => [
                'bg_image' => ['type' => 'image', 'option_key' => 'bg_image_id'],
                'overlay_opacity' => ['type' => 'range'],
            ]],
            'split-screen' => ['fields' => ['description' => ['type' => 'textarea']]],
            'agenda' => ['fields' => ['description' => ['type' => 'textarea']]],
        ],
    ];
    return $registry;
}, 20);
$test_current_language = 'en';
$test_options[Patlis_Core::OPTION_DESIGN_OPTIONS]['top_events'] = [
    'variant' => 'classic',
    'classic' => ['description' => ['en' => 'Classic']],
    'cinematic' => ['bg_image_id' => 42, 'overlay_opacity' => 0],
    'split-screen' => ['description' => ['en' => 'Split']],
    'agenda' => ['description' => ['en' => 'Agenda']],
];
$original_design_options = $test_options[Patlis_Core::OPTION_DESIGN_OPTIONS];
$tags = [
    'patlis_design:top_events:description' => 'Classic',
    'patlis_design:top_events:classic:description' => 'Classic',
    'patlis_design:top_events:cinematic:bg_image' => 'https://example.test/image-42.jpg',
    'patlis_design:top_events:cinematic:overlay_opacity' => '0',
    'patlis_design:top_events:split-screen:description' => 'Split',
    'patlis_design:top_events:agenda:description' => 'Agenda',
    'patlis_design:top_events:heading' => 'Shared heading',
    'patlis_design:top_events:cinematic:heading' => 'Shared heading',
    'patlis_design:top_events:bg_image' => '',
    'patlis_design:top_events:classic:bg_image' => '',
    'patlis_design:top_events:missing:description' => '',
    'patlis_design:missing:cinematic:bg_image' => '',
    'patlis_design:top_events:cinematic:missing' => '',
];
foreach ($tags as $tag => $expected) {
    $input = '{' . $tag . '}';
    test_same($expected, patlis_render_dynamic_tags_in_content($input), 'Variant content ' . $tag);
    test_same($expected, apply_filters('bricks/dynamic_data/render_content', $input, null), 'Variant Bricks content ' . $tag);
    test_same($expected, apply_filters('bricks/frontend/render_data', $input, null), 'Variant frontend ' . $tag);
    test_same($expected, apply_filters('bricks/dynamic_data/render_tag', $input, null), 'Variant direct ' . $tag);
    test_same($expected, apply_filters('bricks/dynamic_data/render_tag', $tag, null), 'Variant direct without braces ' . $tag);
}
test_same(
    'Classic / https://example.test/image-42.jpg / Split / Agenda',
    patlis_render_dynamic_tags_in_content('{patlis_design:top_events:description} / {patlis_design:top_events:cinematic:bg_image} / {patlis_design:top_events:split-screen:description} / {patlis_design:top_events:agenda:description}'),
    'Simultaneous rendering of all variants'
);
test_same(['type' => 'image', 'value' => 42], Patlis_Core::get_design_field('top_events', 'bg_image', 'cinematic'), 'Explicit helper image');
test_same(null, Patlis_Core::get_design_field('top_events', 'description', 'missing'), 'Explicit invalid variant must not fall back');
test_same(null, Patlis_Core::get_design_field('top_events', 'description', ''), 'Explicit empty variant must not fall back');
test_same([42], apply_filters('bricks/dynamic_data/render_tag', '{patlis_design:top_events:cinematic:bg_image}', null, 'image'), 'Explicit image context IDs');
test_same($original_design_options, $test_options[Patlis_Core::OPTION_DESIGN_OPTIONS], 'Variant reads must not change saved settings');
$test_options[Patlis_Core::OPTION_DESIGN_OPTIONS]['top_events']['variant'] = 'cinematic';
test_same('https://example.test/image-42.jpg', patlis_render_dynamic_tags_in_content('{patlis_design:top_events:bg_image}'), 'Legacy tag follows changed selection');
test_same([42], apply_filters('bricks/dynamic_data/render_tag', '{patlis_design:top_events:bg_image}', null, 'image'), 'Legacy image context IDs');
test_same('Classic', patlis_render_dynamic_tags_in_content('{patlis_design:top_events:classic:description}'), 'Explicit variant ignores changed selection');
$test_options[Patlis_Core::OPTION_DESIGN_OPTIONS]['top_events']['variant'] = 'missing';
test_same('Classic', patlis_render_dynamic_tags_in_content('{patlis_design:top_events:description}'), 'Legacy invalid selection falls back to default');
unset($test_options[Patlis_Core::OPTION_DESIGN_OPTIONS]['top_events']['cinematic']['bg_image_id']);
test_same([], apply_filters('bricks/dynamic_data/render_tag', '{patlis_design:top_events:cinematic:bg_image}', null, 'image'), 'Missing explicit image context');
test_same('', patlis_render_dynamic_tags_in_content('{patlis_design:top_events:cinematic:bg_image}'), 'Missing explicit image content');
test_same('', apply_filters('bricks/dynamic_data/render_tag', '{patlis_design:top_events:cinematic:bg_image:extra}', null), 'Reject extra direct arguments');

test_same('', patlis_transl('patlis_missing'), 'Missing translation must not expose key');
test_same('', patlis_transl(''), 'Empty translation key');
test_same('', patlis_core_get_multilang_value(null), 'Absent field');
test_same('', patlis_core_get_multilang_value(['en' => [], 'el' => '', 'de' => 'German']), 'Invalid default must not select third language');
$test_current_language = 'en';
test_same('Greek', patlis_sliders_get_translated_value(['en' => 'English', 'el' => 'Greek'], 'el'), 'Explicit slider language');
test_same('', patlis_sliders_get_translated_value(['en' => '', 'el' => '', 'de' => 'German'], 'el'), 'Explicit slider language cannot select third');

$test_default_language = 'el';
test_same('Greek', patlis_core_get_multilang_value(['en' => '', 'el' => 'Greek']), 'Configured default is not hardcoded');
test_same('', patlis_core_get_multilang_value(['en' => '', 'el' => '', 'de' => 'German']), 'Empty non-English default');
$test_default_language = 'en';
$test_current_language = 'el';
$test_options[Patlis_Core::OPTION_BASIC] = ['contact_form_recipient_email' => 'owner@example.test', 'contact_form_email_subject' => 'Contact'];
patlis_core_send_contact_notification('Visitor', '', 'visitor@example.test', 'Message', 'el');
test_same(false, strpos($test_mail[0]['body'], 'Greek'), 'Owner notification must remain in default language');
test_same(false, strpos($test_mail[0]['body'], 'patlis_form_'), 'Owner notification must not expose missing translation keys');
test_same(true, strpos($test_mail[0]['body'], 'Phone') !== false, 'Default-language owner label');

echo "PASS: {$test_assertions} multilingual fallback checks.\n";
