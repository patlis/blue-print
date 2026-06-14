<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {

    if (function_exists('patlis_accommodation_is_enabled_for_version') && !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    register_taxonomy('property_service', [], [
        'labels' => [
            'name'          => __('Services', 'patlis-accommodation'),
            'singular_name' => __('Service', 'patlis-accommodation'),
            'search_items'  => __('Search services', 'patlis-accommodation'),
            'all_items'     => __('All services', 'patlis-accommodation'),
            'edit_item'     => __('Edit service', 'patlis-accommodation'),
            'update_item'   => __('Update service', 'patlis-accommodation'),
            'add_new_item'  => __('Add new service', 'patlis-accommodation'),
            'new_item_name' => __('New service name', 'patlis-accommodation'),
            'menu_name'     => __('Services', 'patlis-accommodation'),
        ],
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => false,
        'show_in_rest'      => true,
        'hierarchical'      => true,
        'rewrite'           => false,

        // Property-level: χωρίς meta box στο Room editor
        'meta_box_cb'       => false,
    ]);

}, 5);
