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
    </style>';
});