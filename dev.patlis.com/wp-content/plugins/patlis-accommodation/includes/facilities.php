<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {

    if (function_exists('patlis_accommodation_is_enabled_for_version') && !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    register_taxonomy('property_facility', [], [
        'labels' => [
            'name'          => __('Facilities', 'patlis-accommodation'),
            'singular_name' => __('Facility', 'patlis-accommodation'),
            'search_items'  => __('Search facilities', 'patlis-accommodation'),
            'all_items'     => __('All facilities', 'patlis-accommodation'),
            'edit_item'     => __('Edit facility', 'patlis-accommodation'),
            'update_item'   => __('Update facility', 'patlis-accommodation'),
            'add_new_item'  => __('Add new facility', 'patlis-accommodation'),
            'new_item_name' => __('New facility name', 'patlis-accommodation'),
            'menu_name'     => __('Facilities', 'patlis-accommodation'),
        ],
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => false,
        'show_in_rest'      => true,
        'hierarchical'      => true,
        'rewrite'           => false,

        // Property-level: δεν θέλουμε meta box μέσα στο Room editor (για να μη μπερδεύει)
        'meta_box_cb'       => false,
    ]);

}, 5);
