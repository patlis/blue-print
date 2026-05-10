<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'patlis_accommodation_register_cpt_rooms');
add_action('init', 'patlis_accommodation_register_cpt_experience');
add_action('init', 'patlis_accommodation_register_cpt_rates');

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

function patlis_accommodation_register_cpt_experience() {
    if (!function_exists('patlis_accommodation_is_enabled_for_version') || !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    $labels = [
        'name'               => 'Experiences',
        'singular_name'      => 'Experience',
        'menu_name'          => 'Experiences',
        'name_admin_bar'     => 'Experience',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Experience',
        'new_item'           => 'New Experience',
        'edit_item'          => 'Edit Experience',
        'view_item'          => 'View Experience',
        'all_items'          => 'All Experiences',
        'search_items'       => 'Search Experiences',
        'not_found'          => 'No experiences found.',
        'not_found_in_trash' => 'No experiences found in Trash.',
    ];

    register_post_type('experience', [
        'labels'             => $labels,
        'public'             => true,
        'show_in_menu'       => false,
        'has_archive'        => false,
        'rewrite'            => false,
        'supports'           => ['title', 'editor', 'thumbnail', 'page-attributes'],
        'menu_position'      => 59,
        'menu_icon'          => 'dashicons-location-alt',
        'capability_type'    => 'post',
        'show_in_rest'       => true,
    ]);
}

function patlis_accommodation_register_cpt_rates() {
    if (!function_exists('patlis_accommodation_is_enabled_for_version') || !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    $labels = [
        'name'               => 'Offers & Packages',
        'singular_name'      => 'Offer / Package',
        'menu_name'          => 'Offers & Packages',
        'name_admin_bar'     => 'Offer / Package',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Offer / Package',
        'new_item'           => 'New Offer / Package',
        'edit_item'          => 'Edit Offer / Package',
        'view_item'          => 'View Offer / Package',
        'all_items'          => 'All Offers & Packages',
        'search_items'       => 'Search Offers & Packages',
        'not_found'          => 'No offers or packages found.',
        'not_found_in_trash' => 'No offers or packages found in Trash.',
    ];

    register_post_type('rates', [
        'labels'             => $labels,
        'public'             => true,
        'show_in_menu'       => false,
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'rates', 'with_front' => false],
        'supports'           => ['title', 'editor', 'thumbnail', 'page-attributes'],
        'menu_position'      => 60,
        'menu_icon'          => 'dashicons-money-alt',
        'capability_type'    => 'post',
        'show_in_rest'       => true,
    ]);
}
