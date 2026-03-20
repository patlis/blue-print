<?php
if (!defined('ABSPATH')) exit;

function patlis_reservations_option_key(): string
{
    return 'patlis_reservations_settings';
}

function patlis_reservations_defaults(): array
{
    return [
        'mode'           => 'off',   // off | simple | embed
        'min_hours'      => 6,
        'min_time'       => '09:00',
        'max_time'       => '20:00',
        'notify_user_id' => 0,       // WP user id
        'embed_code'     => '',
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
 * Helper: get selected user's email
 */
function patlis_reservations_get_notify_email(): string
{
    $s = patlis_reservations_get_settings();
    $uid = (int)($s['notify_user_id'] ?? 0);

    if ($uid <= 0) return '';

    $u = get_user_by('id', $uid);
    if (!$u) return '';

    return sanitize_email($u->user_email);
}