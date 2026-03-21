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