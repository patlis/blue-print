<?php
if (!defined('ABSPATH')) exit;

function patlis_core_get_ai_status_options(): array
{
    return [
        'none'      => __('None', 'patlis-core'),
        'assisted'   => __('AI-assisted', 'patlis-core'),
        'generated' => __('AI-Generated', 'patlis-core'),
        'modified'    => __('AI-Modified', 'patlis-core'),
    ];
}

function patlis_core_get_attachment_ai_status(int $attachment_id): string
{
    $status = sanitize_key((string) get_post_meta($attachment_id, 'ai_status', true));

    return array_key_exists($status, patlis_core_get_ai_status_options()) ? $status : 'none';
}

add_filter('wp_get_attachment_image_attributes', function (array $attributes, WP_Post $attachment): array {
    $status = patlis_core_get_attachment_ai_status($attachment->ID);
    if ($status !== 'none') {
        $attributes['data-patlis-ai-status'] = $status;
    }

    return $attributes;
}, 10, 2);

add_filter('render_block', function (string $block_content, array $block): string {
    $block_name = $block['blockName'] ?? '';
    if (!in_array($block_name, ['core/image', 'core/gallery', 'core/cover'], true) || stripos($block_content, '<img') === false) {
        return $block_content;
    }

    $block_content = preg_replace_callback('/<img\b[^>]*>/i', static function (array $matches): string {
        $image_tag = $matches[0];
        if (stripos($image_tag, 'data-patlis-ai-status=') !== false) {
            return $image_tag;
        }

        if (!preg_match('/\bwp-image-([0-9]+)\b/', $image_tag, $id_matches)) {
            return $image_tag;
        }

        $status = patlis_core_get_attachment_ai_status((int) $id_matches[1]);
        if ($status === 'none') {
            return $image_tag;
        }

        return preg_replace('/<img\b/i', '<img data-patlis-ai-status="' . esc_attr($status) . '"', $image_tag, 1);
    }, $block_content);

    return is_string($block_content) ? $block_content : '';
}, 10, 2);

add_action('wp_enqueue_scripts', function (): void {
    $script_path = PATLIS_CORE_PATH . 'assets/js/ai-image-labels.min.js';
    $script_version = is_file($script_path) ? (string) filemtime($script_path) : PATLIS_CORE_VERSION;
    $label_settings = get_option(Patlis_Core::OPTION_AI_LABELS, []);
    $label_settings = is_array($label_settings) ? $label_settings : [];

    wp_enqueue_script(
        'ai-labels',
        PATLIS_CORE_URL . 'assets/js/ai-image-labels.min.js',
        [],
        $script_version,
        true
    );

    wp_localize_script('ai-labels', 'patlisAiImageLabels', [
        'enabled'        => ($label_settings['enabled'] ?? '') === 'no' ? 'no' : 'yes',
        'minWidth'       => max(100, min((int) ($label_settings['min_width'] ?? 150), 300)),
        'assistedLabel'  => sanitize_text_field((string) ($label_settings['assisted_label'] ?? 'AI-assisted')),
        'generatedLabel' => sanitize_text_field((string) ($label_settings['generated_label'] ?? 'AI-Generated')),
        'modifiedLabel'  => sanitize_text_field((string) ($label_settings['modified_label'] ?? 'AI-Modified')),
    ]);

});

add_filter('attachment_fields_to_edit', function (array $fields, WP_Post $post): array {
    $status  = patlis_core_get_attachment_ai_status($post->ID);
    $options = patlis_core_get_ai_status_options();
    $html    = '<select name="attachments[' . (int) $post->ID . '][ai_status]">';

    foreach ($options as $value => $label) {
        $html .= '<option value="' . esc_attr($value) . '"' . selected($status, $value, false) . '>' . esc_html($label) . '</option>';
    }

    $html .= '</select>';
    $fields['patlis_ai_status'] = [
        'label' => __('AI Status', 'patlis-core'),
        'input' => 'html',
        'html'  => $html,
    ];

    return $fields;
}, 10, 2);

add_filter('attachment_fields_to_save', function (array $post, array $attachment): array {
    if (!isset($attachment['ai_status'])) {
        return $post;
    }

    $status = sanitize_key((string) $attachment['ai_status']);
    if (!array_key_exists($status, patlis_core_get_ai_status_options())) {
        $status = 'none';
    }

    update_post_meta((int) $post['ID'], 'ai_status', $status);

    return $post;
}, 10, 2);

add_filter('manage_upload_columns', function (array $columns): array {
    $columns['patlis_ai_status'] = __('AI Status', 'patlis-core');

    return $columns;
});

add_action('manage_media_custom_column', function (string $column, int $attachment_id): void {
    if ($column !== 'patlis_ai_status') {
        return;
    }

    $options = patlis_core_get_ai_status_options();
    $status  = patlis_core_get_attachment_ai_status($attachment_id);

    echo esc_html($options[$status]);
}, 10, 2);

add_action('restrict_manage_posts', function (string $post_type): void {
    if ($post_type !== 'attachment') {
        return;
    }

    $selected = isset($_GET['patlis_ai_status']) ? sanitize_key((string) wp_unslash($_GET['patlis_ai_status'])) : '';
    $options  = patlis_core_get_ai_status_options();
    ?>
    <select name="patlis_ai_status">
        <option value=""><?php esc_html_e('All AI statuses', 'patlis-core'); ?></option>
        <?php foreach ($options as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>"<?php selected($selected, $value); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
});

add_action('pre_get_posts', function (WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'attachment') {
        return;
    }

    $status = isset($_GET['patlis_ai_status']) ? sanitize_key((string) wp_unslash($_GET['patlis_ai_status'])) : '';
    if (!array_key_exists($status, patlis_core_get_ai_status_options())) {
        return;
    }

    if ($status === 'none') {
        $query->set('meta_query', [
            'relation' => 'OR',
            [
                'key'     => 'ai_status',
                'value'   => 'none',
                'compare' => '=',
            ],
            [
                'key'     => 'ai_status',
                'compare' => 'NOT EXISTS',
            ],
        ]);
        return;
    }

    $query->set('meta_key', 'ai_status');
    $query->set('meta_value', $status);
});
