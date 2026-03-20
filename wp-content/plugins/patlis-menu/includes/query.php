<?php
if (!defined('ABSPATH')) exit;

/**
 * Return Bricks query args for menu_item loop with Polylang fallback.
 *
 * Usage in Bricks Query editor (PHP):
 *
 * return patlis_menu_items_query([
 *     'term_id' => {term_id},
 * ]);
 */
function patlis_menu_items_query(array $args = []): array
{
    $term_id = isset($args['term_id']) ? (int) $args['term_id'] : 0;

    if (!$term_id) {
        return [
            'post_type'      => 'menu_item',
            'post__in'       => [0],
            'orderby'        => 'post__in',
            'posts_per_page' => -1,
            'lang'           => '',
        ];
    }

    $post_ids = patlis_menu_get_fallback_post_ids_for_term($term_id);

    if (empty($post_ids)) {
        $post_ids = [0];
    }

    return [
        'post_type'      => 'menu_item',
        'post_status'    => 'publish',
        'post__in'       => $post_ids,
        'orderby'        => 'post__in',
        'posts_per_page' => -1,
        'lang'           => '',
    ];
}

/**
 * Return final sorted fallback-aware menu_item IDs for one menu_section term.
 */
function patlis_menu_get_fallback_post_ids_for_term(int $current_term_id): array
{
    if (!$current_term_id) {
        return [];
    }

    $current_lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';
    $default_lang = function_exists('pll_default_language') ? pll_default_language('slug') : '';

    $term_ids = [$current_term_id];

    if ($default_lang && function_exists('pll_get_term')) {
        $default_term_id = (int) pll_get_term($current_term_id, $default_lang);

        if ($default_term_id && $default_term_id !== $current_term_id) {
            $term_ids[] = $default_term_id;
        }
    }

    $raw_ids = get_posts([
        'post_type'      => 'menu_item',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'lang'           => '',
        'meta_query'     => [
            [
                'key'     => 'pmi_show',
                'value'   => '1',
                'compare' => '=',
            ],
        ],
        'tax_query'      => [
            [
                'taxonomy' => 'menu_section',
                'field'    => 'term_id',
                'terms'    => $term_ids,
                'operator' => 'IN',
            ],
        ],
    ]);

    if (empty($raw_ids)) {
        return [];
    }

    $final_ids = [];

    foreach ($raw_ids as $post_id) {
        $final_id = (int) $post_id;

        if ($current_lang && function_exists('pll_get_post')) {
            $translated_id = (int) pll_get_post($post_id, $current_lang);

            if ($translated_id > 0) {
                $final_id = $translated_id;
            }
        }

        $final_ids[$final_id] = $final_id;
    }

    if (empty($final_ids)) {
        return [];
    }

    $final_posts = [];

    foreach (array_values($final_ids) as $final_id) {
        $post = get_post($final_id);

        if ($post instanceof WP_Post && $post->post_type === 'menu_item' && $post->post_status === 'publish') {
            $final_posts[] = $post;
        }
    }
    
    $sort_map = [];
    $itemnr_map = [];
    
    foreach ($final_posts as $post) {
        $sort_map[$post->ID]   = get_post_meta($post->ID, 'pmi_sort', true);
        $itemnr_map[$post->ID] = trim((string) get_post_meta($post->ID, 'pmi_itemnr', true));
    }

    usort($final_posts, function ($a, $b) use ($sort_map, $itemnr_map) {
    
        $a_sort_raw = $sort_map[$a->ID];
        $b_sort_raw = $sort_map[$b->ID];
    
        $a_has_sort = ($a_sort_raw !== '' && $a_sort_raw !== null);
        $b_has_sort = ($b_sort_raw !== '' && $b_sort_raw !== null);
    
        if ($a_has_sort && !$b_has_sort) {
            return -1;
        }
    
        if (!$a_has_sort && $b_has_sort) {
            return 1;
        }
    
        if ($a_has_sort && $b_has_sort) {
            $a_sort = (int) $a_sort_raw;
            $b_sort = (int) $b_sort_raw;
    
            if ($a_sort !== $b_sort) {
                return $a_sort <=> $b_sort;
            }
        }
    
        $a_itemnr = $itemnr_map[$a->ID];
        $b_itemnr = $itemnr_map[$b->ID];
    
        if ($a_itemnr !== $b_itemnr) {
            return strnatcasecmp($a_itemnr, $b_itemnr);
        }
    
        return strcasecmp($a->post_title, $b->post_title);
    });

    return array_map(function ($post) {
        return (int) $post->ID;
    }, $final_posts);
}