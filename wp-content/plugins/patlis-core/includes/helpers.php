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
