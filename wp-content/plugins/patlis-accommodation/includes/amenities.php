<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    if (function_exists('patlis_accommodation_is_enabled_for_version') && !patlis_accommodation_is_enabled_for_version()) {
      return;
    }

    register_taxonomy('room_amenity', ['patlis_room'], [
        'labels' => [
            'name'          => __('Amenities', 'patlis-accommodation'),
            'singular_name' => __('Amenity', 'patlis-accommodation'),
            'search_items'  => __('Search amenities', 'patlis-accommodation'),
            'all_items'     => __('All amenities', 'patlis-accommodation'),
            'edit_item'     => __('Edit amenity', 'patlis-accommodation'),
            'update_item'   => __('Update amenity', 'patlis-accommodation'),
            'add_new_item'  => __('Add new amenity', 'patlis-accommodation'),
            'new_item_name' => __('New amenity name', 'patlis-accommodation'),
            'menu_name'     => __('Amenities', 'patlis-accommodation'),
        ],
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'hierarchical'      => true,
        'rewrite'           => false,
        'meta_box_cb'  => 'post_categories_meta_box',
    ]);

}, 5);
