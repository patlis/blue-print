<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'patlis_accommodation_register_cpt_rooms');

function patlis_accommodation_register_cpt_rooms() {
    if (!function_exists('patlis_accommodation_is_enabled_for_version') || !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    $labels = [
        'name'               => 'Rooms',
        'singular_name'      => 'Room',
        'menu_name'          => 'Rooms',
        'name_admin_bar'     => 'Room',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Room',
        'new_item'           => 'New Room',
        'edit_item'          => 'Edit Room',
        'view_item'          => 'View Room',
        'all_items'          => 'All Rooms',
        'search_items'       => 'Search Rooms',
        'not_found'          => 'No rooms found.',
        'not_found_in_trash' => 'No rooms found in Trash.',
    ];

    register_post_type('patlis_room', [
        'labels'             => $labels,
        'public'             => true,
        'show_in_menu'       => false, // θα το βάλουμε κάτω από Accommodation menu αργότερα
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'rooms', 'with_front' => false],
        'supports'           => ['title', 'editor', 'thumbnail', 'page-attributes'], // page-attributes => menu_order
        'menu_position'      => 58,
        'menu_icon'          => 'dashicons-admin-multisite',
        'capability_type'    => 'post',
        'show_in_rest'       => true,
    ]);
}
