<?php
if (!defined('ABSPATH')) exit;

define('PATLIS_ACCOMMODATION_SETTINGS_KEY', 'patlis_accommodation_settings');

function patlis_accommodation_settings_defaults(): array {
    return [
        'booking_mode'         => 1,
        'booking_email'        => '',
        'booking_days_before'  => 0,
        'booking_redirect_url' => '',
        'booking_3party_code'  => '',
        'rooms_per_page'   => 0,            // 0 = all
        'show_prices'      => 0,            // 0/1
        'prices_text'      => '',
    ];
}

function patlis_accommodation_get_settings(): array {
    $defaults = patlis_accommodation_settings_defaults();
    $saved = get_option(PATLIS_ACCOMMODATION_SETTINGS_KEY, []);
    if (!is_array($saved)) $saved = [];
    return array_merge($defaults, $saved);
}

/* ============================================================
 * Shortcodes for Bricks
 * ============================================================ */

function patlis_acc_get_setting_value(string $key) {
    if (!function_exists('patlis_accommodation_get_settings')) return '';
    $s = patlis_accommodation_get_settings();
    return $s[$key] ?? '';
}

add_shortcode('patlis_acc_booking_mode', function () {
    return (string) (int) patlis_acc_get_setting_value('booking_mode'); // "0" or "1"
});

add_shortcode('patlis_acc_booking_email', function () {
    return (string) patlis_acc_get_setting_value('booking_email');
});

add_shortcode('patlis_acc_booking_days_before', function () {
    return (string) (int) patlis_acc_get_setting_value('booking_days_before');
});

add_shortcode('patlis_acc_booking_redirect_url', function () {
    return (string) patlis_acc_get_setting_value('booking_redirect_url');
});

add_shortcode('patlis_acc_booking_3party_code', function () {
    // επιστρέφει το αποθηκευμένο snippet (έχει ήδη wp_kses_post στο save)
    return (string) patlis_acc_get_setting_value('booking_3party_code');
});

add_shortcode('patlis_acc_rooms_per_page', function () {
    return (string) (int) patlis_acc_get_setting_value('rooms_per_page'); // 0 = all
});

add_shortcode('patlis_acc_show_prices', function () {
    return !empty(patlis_acc_get_setting_value('show_prices')) ? '1' : '0';
});

add_shortcode('patlis_acc_prices_text', function () {
    return (string) patlis_acc_get_setting_value('prices_text');
});

