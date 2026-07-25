<?php
if (!defined('ABSPATH')) exit;

function patlis_reservations_option_key(): string
{
    return 'patlis_reservations_settings';
}

function patlis_reservations_defaults(): array
{
    return [
        'mode'           => 'off',   // off | simple | embed | redirect
        'min_hours'      => 6,
        'min_time'       => '09:00',
        'max_time'       => '20:00',
        'notify_email'   => '',
        'email_subject'  => '',
        'confirm_subject'=> [],
        'confirm_body'   => [],
        'embed_code'     => '',
        'redirect_url'   => '',
    ];
}

function patlis_reservations_get_settings(): array
{
    $defaults = patlis_reservations_defaults();
    $saved = get_option(patlis_reservations_option_key(), []);
    if (!is_array($saved)) $saved = [];
    return array_merge($defaults, $saved);
}

/**
 * Helper: get configured recipient email
 */
function patlis_reservations_get_notify_email(): string
{
    $s = patlis_reservations_get_settings();
    return isset($s['notify_email']) ? sanitize_email((string)$s['notify_email']) : '';
}

/**
 * Helper: get confirmation email body in current language (fallback to default).
 */
function patlis_reservations_get_confirm_body(): string
{
    $s   = patlis_reservations_get_settings();
    $raw = $s['confirm_body'] ?? [];

    if (is_string($raw)) return $raw;
    if (!is_array($raw) || empty($raw)) return '';

    $current_lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : '';
    $default_lang = function_exists('pll_default_language') ? (string) pll_default_language('slug') : '';

    if ($current_lang !== '' && !empty($raw[$current_lang])) return (string) $raw[$current_lang];
    if ($default_lang !== '' && !empty($raw[$default_lang])) return (string) $raw[$default_lang];
    foreach ($raw as $v) { if (!empty($v)) return (string) $v; }

    return '';
}

/**
 * Helper: get confirmation email subject in current language (fallback to default).
 */
function patlis_reservations_get_confirm_subject(): string
{
    $s   = patlis_reservations_get_settings();
    $raw = $s['confirm_subject'] ?? [];

    if (is_string($raw)) return $raw;
    if (!is_array($raw) || empty($raw)) return '';

    $current_lang = function_exists('pll_current_language') ? (string) pll_current_language('slug') : '';
    $default_lang = function_exists('pll_default_language') ? (string) pll_default_language('slug') : '';

    if ($current_lang !== '' && !empty($raw[$current_lang])) return (string) $raw[$current_lang];
    if ($default_lang !== '' && !empty($raw[$default_lang])) return (string) $raw[$default_lang];
    foreach ($raw as $v) { if (!empty($v)) return (string) $v; }

    return '';
}