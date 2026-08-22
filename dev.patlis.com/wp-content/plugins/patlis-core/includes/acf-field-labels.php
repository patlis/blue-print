<?php

if (!defined('ABSPATH')) {
    exit;
}

add_filter('acf/load_field_group', function ($field_group) {
    $titles = [
        'events'            => __('Events', 'patlis-core'),
        'experience'        => __('Experience', 'patlis-core'),
        'offers & packages' => __('Offers and packages', 'patlis-core'),
        'reviews'           => __('Reviews', 'patlis-core'),
        'services'          => __('Services', 'patlis-core'),
        'slides'            => __('Slides', 'patlis-core'),
        'timeline'          => __('Timeline', 'patlis-core'),
    ];

    $title = isset($field_group['title']) ? strtolower(trim((string) $field_group['title'])) : '';
    if (isset($titles[$title])) {
        $field_group['title'] = $titles[$title];
    }

    return $field_group;
}, 20, 1);

add_filter('acf/load_field', function ($field) {
    $labels = [
        /* Events */
        'events_small_description' => __('Short description', 'patlis-core'),
        'events_date_start'        => __('Start date', 'patlis-core'),
        'events_time_start'        => __('Start time', 'patlis-core'),
        'events_date_end'          => __('End date', 'patlis-core'),
        'events_time_end'          => __('End time', 'patlis-core'),
        'events_video_url'         => __('Video URL', 'patlis-core'),
        /* Experience */
        'experience_order'     => __('Display order', 'patlis-core'),
        'experience_link_text' => __('Link text', 'patlis-core'),
        'experience_link_url'  => __('Link URL', 'patlis-core'),
        /* Offers & Packages */
        'packages_short_description' => __('Short description', 'patlis-core'),
        'packages_valid_from'        => __('Valid from', 'patlis-core'),
        'packages_valid_until'       => __('Valid until', 'patlis-core'),
        'package_price_text'         => __('Price text', 'patlis-core'),
        'packages_discount'          => __('Discount (%)', 'patlis-core'),
        'package_order'              => __('Display order', 'patlis-core'),
        'package_enabled'            => __('Enable', 'patlis-core'),
        'package_linked_rooms'       => __('Linked rooms', 'patlis-core'),
        'package_booking_url'        => __('Booking URL (redirect mode)', 'patlis-core'),
        /* Reviews */
        'review_rating'   => __('Stars', 'patlis-core'),
        'review_date'     => __('Date', 'patlis-core'),
        'review_text'     => __('Review text', 'patlis-core'),
        'review_source'   => __('Source', 'patlis-core'),
        'review_show'     => __('Enable', 'patlis-core'),
        'review_featured' => __('Show on the home page', 'patlis-core'),
        /* Services */
        'service_small_description' => __('Short description', 'patlis-core'),
        'create_service_page'       => __('Create service page', 'patlis-core'),
        'service_sticky'            => __('Show on the home page', 'patlis-core'),
        'service_show'              => __('Enable', 'patlis-core'),
        'service_order'             => __('Display order', 'patlis-core'),
        'service_video_url'         => __('Video URL', 'patlis-core'),
        /* Slides */
        'slide_sort'      => __('Display order', 'patlis-core'),
        /* Timeline */
        'timeline_date'  => __('Date (optional)', 'patlis-core'),
        'timeline_image' => __('Image (optional)', 'patlis-core'),
        'timeline_sort'  => __('Display order', 'patlis-core'),

    ];

    $field_name = isset($field['name']) ? (string) $field['name'] : '';
    if (isset($labels[$field_name])) {
        $field['label'] = $labels[$field_name];
    }

    return $field;
}, 20, 1);