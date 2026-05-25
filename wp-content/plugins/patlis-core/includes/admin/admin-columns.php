<?php
if (!defined('ABSPATH')) exit;

add_action('admin_init', function () {
    $post_types = get_post_types(['show_ui' => true], 'names');

    foreach ($post_types as $post_type) {
        if (in_array($post_type, ['attachment', 'acf-field', 'acf-field-group'], true)) {
            continue;
        }

        add_filter("manage_{$post_type}_posts_columns", 'patlis_add_featured_image_column');
        add_action("manage_{$post_type}_posts_custom_column", 'patlis_render_featured_image_column', 10, 2);
    }
});

function patlis_add_featured_image_column(array $columns): array
{
    $new = [];

    foreach ($columns as $key => $label) {
        if ($key === 'title') {
            $new['patlis_featured_image'] = 'Image';
        }

        $new[$key] = $label;
    }

    return $new;
}

function patlis_render_featured_image_column(string $column, int $post_id): void
{
    if ($column !== 'patlis_featured_image') {
        return;
    }

    if (has_post_thumbnail($post_id)) {
        echo get_the_post_thumbnail($post_id, [60, 60], [
            'style' => 'width:60px;height:60px;object-fit:cover;border-radius:4px;display:block;'
        ]);
    } else {
        echo '—';
    }
}

add_action('admin_head', function () {
    echo '<style>
        .column-patlis_featured_image {
            width: 80px;
            text-align: center;
        }
        .column-patlis_featured_image img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .column-patlis_event_start {
            width: 180px;
        }
    </style>';
});

add_filter('manage_events_posts_columns', function (array $columns): array {
    $new = [];

    foreach ($columns as $key => $label) {
        $new[$key] = $label;

        if ($key === 'title') {
            $new['patlis_event_start'] = 'Start Date';
        }
    }

    if (!isset($new['patlis_event_start'])) {
        $new['patlis_event_start'] = 'Start Date';
    }

    return $new;
});

add_action('manage_events_posts_custom_column', function (string $column, int $post_id): void {
    if ($column !== 'patlis_event_start') {
        return;
    }

    $raw_value = get_post_meta($post_id, 'events_date_start', true);

    if (!is_string($raw_value) || trim($raw_value) === '') {
        echo '—';
        return;
    }

    $raw_value = trim($raw_value);
    $timestamp = strtotime($raw_value);

    if ($timestamp === false) {
        echo esc_html($raw_value);
        return;
    }

    $is_past = $timestamp < current_time('timestamp');
    $style = $is_past ? 'color:#c62828;font-weight:600;' : '';

    echo '<span style="' . esc_attr($style) . '">' . esc_html(date_i18n('d M Y', $timestamp)) . '</span>';
}, 10, 2);

add_filter('manage_edit-events_sortable_columns', function (array $columns): array {
    $columns['patlis_event_start'] = 'patlis_event_start';

    return $columns;
});

add_action('pre_get_posts', function (WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $post_type = $query->get('post_type');
    if ($post_type !== 'events') {
        return;
    }

    $requested_orderby = isset($_GET['orderby']) ? sanitize_key((string) wp_unslash($_GET['orderby'])) : '';
    $is_default_admin_load = ($requested_orderby === '');
    $is_start_date_sort = ($query->get('orderby') === 'patlis_event_start');

    if (!$is_default_admin_load && !$is_start_date_sort) {
        return;
    }

    $query->set('meta_key', 'events_date_start');
    $query->set('orderby', 'meta_value');

    if ($is_default_admin_load && !isset($_GET['order'])) {
        $query->set('order', 'DESC');
    }
});