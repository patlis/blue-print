<?php
if (!defined('ABSPATH')) exit;

/**
 * Admin list columns for Offers & Packages (rates CPT)
 */

// Add "Order" column
add_filter('manage_rates_posts_columns', function ($columns) {
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['package_order'] = 'Order';
        }
    }
    return $new;
});

// Render column value
add_action('manage_rates_posts_custom_column', function ($column, $post_id) {
    if ($column === 'package_order') {
        $val = get_post_meta((int) $post_id, 'package_order', true);
        echo $val !== '' ? (int) $val : '—';
    }
}, 10, 2);

// Make it sortable
add_filter('manage_edit-rates_sortable_columns', function ($columns) {
    $columns['package_order'] = 'package_order';
    return $columns;
});

// Handle the sort query
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'rates') return;
    if ($query->get('orderby') !== 'package_order') return;

    $query->set('meta_key', 'package_order');
    $query->set('orderby', 'meta_value_num');
});
