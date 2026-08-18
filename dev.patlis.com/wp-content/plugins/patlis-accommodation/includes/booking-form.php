<?php
if (!defined('ABSPATH')) exit;

/**
 * Patlis Accommodation - Booking helpers
 * - REST endpoint: /wp-json/patlis-acc/v1/rooms
 * - Frontend script support for booking form fields
 */

function patlis_acc_get_rooms_options_string(): string
{
    $q = new WP_Query([
        'post_type'      => 'patlis_room',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    if (empty($q->posts)) return '';

    $lines = [];
    foreach ($q->posts as $pid) {
        $lines[] = get_the_title((int) $pid) . '|' . (int) $pid;
    }

    return implode("\n", $lines);
}

/* ============================================================
 * Cache clear for rooms list (transient)
 * ============================================================ */
function patlis_acc_rooms_list_cache_clear(): void
{
    delete_transient('patlis_acc_rooms_list_v2');

    if (function_exists('pll_languages_list')) {
        $langs = pll_languages_list(['fields' => 'slug']);
        if (is_array($langs)) {
            foreach ($langs as $lang) {
                if (!is_string($lang) || $lang === '') continue;
                delete_transient('patlis_acc_rooms_list_v2_' . $lang);
            }
        }
    }
}

add_action('save_post_patlis_room', function ($post_id) {
    // ignore autosave & revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    patlis_acc_rooms_list_cache_clear();
});

add_action('trashed_post', function ($post_id) {
    if (get_post_type($post_id) === 'patlis_room') {
        patlis_acc_rooms_list_cache_clear();
    }
});

add_action('deleted_post', function ($post_id) {
    if (get_post_type($post_id) === 'patlis_room') {
        patlis_acc_rooms_list_cache_clear();
    }
});

/* ============================================================
 * 1) REST endpoint: rooms list
 * ============================================================ */
add_action('rest_api_init', function () {
    register_rest_route('patlis-acc/v1', '/rooms', [
        'methods'  => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function () {

            $cache_key = 'patlis_acc_rooms_list_v2';
            if (function_exists('pll_current_language')) {
                $lang = pll_current_language('slug');
                if (is_string($lang) && $lang !== '') {
                    $cache_key .= '_' . $lang;
                }
            }

            $cached = get_transient($cache_key);
            if (is_array($cached)) {
                return rest_ensure_response($cached);
            }

            $q = new WP_Query([
                'post_type'      => 'patlis_room',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]);

            $out = [];
            if (!empty($q->posts)) {
                foreach ($q->posts as $pid) {
                    $out[] = [
                        'id'         => (int) $pid,
                        'title'      => (string) get_the_title($pid),
                        'slug'       => (string) get_post_field('post_name', $pid),
                        'meal_plans' => function_exists('patlis_acc_get_room_meal_plans')
                            ? patlis_acc_get_room_meal_plans((int) $pid)
                            : [],
                    ];
                }
            }

            set_transient($cache_key, $out, 10 * MINUTE_IN_SECONDS);

            return rest_ensure_response($out);
        },
    ]);
});

