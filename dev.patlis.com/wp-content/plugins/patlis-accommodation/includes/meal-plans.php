<?php
if (!defined('ABSPATH')) exit;

function patlis_acc_meal_plan_default_post_id(int $post_id): int
{
    if ($post_id <= 0 || !function_exists('pll_default_language') || !function_exists('pll_get_post')) {
        return $post_id;
    }

    $default_lang = pll_default_language('slug');
    $default_post_id = is_string($default_lang) && $default_lang !== ''
        ? (int) pll_get_post($post_id, $default_lang)
        : 0;

    return $default_post_id > 0 ? $default_post_id : $post_id;
}

/* ============================================================
 * Helper: get all meal plans globally, sorted by price.
 * Used by REST endpoint (booking-form.php) and Bricks tags.
 * ============================================================ */
function patlis_acc_get_room_meal_plans(int $room_id = 0): array
{
    unset($room_id);

    $current_lang = '';
    $default_lang = '';
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if (is_string($lang) && $lang !== '') {
            $current_lang = $lang;
        }
    }
    if (function_exists('pll_default_language')) {
        $lang = pll_default_language('slug');
        if (is_string($lang) && $lang !== '') {
            $default_lang = $lang;
        }
    }

    $args = [
        'post_type'      => 'patlis_meal_plan',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ];

    if ($current_lang !== '') {
        $args['lang'] = $current_lang;
    }

    $plans_posts = get_posts($args);
    if ((empty($plans_posts) || !is_array($plans_posts)) && $default_lang !== '' && $default_lang !== $current_lang) {
        $fallback_args = $args;
        $fallback_args['lang'] = $default_lang;
        $plans_posts = get_posts($fallback_args);
    }

    if (empty($plans_posts) || !is_array($plans_posts)) return [];

    $plans = [];
    foreach ($plans_posts as $plan_post) {
        $pid = (int) $plan_post->ID;
        $default_pid = patlis_acc_meal_plan_default_post_id($pid);
        $plans[] = [
            'id'          => $pid,
            'name'        => (string) get_the_title($pid),
            'price_adult' => (float)  get_post_meta($default_pid, 'patlis_meal_plan_price_adult', true),
            'is_default'  => (bool)   get_post_meta($default_pid, 'patlis_meal_plan_is_default', true),
            'description' => (string) get_post_meta($pid, 'patlis_meal_plan_description', true),
        ];
    }

    usort($plans, function ($a, $b) {
        if ($a['price_adult'] === $b['price_adult']) {
            return strcasecmp((string) $a['name'], (string) $b['name']);
        }
        return $a['price_adult'] <=> $b['price_adult'];
    });

    return $plans;
}

if (!function_exists('patlis_acc_meal_plan_description')) {
    function patlis_acc_meal_plan_description(?int $post_id = null): string
    {
        $post_id = (int) ($post_id ?? get_the_ID());
        if ($post_id <= 0) {
            return '';
        }

        $desc = (string) get_post_meta($post_id, 'patlis_meal_plan_description', true);
        if (trim($desc) !== '') {
            return $desc;
        }

        if (!function_exists('pll_default_language') || !function_exists('pll_get_post')) {
            return $desc;
        }

        $default_lang = pll_default_language('slug');
        if (!is_string($default_lang) || $default_lang === '') {
            return $desc;
        }

        $default_id = (int) pll_get_post($post_id, $default_lang);
        if ($default_id <= 0 || $default_id === $post_id) {
            return $desc;
        }

        $fallback = (string) get_post_meta($default_id, 'patlis_meal_plan_description', true);
        return trim($fallback) !== '' ? $fallback : $desc;
    }
}

if (!function_exists('patlis_acc_meal_plan_price_adult')) {
    function patlis_acc_meal_plan_price_adult(?int $post_id = null): string
    {
        $post_id = (int) ($post_id ?? get_the_ID());
        if ($post_id <= 0 || !function_exists('patlis_format_currency')) {
            return '';
        }

        $default_post_id = patlis_acc_meal_plan_default_post_id($post_id);
        return patlis_format_currency(get_post_meta($default_post_id, 'patlis_meal_plan_price_adult', true));
    }
}

if (!function_exists('patlis_acc_meal_plans_count')) {
    function patlis_acc_meal_plans_count(): string
    {
        $plans = function_exists('patlis_acc_get_room_meal_plans') ? patlis_acc_get_room_meal_plans(0) : [];
        return (string) count(is_array($plans) ? $plans : []);
    }
}
