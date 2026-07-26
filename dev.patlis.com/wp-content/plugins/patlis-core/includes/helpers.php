<?php
if (!defined('ABSPATH')) exit;

/**
 * Get Patlis basic settings array.
 */
function patlis_get_basic_option(): array
{
    $optionName = 'patlis_basic';

    if (class_exists('Patlis_Core') && defined('Patlis_Core::OPTION_BASIC')) {
        $optionName = constant('Patlis_Core::OPTION_BASIC');
    }

    $opt = get_option($optionName, []);
    return is_array($opt) ? $opt : [];
}

/**
 * Get appearance mode from basic settings.
 * Returns one of: light_dark, light_only, dark_only.
 */
function patlis_get_appearance_mode(): string
{
    $opt = patlis_get_basic_option();

    $mode = isset($opt['appearance_mode']) ? sanitize_key((string) $opt['appearance_mode']) : 'light_dark';
    $allowed = ['light_dark', 'light_only', 'dark_only'];

    return in_array($mode, $allowed, true) ? $mode : 'light_dark';
}

/**
 * Get white label settings array.
 */
/**
 * Get the welcome section video URL (media library takes priority over URL).
 * Returns empty string if no video is set.
 */
function patlis_get_welcome_video_url(): string
{
    $opt = class_exists('Patlis_Core') ? get_option(Patlis_Core::OPTION_HOMEPAGE, []) : get_option('patlis_homepage', []);
    if (!is_array($opt)) return '';
    $url = isset($opt['welcome_video_url']) ? (string)$opt['welcome_video_url'] : '';
    return esc_url_raw($url);
}

/**
 * '1' if a welcome video URL is saved, '0' otherwise.
 * Use: {echo:patlis_home_video_has_url()} == 1
 */
function patlis_home_video_has_url(): string
{
    return patlis_get_welcome_video_url() !== '' ? '1' : '0';
}

/**
 * '1' if the welcome video is a local/uploaded file, '0' if external (YouTube, Vimeo…).
 * Use: {echo:patlis_home_video_is_local()} == 1
 */
function patlis_home_video_is_local(): string
{
    $url = patlis_get_welcome_video_url();
    if ($url === '') return '0';
    return patlis_is_local_video_url($url);
}

/**
 * Ready-to-render HTML for the welcome section video (no nesting needed).
 * Use: {echo:patlis_home_video_html()}
 */
function patlis_home_video_html(): string
{
    $url = patlis_get_welcome_video_url();
    if ($url === '' || !function_exists('patlis_video_html')) return '';
    return patlis_video_html($url);
}

/**
 * Version / active-plugin helpers — safe to use in Bricks echo conditions.
 * Returns '1' (active) or '0' (inactive/not in version).
 */
function patlis_has_reservations(): string {
    return function_exists('patlis_reservations_get_settings') ? '1' : '0';
}

function patlis_has_gastro(): string {
    return (function_exists('patlis_version_has_gastro') && patlis_version_has_gastro()) ? '1' : '0';
}

function patlis_has_hotel(): string {
    return (function_exists('patlis_version_has_hotel') && patlis_version_has_hotel()) ? '1' : '0';
}

function patlis_has_kiosk(): string {
    return (function_exists('patlis_version_has_kiosk') && patlis_version_has_kiosk()) ? '1' : '0';
}

function patlis_has_dining(): string {
    return (function_exists('patlis_version_has_dining') && patlis_version_has_dining()) ? '1' : '0';
}

function patlis_core_slides_count(): int {
    $args = [
        'post_type'      => 'slide',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
}

function patlis_top_services_count(): int {
    $args = [
        'post_type'      => 'services',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'service_show',
                'value'   => '1',
                'compare' => '=',
            ],
            [
                'key'     => 'create_service_page',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
}

function patlis_sticky_services_count(): int {
    $args = [
        'post_type'      => 'services',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'service_show',
                'value'   => '1',
                'compare' => '=',
            ],
            [
                'key'     => 'service_sticky',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
}

function patlis_more_services_count(): int {
    $args = [
        'post_type'      => 'services',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => 'create_service_page',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => 'create_service_page',
                'value'   => '1',
                'compare' => '!=',
            ],
        ],
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
}

function patlis_service_gallery_count(): int {
    $post = get_post();
    if (!$post || get_post_type($post) !== 'services') return 0;

    if (!function_exists('patlis_core_resolve_gallery_ids_for_post')) return 0;

    $ids = patlis_core_resolve_gallery_ids_for_post((int) $post->ID, 'services_gallery_ids');
    return count($ids);
}

function patlis_event_gallery_count(): int {
    $post = get_post();
    if (!$post || get_post_type($post) !== 'events') return 0;

    if (!function_exists('patlis_core_resolve_gallery_ids_for_post')) return 0;

    $ids = patlis_core_resolve_gallery_ids_for_post((int) $post->ID, 'events_gallery_ids');
    return count($ids);
}

function patlis_show_previous_events(): int {
    $args = [
        'post_type'      => 'events',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => 'events_date_start',
                'value'   => date('Ymd'),
                'compare' => '<',
                'type'    => 'NUMERIC',
            ],
        ],
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return $q->have_posts() ? 1 : 0;
}

function patlis_show_next_events(): int {
    $args = [
        'post_type'      => 'events',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [
            [
                'key'     => 'events_date_start',
                'value'   => date('Ymd'),
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
        ],
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return $q->have_posts() ? 1 : 0;
}

function patlis_show_next_events_pager(): int {
    $args = [
        'post_type'      => 'events',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'events_date_start',
                'value'   => date('Ymd'),
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
        ],
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
}

function patlis_count_other_upcoming_events(): int {
    $args = [
        'post_type'      => 'events',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'exclude_current'=> true,
        'meta_query'     => [
            [
                'key'     => 'events_date_start',
                'value'   => date('Ymd'),
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ],
        ],
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
}

function patlis_count_gallery_categories(): int {
    $args = [
        'post_type'      => function_exists('patlis_gallery_post_type') ? patlis_gallery_post_type() : 'patlis_gallery',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
}

function patlis_home_gallery_count(): int {
    if (!function_exists('patlis_gallery_get_home_id')
        || !function_exists('patlis_gallery_parse_ids')
        || !function_exists('patlis_gallery_meta_key')) {
        return 0;
    }

    $home_id = patlis_gallery_get_home_id();
    if ($home_id <= 0) return 0;

    $ids = patlis_gallery_parse_ids(get_post_meta($home_id, patlis_gallery_meta_key(), true));
    return count($ids);
}

function patlis_languages_count(): int {
    if (!function_exists('pll_languages_list')) return 1;

    $langs = pll_languages_list(['fields' => 'slug']);
    return is_array($langs) ? count($langs) : 1;
}

/**
 * Helpers: get contact confirmation subject/body in current language.
 */
function patlis_get_contact_confirm_subject(): string {
    $opt = Patlis_Core::get_basic('contact_confirm_subject', []);
    return patlis_core_get_multilang_value($opt);
}

function patlis_get_contact_confirm_body(): string {
    $opt = Patlis_Core::get_basic('contact_confirm_body', []);
    return patlis_core_get_multilang_value($opt);
}

function patlis_core_get_multilang_value($raw): string {
    if (is_string($raw)) return $raw;
    if (!is_array($raw) || empty($raw)) return '';

    $current = function_exists('pll_current_language') ? (string) pll_current_language('slug') : '';
    $default = function_exists('pll_default_language') ? (string) pll_default_language('slug') : '';

    if ($current !== '' && !empty($raw[$current])) return (string) $raw[$current];
    if ($default !== '' && !empty($raw[$default])) return (string) $raw[$default];
    foreach ($raw as $v) { if (!empty($v)) return (string) $v; }
    return '';
}

function patlis_get_white_label_option(): array
{
    $optionName = 'patlis_white_label';

    if (class_exists('Patlis_Core') && defined('Patlis_Core::OPTION_WHITE_LABEL')) {
        $optionName = constant('Patlis_Core::OPTION_WHITE_LABEL');
    }

    $opt = get_option($optionName, []);
    return is_array($opt) ? $opt : [];
}

/**
 * Helper: get reseller domain.
 */
function patlis_get_reseller_domain(): string
{
    $opt = patlis_get_white_label_option();
    $domain = isset($opt['reseller_domain']) ? esc_url_raw((string) $opt['reseller_domain']) : '';

    return $domain !== '' ? $domain : 'https://patlis.com';
}

/**
 * Helper: get reseller company name.
 */
function patlis_get_reseller_company_name(): string
{
    $opt = patlis_get_white_label_option();
    $name = isset($opt['reseller_company_name']) ? sanitize_text_field((string) $opt['reseller_company_name']) : '';

    return $name !== '' ? $name : 'Patlis.com';
}

/**
 * Helper: show legal notice (default: true).
 */
function patlis_show_legal_notice(): bool
{
    $opt = patlis_get_basic_option();
    return !isset($opt['show_legal_notice']) || !empty($opt['show_legal_notice']);
}

/**
 * Helper: show privacy policy (default: true).
 */
function patlis_show_privacy_policy(): bool
{
    $opt = patlis_get_basic_option();
    return !isset($opt['show_privacy_policy']) || !empty($opt['show_privacy_policy']);
}

/**
 * Helper: show terms of use (default: false).
 */
function patlis_show_terms_of_use(): bool
{
    $opt = patlis_get_basic_option();
    return !empty($opt['show_terms_of_use']);
}

/**
 * Read formatting settings from basic.php (your keys).
 */
function patlis_get_format_settings(): array
{
    $opt = patlis_get_basic_option();

    $defaults = [
        'decimals'          => 2,
        'decimal_separator' => ',',
        'currency_symbol'   => '€',
        'currency_position' => 'after',
    ];

    $out = $defaults;

    // decimals 0..2
    if (isset($opt['decimals'])) {
        $d = (int)$opt['decimals'];
        $out['decimals'] = max(0, min(2, $d));
    }

    // decimal divider (your dropdown)
    if (isset($opt['decimal_divider']) && $opt['decimal_divider'] !== '') {
        $allowed = [',', '.', '٫', "'"];
        $sep = (string)$opt['decimal_divider'];
        $out['decimal_separator'] = in_array($sep, $allowed, true) ? $sep : $defaults['decimal_separator'];
    }

    // currency symbol
    if (isset($opt['currency_symbol'])) {
        $out['currency_symbol'] = sanitize_text_field((string)$opt['currency_symbol']);
    }

    // currency position
    if (isset($opt['currency_position'])) {
        $pos = (string)$opt['currency_position'];
        $out['currency_position'] = ($pos === 'before') ? 'before' : 'after';
    }

    return $out;
}

/**
 * Parse number robustly (accepts 4,9  4.9  €4,90  etc.)
 */
function patlis_parse_number($value): ?float
{
    if ($value === null) return null;

    if (is_int($value) || is_float($value)) return (float)$value;

    if (!is_string($value)) return null;

    $v = trim($value);
    if ($v === '') return null;

    // remove spaces incl. nbsp
    $v = str_replace(["\xC2\xA0", ' '], '', $v);

    // keep digits + separators + sign
    $v = preg_replace('/[^0-9\-\+,\.\x{066B}\']+/u', '', $v);
    if ($v === '' || $v === '-' || $v === '+') return null;

    // Determine decimal separator by last occurrence of (comma/dot/arabic/quote)
    if (preg_match_all('/[,\.\x{066B}\']+/u', $v, $m, PREG_OFFSET_CAPTURE)) {
        $last = end($m[0]);
        $sepPos = $last[1];
        $sepLen = strlen($last[0]);

        $after = substr($v, $sepPos + $sepLen);

        // if 1-2 digits after => decimal separator
        if (preg_match('/^\d{1,2}$/', $after)) {
            $before = substr($v, 0, $sepPos);
            $before = preg_replace('/[,\.\x{066B}\']+/u', '', $before);
            $after  = preg_replace('/[,\.\x{066B}\']+/u', '', $after);
            $v = $before . '.' . $after;
        } else {
            // otherwise treat separators as thousands and remove
            $v = preg_replace('/[,\.\x{066B}\']+/u', '', $v);
        }
    }

    return is_numeric($v) ? (float)$v : null;
}

function patlis_format_number($value, ?array $settings = null): string
{
    $settings = $settings ?: patlis_get_format_settings();

    $n = patlis_parse_number($value);
    if ($n === null) return '';

    $decimals = (int)($settings['decimals'] ?? 2);
    $decimals = max(0, min(2, $decimals));

    $sep = (string)($settings['decimal_separator'] ?? ',');

    $formatted = number_format($n, $decimals, '.', '');
    if ($sep !== '.') $formatted = str_replace('.', $sep, $formatted);

    return $formatted;
}

function patlis_format_currency($value, ?array $settings = null): string
{
    $settings = $settings ?: patlis_get_format_settings();

    $amount = patlis_format_number($value, $settings);
    if ($amount === '') return '';

    $symbol = trim((string)($settings['currency_symbol'] ?? '€'));
    if ($symbol === '') return $amount;

    $pos = (string)($settings['currency_position'] ?? 'after');
    $pos = ($pos === 'before') ? 'before' : 'after';

    return ($pos === 'before') ? ($symbol . ' ' . $amount) : ($amount . ' ' . $symbol);
}

/**
 * -------- Bricks wrappers --------
 * Use:
 * {echo:patlis_bricks_currency("{patlis_menu_item_price}")}
 */
function patlis_bricks_currency(string $dynamicTag): string
{
    $raw = $dynamicTag;

    if (function_exists('bricks_render_dynamic_data')) {
        $raw = bricks_render_dynamic_data($dynamicTag);
    }

    return patlis_format_currency($raw);
}

function patlis_bricks_number(string $dynamicTag): string
{
    $raw = $dynamicTag;

    if (function_exists('bricks_render_dynamic_data')) {
        $raw = bricks_render_dynamic_data($dynamicTag);
    }

    return patlis_format_number($raw);
}

/**
 * Usage in Bricks: {echo:patlis_bricks_appearance_mode()}
 */
function patlis_bricks_appearance_mode(): string
{
    return patlis_get_appearance_mode();
}

/**
 * Allow wrappers in Bricks {echo:...}
 */
add_filter('bricks/code/echo_function_names', function ($functions) {
    if (empty($functions)) {
        $functions = [];
    } elseif (is_string($functions)) {
        $functions = array_map('trim', explode(',', $functions));
    } elseif (!is_array($functions)) {
        $functions = [];
    }

    $functions[] = 'patlis_bricks_currency';
    $functions[] = 'patlis_bricks_number';
 	$functions[] = 'patlis_bricks_home_url';
	$functions[] = 'patlis_transl';
	$functions[] = 'patlis_is_local_video_url';
    $functions[] = 'patlis_video_html';
    $functions[] = 'patlis_bricks_appearance_mode';
	$functions[] = 'patlis_get_reseller_domain';
	$functions[] = 'patlis_get_reseller_company_name';
	$functions[] = 'patlis_show_legal_notice';
	$functions[] = 'patlis_show_privacy_policy';
	$functions[] = 'patlis_show_terms_of_use';
	$functions[] = 'patlis_cookies_is_banner_enabled';
	$functions[] = 'patlis_has_reservations';
	$functions[] = 'patlis_get_welcome_video_url';
	$functions[] = 'patlis_home_video_has_url';
	$functions[] = 'patlis_home_video_is_local';
	$functions[] = 'patlis_home_video_html';
	$functions[] = 'patlis_has_gastro';
	$functions[] = 'patlis_has_hotel';
	$functions[] = 'patlis_has_kiosk';
	$functions[] = 'patlis_has_dining';
	$functions[] = 'patlis_core_slides_count';
	$functions[] = 'patlis_top_services_count';
	$functions[] = 'patlis_sticky_services_count';
	$functions[] = 'patlis_more_services_count';
	$functions[] = 'patlis_service_gallery_count';
	$functions[] = 'patlis_event_gallery_count';
	$functions[] = 'patlis_show_previous_events';
	$functions[] = 'patlis_show_next_events';
	$functions[] = 'patlis_show_next_events_pager';
	$functions[] = 'patlis_count_other_upcoming_events';
	$functions[] = 'patlis_count_gallery_categories';
	$functions[] = 'patlis_home_gallery_count';
	$functions[] = 'patlis_languages_count';
	
    return array_unique($functions);
});


function patlis_bricks_home_url(): string
{
    if (function_exists('pll_home_url')) {
        return pll_home_url();
    }

    return home_url('/');
}

/**
 * Returns '1' if the URL is hosted on this site, '0' if external (YouTube, Vimeo, etc.).
 * Used by multiple plugins. Usage: {echo:patlis_is_local_video_url({tag})}
 */
function patlis_is_local_video_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '0';
    }

    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $site_host = is_string($site_host) ? strtolower($site_host) : '';
    $url_host  = wp_parse_url($url, PHP_URL_HOST);

    if ($url_host === null || $url_host === false || $url_host === '') {
        return '1'; // relative = local
    }

    $url_host  = strtolower((string) $url_host);
    $normalize = static function (string $host): string {
        return preg_replace('/^www\./', '', $host) ?? $host;
    };

    return $normalize($url_host) === $normalize($site_host) ? '1' : '0';
}

if (!function_exists('patlis_video_embed_url')) {
    /**
     * Convert video URLs to embeddable URLs.
     * Supports: YouTube, Vimeo. Other URLs are returned as-is.
     */
    function patlis_video_embed_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // YouTube
        if (preg_match('~(?:youtube\.com/watch\?.*v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $url, $m)) {
            $embed = 'https://www.youtube.com/embed/' . $m[1];
            $query = wp_parse_url($url, PHP_URL_QUERY);
            parse_str(is_string($query) ? $query : '', $params);
            if (!empty($params['t'])) {
                $embed .= '?start=' . (int) $params['t'];
            }
            return $embed;
        }

        // Vimeo
        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return $url;
    }
}

if (!function_exists('patlis_video_html')) {
    /**
     * Ready-to-render video HTML (local video tag or responsive iframe embed).
     */
    function patlis_video_html(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (patlis_is_local_video_url($url) === '1') {
            return '<video controls style="width:100%;max-width:100%;" src="' . esc_attr($url) . '">'
                . '<p>Your browser does not support the video tag.</p>'
                . '</video>';
        }

        $embed = patlis_video_embed_url($url);

        return '<div style="position:relative;width:100%;padding-bottom:56.25%;height:0;overflow:hidden;" class="brxe-video">'
            . '<iframe src="' . esc_attr($embed) . '" '
            . 'style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" '
            . 'allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin">'
            . '</iframe>'
            . '</div>';
    }
}

/**
 * Normalize date to Y-m-d.  Accepts: Y-m-d, d/m/Y or m/d/Y (tries to detect), d.m.Y
 */
function patlis_normalize_date_to_ymd(string $date): string
{
    $date = trim($date);
    if ($date === '') return '';

    // already Y-m-d
    if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $date)) {
        return $date;
    }

    // d.m.Y
    if (preg_match('~^\d{1,2}\.\d{1,2}\.\d{4}$~', $date)) {
        [$d, $m, $y] = array_map('intval', explode('.', $date));
        if ($y < 1900 || $y > 2100) return '';
        if ($m < 1 || $m > 12) return '';
        if ($d < 1 || $d > 31) return '';
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    // d/m/Y OR m/d/Y
    if (preg_match('~^\d{1,2}/\d{1,2}/\d{4}$~', $date)) {
        [$a, $b, $y] = array_map('intval', explode('/', $date));
        if ($y < 1900 || $y > 2100) return '';

        // detect:
        // if a > 12 => d/m/Y
        // if b > 12 => m/d/Y
        // else ambiguous => assume d/m/Y (EU)
        if ($a > 12) { $d = $a; $m = $b; }
        elseif ($b > 12) { $m = $a; $d = $b; }
        else { $d = $a; $m = $b; } // assume d/m/Y

        if ($m < 1 || $m > 12) return '';
        if ($d < 1 || $d > 31) return '';
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    return '';
}

/**
 * Center popup visibility rule:
 * - enabled must be true
 * - if start_date is empty => 01.01.1900
 * - if end_date is empty   => 01.01.2100
 * - today between start/end (inclusive), using WP timezone
 */
function patlis_center_popup_should_show(): bool
{
    if (!class_exists('Patlis_Core') || !defined('Patlis_Core::OPTION_CENTER_POPUP')) {
        return false;
    }

    $opt = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
    if (!is_array($opt)) $opt = [];

    if (empty($opt['enabled'])) {
        return false;
    }

    $today = current_time('Y-m-d'); // WP timezone

    $start = isset($opt['start_date']) ? trim((string)$opt['start_date']) : '';
    $end   = isset($opt['end_date']) ? trim((string)$opt['end_date']) : '';

    // defaults (as you requested)
    if ($start === '') $start = '01.01.1900';
    if ($end === '')   $end   = '01.01.2100';

    $start = patlis_normalize_date_to_ymd($start);
    $end   = patlis_normalize_date_to_ymd($end);

    // extra safety
    if ($start === '') $start = '1900-01-01';
    if ($end === '')   $end   = '2100-01-01';

    if ($today < $start) return false;
    if ($today > $end)   return false;

    return true;
}


/**
 * Notification bar visibility rule:
 * - enabled must be true
 * - if start_date is empty => 01.01.1900
 * - if end_date is empty   => 01.01.2100
 * - today between start/end (inclusive), using WP timezone
 */
function patlis_notification_bar_should_show(): bool
{
    if (!class_exists('Patlis_Core') || !defined('Patlis_Core::OPTION_NOTIFICATION_BAR')) {
        return false;
    }

    $opt = get_option(Patlis_Core::OPTION_NOTIFICATION_BAR, []);
    if (!is_array($opt)) $opt = [];

    if (empty($opt['enabled'])) {
        return false;
    }

    $today = current_time('Y-m-d'); // WP timezone

    $start = isset($opt['start_date']) ? trim((string)$opt['start_date']) : '';
    $end   = isset($opt['end_date']) ? trim((string)$opt['end_date']) : '';

    // defaults (same logic as center popup)
    if ($start === '') $start = '01.01.1900';
    if ($end === '')   $end   = '01.01.2100';

    $start = patlis_normalize_date_to_ymd($start);
    $end   = patlis_normalize_date_to_ymd($end);

    // extra safety
    if ($start === '') $start = '1900-01-01';
    if ($end === '')   $end   = '2100-01-01';

    if ($today < $start) return false;
    if ($today > $end)   return false;

    return true;
}


/**
 * Add body classes for notification bar (for CSS variables).
 */
function patlis_filter_body_classes(array $classes): array
{
    if (patlis_notification_bar_should_show()) {
        $classes[] = 'patlis-has-notification-bar';
    }

    return $classes;
}

/**
 * Enforce appearance mode on frontend when mode is locked.
 * This keeps Bricks dark-mode class and localStorage value in sync.
 */
function patlis_output_appearance_mode_lock_script(): void
{
        if (is_admin()) {
                return;
        }

        $mode = patlis_get_appearance_mode();
        if ($mode !== 'dark_only' && $mode !== 'light_only') {
                return;
        }

        $force_dark = $mode === 'dark_only' ? 'true' : 'false';
        ?>
        <script>
            (function () {
                var forceDark = <?php echo $force_dark; ?>;

                function applyLockedMode() {
                    var root = document.documentElement;
                    if (!root) return;

                    root.setAttribute('data-brx-theme', forceDark ? 'dark' : 'light');

                    try {
                        localStorage.setItem('darkMode', forceDark ? 'true' : 'false');
                    } catch (e) {
                        // Ignore storage failures (private mode, blocked storage, etc.)
                    }
                }

                // Run immediately to reduce flash and run again after DOM is ready.
                applyLockedMode();
                document.addEventListener('DOMContentLoaded', applyLockedMode, { once: true });
            })();
        </script>
        <?php
}
add_action('wp_head', 'patlis_output_appearance_mode_lock_script', 1);
