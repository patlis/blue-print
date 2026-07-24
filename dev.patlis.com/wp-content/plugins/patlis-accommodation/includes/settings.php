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

function patlis_acc_show_room_pager(): int {
    $s        = function_exists('patlis_accommodation_get_settings') ? patlis_accommodation_get_settings() : [];
    $per_page = (int) ($s['rooms_per_page'] ?? 0);
    if ($per_page <= 0) return 0;

    $args = [
        'post_type'      => 'patlis_room',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return $q->found_posts > $per_page ? 1 : 0;
}

function patlis_acc_count_top_rooms(): int {
    $args = [
        'post_type'      => 'patlis_room',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'room_sticky',
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

function patlis_acc_top_experience_count(): int {
    $args = [
        'post_type'      => 'experience',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => '_thumbnail_id',
                'value'   => '',
                'compare' => '!=',
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

function patlis_rates_count_for_current_room(): int {
    $args = [
        'post_type'      => 'rates',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'package_enabled',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
    ];

    if (function_exists('patlis_rates_query_for_current_room')) {
        $args = patlis_rates_query_for_current_room($args);
    } elseif (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
}

function patlis_acc_rooms_count(): int {
    $args = [
        'post_type'      => 'patlis_room',
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

function patlis_acc_top_packages_count(): int {
    $args = [
        'post_type'      => 'rates',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => false,
        'meta_query'     => [
            [
                'key'     => 'package_enabled',
                'value'   => '1',
                'compare' => '=',
            ],
            [
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ],
        ],
    ];

    if (function_exists('patlis_fallback_posts_query')) {
        $args = patlis_fallback_posts_query($args);
    }

    $q = new WP_Query($args);
    return (int) $q->found_posts;
}

function patlis_acc_offer_applied_rooms(): int {
    $post = get_post();
    if (!$post) return 0;

    $pid = (int) $post->ID;

    // ACF get_field handles relationship/text fields correctly
    if (function_exists('get_field')) {
        $val = get_field('package_linked_rooms', $pid);
        if (is_array($val)) {
            return count(array_filter(array_map('intval', $val), static fn($id) => $id > 0));
        }
        if (is_string($val) && $val !== '') {
            $ids = preg_split('/\s*,\s*/', $val, -1, PREG_SPLIT_NO_EMPTY);
            return count(array_filter(array_map('intval', $ids), static fn($id) => $id > 0));
        }
        return 0;
    }

    // Fallback: raw meta (array or comma-separated string)
    $raw = get_post_meta($pid, 'package_linked_rooms', true);

    if (is_array($raw)) {
        return count(array_filter(array_map('intval', $raw), static fn($id) => $id > 0));
    }

    if (empty($raw) || !is_string($raw)) return 0;

    $ids = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    return count(array_values(array_filter(array_map('intval', $ids), static fn($id) => $id > 0)));
}

