<?php

if (!defined('ABSPATH')) {
    exit;
}

function patlis_cpt_common_labels(): array
{
    return [
        'all_items'          => __('All', 'patlis-core'),
        'add_new'            => __('Add new', 'patlis-core'),
        'add_new_item'       => __('Add new', 'patlis-core'),
        'edit_item'          => __('Edit', 'patlis-core'),
        'new_item'           => __('New', 'patlis-core'),
        'view_item'          => __('View', 'patlis-core'),
        'search_items'       => __('Search', 'patlis-core'),
        'not_found'          => __('Not found.', 'patlis-core'),
        'not_found_in_trash' => __('Not found in Trash.', 'patlis-core'),
    ];
}

add_filter('register_post_type_args', function (array $args, string $post_type): array {
    $post_types = [
        'events' => [
            'plural'   => __('Events', 'patlis-core'),
            'singular' => __('Event', 'patlis-core'),
        ],
        'services' => [
            'plural'   => __('Services', 'patlis-core'),
            'singular' => __('Service', 'patlis-core'),
        ],
        'reviews' => [
            'plural'   => __('Reviews', 'patlis-core'),
            'singular' => __('Review', 'patlis-core'),
        ],
        'timeline_item' => [
            'plural'   => __('Timelines', 'patlis-core'),
            'singular' => __('Timeline', 'patlis-core'),
        ],
        'slide' => [
            'plural'   => __('Slides', 'patlis-core'),
            'singular' => __('Slide', 'patlis-core'),
        ],
    ];

    if (!isset($post_types[$post_type])) {
        return $args;
    }

    $plural = $post_types[$post_type]['plural'];
    $singular = $post_types[$post_type]['singular'];

    $labels = array_merge([
        'name'           => $plural,
        'singular_name'  => $singular,
        'menu_name'      => $plural,
        'name_admin_bar' => $singular,
    ], patlis_cpt_common_labels());

    $args['label'] = $labels['name'];
    $args['labels'] = array_merge(
        isset($args['labels']) && is_array($args['labels']) ? $args['labels'] : [],
        $labels
    );

    return $args;
}, 20, 2);
