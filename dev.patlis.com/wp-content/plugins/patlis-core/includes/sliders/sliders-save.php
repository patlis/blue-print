<?php

if (!defined('ABSPATH')) exit;


/**
 * SAVE SLIDERS
 */
add_action('admin_post_patlis_save_sliders', function () {

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'patlis-core'));
    }

    check_admin_referer('patlis_save_sliders', 'patlis_sliders_nonce');

    global $slider_groups;

    $registry = patlis_sliders_get_registry();

    $posted = isset($_POST['sliders']) && is_array($_POST['sliders'])
        ? wp_unslash($_POST['sliders'])
        : [];

    // Preserve groups that are not currently visible/enabled on this site.
    $saved = get_option(patlis_sliders_option_name(), []);

    if (!is_array($saved)) {
        $saved = [];
    }

    foreach ($posted as $group_key => $group_data) {

        $group_key = sanitize_key($group_key);

        // Only known slider groups may be saved.
        if (!isset($slider_groups[$group_key])) {
            continue;
        }

        if (!is_array($group_data)) {
            continue;
        }

        $existing_group = isset($saved[$group_key]) && is_array($saved[$group_key])
            ? $saved[$group_key]
            : [];
        $existing_slides = isset($existing_group['slides']) && is_array($existing_group['slides'])
            ? $existing_group['slides']
            : [];

        $settings = isset($group_data['settings']) && is_array($group_data['settings'])
            ? $group_data['settings']
            : [];

        $duration = isset($settings['duration'])
            ? absint($settings['duration'])
            : 5000;

        if ($duration < 1000) {
            $duration = 5000;
        }

        $clean_group = [
            'settings' => [
                'autoplay' => !empty($settings['autoplay']) ? 1 : 0,
                'duration' => $duration,
            ],
            'slides' => [],
        ];

        $slides = isset($group_data['slides']) && is_array($group_data['slides'])
            ? $group_data['slides']
            : [];

        foreach ($slides as $slide_index => $slide) {

            if (!is_array($slide)) {
                continue;
            }

            $slide_id = isset($slide['id'])
                ? sanitize_text_field((string) $slide['id'])
                : '';
            $existing_slide = [];

            if ($slide_id !== '') {
                foreach ($existing_slides as $candidate) {
                    if (is_array($candidate) && isset($candidate['id']) && hash_equals((string) $candidate['id'], $slide_id)) {
                        $existing_slide = $candidate;
                        break;
                    }
                }
            }

            if ($existing_slide === [] && isset($existing_slides[$slide_index]) && is_array($existing_slides[$slide_index])) {
                $existing_slide = $existing_slides[$slide_index];
            }

            if ($slide_id === '') {
                $slide_id = wp_generate_uuid4();
            }

            $layout = isset($slide['layout']) && is_string($slide['layout'])
                ? sanitize_key($slide['layout'])
                : $registry['default_layout'];

            if (!isset($registry['layouts'][$layout])) {
                $layout = $registry['default_layout'];
            }

            $posted_options = isset($slide['options']) && is_array($slide['options']) ? $slide['options'] : [];
            $existing_options = isset($existing_slide['options']) && is_array($existing_slide['options'])
                ? $existing_slide['options']
                : [];
            $clean_options = [];

            foreach ($registry['layouts'] as $layout_key => $layout_data) {
                $clean_options[$layout_key] = patlis_sliders_sanitize_layout_options(
                    $layout_key,
                    $layout_key === $layout ? ($posted_options[$layout_key] ?? []) : [],
                    $existing_options[$layout_key] ?? []
                );
            }

            $slide_duration = isset($slide['duration'])
                ? absint($slide['duration'])
                : 0;

            if ($slide_duration > 0 && $slide_duration < 1000) {
                $slide_duration = 1000;
            }

            $clean_group['slides'][] = [
                'id'          => $slide_id,
                'enabled'     => !empty($slide['enabled']) ? 1 : 0,
                'sort'        => count($clean_group['slides']) + 1,
                'name'        => isset($slide['name']) ? sanitize_text_field($slide['name']) : '',
                'small_title' => patlis_sliders_sanitize_multilingual_value(
                    $slide['small_title'] ?? [],
                    $existing_slide['small_title'] ?? '',
                    'text'
                ),
                'header'      => patlis_sliders_sanitize_multilingual_value(
                    $slide['header'] ?? [],
                    $existing_slide['header'] ?? '',
                    'text'
                ),
                'content'     => patlis_sliders_sanitize_multilingual_value(
                    $slide['content'] ?? [],
                    $existing_slide['content'] ?? '',
                    'html'
                ),
                'image_id'    => isset($slide['image_id']) ? absint($slide['image_id']) : 0,
                'layout'      => $layout,
                'options'     => $clean_options,
                'cta1_text'   => patlis_sliders_sanitize_multilingual_value(
                    $slide['cta1_text'] ?? [],
                    $existing_slide['cta1_text'] ?? '',
                    'text'
                ),
                'cta1_url'    => patlis_sliders_sanitize_multilingual_value(
                    $slide['cta1_url'] ?? [],
                    $existing_slide['cta1_url'] ?? '',
                    'url'
                ),
                'cta1_target' => isset($slide['cta1_target']) && $slide['cta1_target'] === '_blank' ? '_blank' : '_self',
                'cta2_text'   => patlis_sliders_sanitize_multilingual_value(
                    $slide['cta2_text'] ?? [],
                    $existing_slide['cta2_text'] ?? '',
                    'text'
                ),
                'cta2_url'    => patlis_sliders_sanitize_multilingual_value(
                    $slide['cta2_url'] ?? [],
                    $existing_slide['cta2_url'] ?? '',
                    'url'
                ),
                'cta2_target' => isset($slide['cta2_target']) && $slide['cta2_target'] === '_blank' ? '_blank' : '_self',
                'duration'    => $slide_duration,
            ];
        }

        $saved[$group_key] = $clean_group;
    }

    update_option(
        patlis_sliders_option_name(),
        $saved,
        false
    );

    $selected_language = isset($_POST['patlis_sliders_language']) && is_string($_POST['patlis_sliders_language'])
        ? sanitize_key(wp_unslash($_POST['patlis_sliders_language']))
        : '';

    if (!isset(patlis_sliders_get_languages()[$selected_language])) {
        $selected_language = 'all';
    }

    wp_safe_redirect(
        add_query_arg(
            [
                'page'    => 'patlis-slides',
                'updated' => '1',
                'lang'    => $selected_language,
            ],
            admin_url('admin.php')
        )
    );

    exit;
});
