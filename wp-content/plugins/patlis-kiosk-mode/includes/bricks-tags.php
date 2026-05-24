<?php
if (!defined('ABSPATH')) exit;

/**
 * Register Bricks Builder Dynamic Data tags for Kiosk Slides.
 */

// 1. Add the tags to the Bricks dynamic data dropdown
function patlis_kiosk_add_bricks_dynamic_tags($tags) {
    if (!is_array($tags)) {
        return $tags;
    }

    $group = 'Patlis – Kiosk Mode';

    $tags[] = ['name' => '{kiosk_slide_type}',           'label' => 'Slide Type (image, video, html)', 'group' => $group];
    $tags[] = ['name' => '{kiosk_video_url}',            'label' => 'Video URL',                       'group' => $group];
    $tags[] = ['name' => '{kiosk_html_content}',         'label' => 'HTML Content',                    'group' => $group];
    $tags[] = ['name' => '{kiosk_html_position}',        'label' => 'HTML Position Class',             'group' => $group];
    $tags[] = ['name' => '{kiosk_html_theme}',           'label' => 'HTML Theme Class',                'group' => $group];
    $tags[] = ['name' => '{kiosk_html_bg_color}',        'label' => 'Background Color (Hex)',          'group' => $group];
    $tags[] = ['name' => '{kiosk_html_bg_image}',        'label' => 'Background Image URL',            'group' => $group];
    $tags[] = ['name' => '{kiosk_html_overlay_opacity}', 'label' => 'Overlay Opacity (%)',             'group' => $group];
    $tags[] = ['name' => '{kiosk_slide_duration}',       'label' => 'Duration (seconds)',              'group' => $group];
    $tags[] = ['name' => '{kiosk_menu_order}',           'label' => 'Display Order',                   'group' => $group];

    return $tags;
}
add_filter('bricks/dynamic_tags_list', 'patlis_kiosk_add_bricks_dynamic_tags');

// 2. Render the actual data when the tags are used
function patlis_kiosk_render_bricks_dynamic_tags_content($content) {
    if (!is_string($content) || strpos($content, '{kiosk_') === false) {
        return $content;
    }
    
    // Determine the post ID context based on where Bricks is currently processing
    $post_id = 0;

    // Check if we are inside a Bricks loop
    if (class_exists('\Bricks\Query')) {
        $loop_item = \Bricks\Query::get_loop_object();
        if ($loop_item && is_a($loop_item, 'WP_Post')) {
            $post_id = $loop_item->ID;
        }
    }
    
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    if (!$post_id) {
        return $content; 
    }

    // Map tags to actual values
    $replacements = [
        '{kiosk_slide_type}'           => get_post_meta($post_id, '_slide_type', true) ?: 'image',
        '{kiosk_video_url}'            => get_post_meta($post_id, '_video_url', true),
        '{kiosk_html_content}'         => get_post_meta($post_id, '_html_content', true),
        '{kiosk_html_position}'        => 'kiosk-pos-' . (get_post_meta($post_id, '_html_position', true) ?: 'center-center'),
        '{kiosk_html_theme}'           => 'kiosk-theme-' . (get_post_meta($post_id, '_html_theme', true) ?: 'light'),
        '{kiosk_html_bg_color}'        => get_post_meta($post_id, '_html_bg_color', true) ?: '',
        '{kiosk_html_bg_image}'        => get_post_meta($post_id, '_html_bg_image', true),
        '{kiosk_html_overlay_opacity}' => get_post_meta($post_id, '_html_overlay_opacity', true) ?: '50',
        '{kiosk_slide_duration}'       => (int)(get_post_meta($post_id, '_slide_duration', true) ?: 5) * 1000,
        '{kiosk_menu_order}'           => get_post_field('menu_order', $post_id) ?: '0',
    ];

    // Replace the tags in the content
    foreach ($replacements as $tag => $value) {
        if (strpos($content, $tag) !== false) {
            $content = str_replace($tag, $value, $content);
        }
    }

    return $content;
}

add_filter('bricks/dynamic_data/render_content', function($content, $post, $context = 'text') {
    return patlis_kiosk_render_bricks_dynamic_tags_content($content);
}, 20, 3);

add_filter('bricks/frontend/render_data', function($content, $post) {
    return patlis_kiosk_render_bricks_dynamic_tags_content($content);
}, 20, 2);

// 3. Render tags for Bricks Dynamic Data fields (Image, Video etc)
add_filter('bricks/dynamic_data/render_tag', function($tag, $post, $context = 'text') {
    if (!is_string($tag) || strpos($tag, 'kiosk_') === false) {
        return $tag;
    }

    $clean = str_replace(['{', '}'], '', $tag);
    
    // Determine post ID
    $post_id = 0;
    if (class_exists('\Bricks\Query')) {
        $loop_item = \Bricks\Query::get_loop_object();
        if ($loop_item && is_a($loop_item, 'WP_Post')) {
            $post_id = $loop_item->ID;
        }
    }
    if (!$post_id) $post_id = get_the_ID();
    if (!$post_id) return $tag;

    // Image context
    if ($clean === 'kiosk_html_bg_image') {
        $url = get_post_meta($post_id, '_html_bg_image', true);
        if ($context === 'image') return $url ? [$url] : [];
        return $url ?: '';
    }

    // Video context
    if ($clean === 'kiosk_video_url') {
        return get_post_meta($post_id, '_video_url', true) ?: '';
    }
    
    // Fallback for everything else in element fields (like classes)
    $replacements = [
        'kiosk_slide_type'           => get_post_meta($post_id, '_slide_type', true) ?: 'image',
        'kiosk_html_position'        => 'kiosk-pos-' . (get_post_meta($post_id, '_html_position', true) ?: 'center-center'),
        'kiosk_html_theme'           => 'kiosk-theme-' . (get_post_meta($post_id, '_html_theme', true) ?: 'light'),
        'kiosk_menu_order'           => get_post_field('menu_order', $post_id) ?: '0',
        'kiosk_slide_duration'       => (int)(get_post_meta($post_id, '_slide_duration', true) ?: 5) * 1000,
    ];
    
    if (array_key_exists($clean, $replacements)) {
        return $replacements[$clean];
    }

    return $tag;
}, 20, 3);