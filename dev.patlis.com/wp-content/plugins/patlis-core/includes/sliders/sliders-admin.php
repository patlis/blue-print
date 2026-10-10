<?php

if (!defined('ABSPATH')) exit;

/** ADMIN MENU */
add_action('admin_menu', function () {

    add_menu_page(
        __('Slides', 'patlis-core'),
        __('Slides', 'patlis-core'),
        'manage_options',
        'patlis-slides',
        'patlis_render_slides_admin',
        'dashicons-images-alt2',
        30
    );
});

/**  ADMIN ASSETS */
add_action('admin_enqueue_scripts', function ($hook) {

    if ($hook !== 'toplevel_page_patlis-slides') {
        return;
    }

    wp_enqueue_media();     // WordPress Media Library
    wp_enqueue_script('jquery-ui-sortable');     // WordPress / jQuery UI sortable
});

/** AVAILABLE SLIDER GROUPS */
$slider_groups = [

    'home' => [
        'enabled' => true,
        'label'   => __('Home', 'patlis-core'),
    ],

    'rooms' => [
        'enabled' => true,
        'label'   => __('Rooms', 'patlis-core'),
    ],

    'services' => [
        'enabled' => true,
        'label'   => __('Services', 'patlis-core'),
    ],

    'events' => [
        'enabled' => true,
        'label'   => __('Events', 'patlis-core'),
    ],

    'menu' => [
        'enabled' => false,
        'label'   => __('Menu', 'patlis-core'),
    ],

    'reservation' => [
        'enabled' => false,
        'label'   => __('Reservation', 'patlis-core'),
    ],

    'offers-packages' => [
        'enabled' => false,
        'label'   => __('Offers & Packages', 'patlis-core'),
    ],
];


/**  SLIDER LAYOUTS */
$slider_layouts = patlis_sliders_get_layouts();

/** RENDER ONE SLIDE */
function patlis_sliders_get_admin_language(): string {
    $language = isset($_GET['lang']) && is_string($_GET['lang'])
        ? sanitize_key(wp_unslash($_GET['lang']))
        : '';

    return isset(patlis_sliders_get_languages()[$language]) ? $language : '';
}

function patlis_render_slide_admin($group, $index, $slide = [], $is_template = false) {

    global $slider_layouts;

    $registry = patlis_sliders_get_registry();
    $languages = patlis_sliders_get_languages();
    $selected_language = patlis_sliders_get_admin_language();

    if ($selected_language !== '') {
        $languages = [$selected_language => $languages[$selected_language]];
    }

    $defaults = [
        'id'          => '',
        'enabled'     => 1,
        'sort'        => $is_template ? '' : ($index + 1),
        'name'        => '',
        'small_title' => '',
        'header'      => '',
        'content'     => '',
        'image_id'    => 0,
        'layout'      => $registry['default_layout'],
        'options'     => [],
        'cta1_text'   => '',
        'cta1_url'    => '',
        'cta1_target' => '_self',
        'cta2_text'   => '',
        'cta2_url'    => '',
        'cta2_target' => '_self',
        'duration'    => 0,
    ];

    $slide = wp_parse_args($slide, $defaults);

    if (!is_string($slide['layout']) || !isset($registry['layouts'][$slide['layout']])) {
        $slide['layout'] = $registry['default_layout'];
    }

    $slide_options = is_array($slide['options']) ? $slide['options'] : [];

    if (!$is_template && $slide['id'] === '') {
        $slide['id'] = wp_generate_uuid4();
    }

    foreach (['small_title', 'header', 'content', 'cta1_text', 'cta1_url', 'cta2_text', 'cta2_url'] as $field) {
        $slide[$field] = patlis_sliders_normalize_multilingual_value($slide[$field]);
    }

    $group_attr = $is_template ? '__GROUP__' : sanitize_key($group);
    $index_attr = $is_template ? '__INDEX__' : absint($index);

    $field_name = function ($field, $language = '') use ($group_attr, $index_attr) {
        $name = 'sliders[' . $group_attr . '][slides][' . $index_attr . '][' . $field . ']';

        return $language !== '' ? $name . '[' . $language . ']' : $name;
    };

    $image_url = '';

    if (!empty($slide['image_id'])) {
        $image_url = wp_get_attachment_image_url(absint($slide['image_id']), 'medium');
    }

    $title = trim((string) $slide['name']);

    if ($title === '') {
        $title = $is_template
            ? __('New slide', 'patlis-core')
            : sprintf(__('Slide %d', 'patlis-core'), $index + 1);
    }

    ?>

    <div class="patlis-slide<?php echo $is_template ? ' is-open' : ''; ?>" data-slide-index="<?php echo esc_attr($index_attr); ?>">
        <!-- SLIDE HEADER -->
        <div class="patlis-slide-header">

            <!-- DRAG -->
            <span class="patlis-slide-drag dashicons dashicons-move"
                title="<?php esc_attr_e('Drag to reorder', 'patlis-core'); ?>"
            ></span>

            <!-- TITLE / ACCORDION -->
            <button type="button" class="patlis-slide-toggle">
                <span class="patlis-slide-title"><?php echo esc_html($title); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>

            <!-- ENABLE -->
            <label class="patlis-slide-enabled">
                <input type="checkbox" data-field="enabled" name="<?php echo esc_attr($field_name('enabled')); ?>"
                    value="1"
                    <?php checked(!empty($slide['enabled'])); ?>
                >
                <?php esc_html_e('Enabled', 'patlis-core'); ?>
            </label>

        </div>

        <!-- SLIDE CONTENT -->
        <div class="patlis-slide-content">

        <!-- ID -->
        <input type="hidden" data-field="id" name="<?php echo esc_attr($field_name('id')); ?>" value="<?php echo esc_attr($slide['id']); ?>">

        <!-- SORT -->
        <input type="hidden" data-field="sort" name="<?php echo esc_attr($field_name('sort')); ?>" value="<?php echo esc_attr($slide['sort']); ?>">
        
        <div style="display: flex; flex-direction: row; gap: 20px; flex-wrap: wrap;">
            <!-- INTERNAL NAME -->
            <div class="patlis-field" style="flex: 1; min-width: 400px;">
                <h3><?php esc_html_e('Internal name', 'patlis-core'); ?></h3>
                <input
                    type="text"
                    data-field="name"
                    name="<?php echo esc_attr($field_name('name')); ?>"
                    value="<?php echo esc_attr($slide['name']); ?>"
                    class="regular-text patlis-slide-name"
                >
            </div>

            <!-- IMAGE -->
            <div class="patlis-field" style="flex: 1; min-width: 400px;">
                <h3><?php esc_html_e('Image', 'patlis-core'); ?></h3>

                <input type="hidden" data-field="image_id" name="<?php echo esc_attr($field_name('image_id')); ?>"
                    class="patlis-image-id" value="<?php echo esc_attr(absint($slide['image_id'])); ?>"
                >

                <div class="patlis-image-preview">
                    <?php if ($image_url) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="">
                    <?php endif; ?>
                </div>

                <div class="patlis-image-actions">
                    <button type="button" class="button patlis-select-image"><?php esc_html_e('Select image', 'patlis-core'); ?></button>

                    <button type="button" class="button-link-delete patlis-remove-image" <?php echo $image_url ? '' : 'hidden'; ?>>
                        <?php esc_html_e('Remove image', 'patlis-core'); ?>
                    </button>

                </div>
            </div>

            <!-- DURATION -->
            <div class="patlis-field" style="flex: 1; min-width: 400px;">
                <label><strong><?php esc_html_e('Duration override', 'patlis-core'); ?></strong></label>
                <input type="number" data-field="duration"
                    name="<?php echo esc_attr($field_name('duration')); ?>"
                    value="<?php echo !empty($slide['duration']) ? esc_attr(absint($slide['duration'])) : ''; ?>"
                    min="1000" step="500">
                <span>ms</span>

                <p class="description">
                    <?php esc_html_e('Leave empty to use the slider default duration.', 'patlis-core'); ?>
                </p>
            </div>

            <!-- LAYOUT -->
            <div class="patlis-field" style="flex: 1; min-width: 400px;">
                <h3><strong><?php esc_html_e('Layout', 'patlis-core'); ?></strong></h3>

                <select data-field="layout" name="<?php echo esc_attr($field_name('layout')); ?>">
                    <?php foreach ($slider_layouts as $layout_key => $layout_label) : ?>
                        <option value="<?php echo esc_attr($layout_key); ?>" <?php selected($slide['layout'], $layout_key); ?>>
                            <?php echo esc_html($layout_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

            <?php foreach ($registry['layouts'] as $layout_key => $layout) : ?>
                <?php
                $is_active_layout = $slide['layout'] === $layout_key;
                $layout_options = isset($slide_options[$layout_key]) && is_array($slide_options[$layout_key])
                    ? $slide_options[$layout_key]
                    : [];
                $layout_options = patlis_sliders_sanitize_layout_options($layout_key, $layout_options);
                ?>
                <div class="patlis-layout-options" data-layout="<?php echo esc_attr($layout_key); ?>" <?php echo $is_active_layout ? '' : 'hidden'; ?>>
                    <div class="patlis-row">
                        <?php foreach ($layout['fields'] as $field) : ?>
                            <?php
                            if (!in_array($field['type'], ['dropdown', 'range'], true)) {
                                continue;
                            }

                            $option_key = $field['option_key'];
                            $value = $layout_options[$option_key] ?? $field['default'];
                            ?>
                            <div class="patlis-field">
                                <label>
                                    <span><?php echo esc_html($field['label']); ?></span>
                                    <?php if ($field['type'] === 'range') : ?>
                                        <input type="range" data-field="options" data-layout="<?php echo esc_attr($layout_key); ?>"
                                            data-option="<?php echo esc_attr($option_key); ?>"
                                            name="<?php echo esc_attr($field_name('options') . '[' . $layout_key . '][' . $option_key . ']'); ?>"
                                            value="<?php echo esc_attr($value); ?>"
                                            min="<?php echo esc_attr($field['min']); ?>"
                                            max="<?php echo esc_attr($field['max']); ?>"
                                            step="<?php echo esc_attr($field['step']); ?>"
                                            <?php disabled(!$is_active_layout); ?>>
                                        <output class="patlis-range-value"><?php echo esc_html($value); ?></output>
                                    <?php else : ?>
                                    <select data-field="options" data-layout="<?php echo esc_attr($layout_key); ?>"
                                        data-option="<?php echo esc_attr($option_key); ?>"
                                        name="<?php echo esc_attr($field_name('options') . '[' . $layout_key . '][' . $option_key . ']'); ?>"
                                        <?php disabled(!$is_active_layout); ?>>
                                        <?php foreach ($field['options'] as $option_value => $option_label) : ?>
                                            <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                                                <?php echo esc_html($option_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- SMALL TITLE -->
            <h3><?php esc_html_e('Small title', 'patlis-core'); ?></h3>
            <div class="patlis-field patlis-row">
                <?php foreach ($languages as $language => $language_label) : ?>
                    <div class="patlis-language-field" data-language="<?php echo esc_attr($language); ?>">
                        <span class="patlis-language-label"><?php echo esc_html($language_label); ?></span>
                        <input type="text" data-field="small_title" style="min-width: 300px;"
                            data-language="<?php echo esc_attr($language); ?>"
                            name="<?php echo esc_attr($field_name('small_title', $language)); ?>"
                            value="<?php echo esc_attr($slide['small_title'][$language] ?? ''); ?>"
                            class="regular-text"
                        >
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- HEADER -->
            <h3><?php esc_html_e('Header', 'patlis-core'); ?></h3>
            <div class="patlis-field patlis-row">

                <?php foreach ($languages as $language => $language_label) : ?>
                    <div class="patlis-language-field" data-language="<?php echo esc_attr($language); ?>">
                        <span class="patlis-language-label"><?php echo esc_html($language_label); ?></span>
                        <input type="text" data-field="header" style="min-width: 300px;"
                            data-language="<?php echo esc_attr($language); ?>"
                            name="<?php echo esc_attr($field_name('header', $language)); ?>"
                            value="<?php echo esc_attr($slide['header'][$language] ?? ''); ?>"
                            class="regular-text"
                        >
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- TEXT -->
            <div><span style="font-size: 1.3em; font-weight: bold;"><?php esc_html_e('Text', 'patlis-core'); ?></span> <small><?php esc_html_e(' (Keep it concise to fit in mobile view.)', 'patlis-core'); ?></small></div>
            <div class="patlis-field patlis-row">
                <?php foreach ($languages as $language => $language_label) : ?>
                    <div class="patlis-language-field" data-language="<?php echo esc_attr($language); ?>" style="min-width: 300px;">
                        <span class="patlis-language-label"><?php echo esc_html($language_label); ?></span>
                        <textarea data-field="content" data-language="<?php echo esc_attr($language); ?>"
                            name="<?php echo esc_attr($field_name('content', $language)); ?>" rows="3" class="large-text"
                        ><?php echo esc_textarea($slide['content'][$language] ?? ''); ?></textarea>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CTA 1 -->
            <fieldset class="patlis-cta-group">
                <legend><h3><?php esc_html_e('CTA 1', 'patlis-core'); ?></h3></legend>

                <h3><?php esc_html_e('Text', 'patlis-core'); ?></h3>
                <div class="patlis-field patlis-row">

                    <?php foreach ($languages as $language => $language_label) : ?>
                        <div class="patlis-language-field" data-language="<?php echo esc_attr($language); ?>"  style="min-width: 300px;">
                            <span class="patlis-language-label"><?php echo esc_html($language_label); ?></span>
                            <input
                                type="text"
                                data-field="cta1_text"
                                data-language="<?php echo esc_attr($language); ?>"
                                name="<?php echo esc_attr($field_name('cta1_text', $language)); ?>"
                                value="<?php echo esc_attr($slide['cta1_text'][$language] ?? ''); ?>"
                                class="regular-text"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3><?php esc_html_e('URL', 'patlis-core'); ?></h3>
                <div class="patlis-field patlis-row">
                    <?php foreach ($languages as $language => $language_label) : ?>
                        <div class="patlis-language-field" data-language="<?php echo esc_attr($language); ?>" style="min-width: 300px;">
                            <span class="patlis-language-label"><?php echo esc_html($language_label); ?></span>
                            <input  type="text"
                                data-field="cta1_url"
                                data-language="<?php echo esc_attr($language); ?>"
                                name="<?php echo esc_attr($field_name('cta1_url', $language)); ?>"
                                value="<?php echo esc_attr($slide['cta1_url'][$language] ?? ''); ?>"
                                class="regular-text"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3><?php esc_html_e('Target', 'patlis-core'); ?></h3>
                <div class="patlis-field">
                    <select data-field="cta1_target" name="<?php echo esc_attr($field_name('cta1_target')); ?>">
                        <option value="_self" <?php selected($slide['cta1_target'], '_self'); ?>>
                            <?php esc_html_e('Same window', 'patlis-core'); ?>
                        </option>
                        <option value="_blank" <?php selected($slide['cta1_target'], '_blank'); ?>>
                            <?php esc_html_e('New window', 'patlis-core'); ?>
                        </option>
                    </select>
                </div>
            </fieldset>

            <!-- CTA 2 -->
            <fieldset class="patlis-cta-group">
                <legend><strong><?php esc_html_e('CTA 2', 'patlis-core'); ?></strong></legend>

                <h3><?php esc_html_e('Text', 'patlis-core'); ?></h3>
                <div class="patlis-field patlis-row">
                    <?php foreach ($languages as $language => $language_label) : ?>
                        <div class="patlis-language-field" data-language="<?php echo esc_attr($language); ?>"  style="min-width: 300px;">
                            <span class="patlis-language-label"><?php echo esc_html($language_label); ?></span>
                            <input type="text" data-field="cta2_text" data-language="<?php echo esc_attr($language); ?>"
                                name="<?php echo esc_attr($field_name('cta2_text', $language)); ?>"
                                value="<?php echo esc_attr($slide['cta2_text'][$language] ?? ''); ?>"
                                class="regular-text"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3><?php esc_html_e('URL', 'patlis-core'); ?></h3>
                <div class="patlis-field patlis-row">
                    <?php foreach ($languages as $language => $language_label) : ?>
                        <div class="patlis-language-field" data-language="<?php echo esc_attr($language); ?>"  style="min-width: 300px;">
                            <span class="patlis-language-label"><?php echo esc_html($language_label); ?></span>
                            <input type="text" data-field="cta2_url"
                                data-language="<?php echo esc_attr($language); ?>"
                                name="<?php echo esc_attr($field_name('cta2_url', $language)); ?>"
                                value="<?php echo esc_attr($slide['cta2_url'][$language] ?? ''); ?>"
                                class="regular-text"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="patlis-field">
                    <label><?php esc_html_e('Target', 'patlis-core'); ?></label>

                    <select
                        data-field="cta2_target"
                        name="<?php echo esc_attr($field_name('cta2_target')); ?>"
                    >
                        <option value="_self" <?php selected($slide['cta2_target'], '_self'); ?>>
                            <?php esc_html_e('Same window', 'patlis-core'); ?>
                        </option>
                        <option value="_blank" <?php selected($slide['cta2_target'], '_blank'); ?>>
                            <?php esc_html_e('New window', 'patlis-core'); ?>
                        </option>
                    </select>
                </div>
            </fieldset>

            <!-- REMOVE -->
            <div class="patlis-slide-actions">
                <button type="button" class="button-link-delete patlis-remove-slide"><?php esc_html_e('Remove slide', 'patlis-core'); ?></button>
            </div>

        </div>

    </div>

    <?php
}

/** ADMIN PAGE */
function patlis_render_slides_admin() {

    global $slider_groups;

    $registry = patlis_sliders_get_registry();
    $selected_language = patlis_sliders_get_admin_language();

    $saved_sliders = get_option(patlis_sliders_option_name(), []);

    if (!is_array($saved_sliders)) {
        $saved_sliders = [];
    }

    ?>

    <div class="wrap patlis-slides-admin">

        <h1><?php esc_html_e('Slides', 'patlis-core'); ?></h1>

        <?php if (!empty($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Slides saved.', 'patlis-core'); ?></p>
            </div>
        <?php endif; ?>

        <h2><?php esc_html_e('Available Layouts', 'patlis-core'); ?></h2>
        <div class="patlis-layout-previews">
            <?php foreach ($registry['layouts'] as $layout) : ?>
                <div class="patlis-layout-preview">
                    <h2><?php echo esc_html($layout['label']); ?></h2>
                    <img src="<?php echo esc_url(PATLIS_CORE_URL . $layout['preview']); ?>"
                        alt="<?php echo esc_attr($layout['label']); ?>" loading="lazy">
                    <p><?php echo esc_html($layout['description']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

            <input type="hidden" name="action" value="patlis_save_sliders">
            <input type="hidden" name="patlis_sliders_language" value="<?php echo esc_attr($selected_language); ?>">

            <?php wp_nonce_field('patlis_save_sliders', 'patlis_sliders_nonce'); ?>

            <div class="patlis-slider-groups">

                <?php foreach ($slider_groups as $slider_group => $slider_group_data) : ?>

                    <?php

                    if (!$slider_group_data['enabled']) {
                        continue;
                    }

                    if (
                        function_exists('allowed_section') &&
                        allowed_section($slider_group) !== 1
                    ) {
                        continue;
                    }

                    $group_saved = isset($saved_sliders[$slider_group]) && is_array($saved_sliders[$slider_group])
                        ? $saved_sliders[$slider_group]
                        : [];

                    $group_settings = isset($group_saved['settings']) && is_array($group_saved['settings'])
                        ? $group_saved['settings']
                        : [];

                    $group_slides = isset($group_saved['slides']) && is_array($group_saved['slides'])
                        ? $group_saved['slides']
                        : [];

                    $autoplay = array_key_exists('autoplay', $group_settings)
                        ? !empty($group_settings['autoplay'])
                        : true;

                    $default_duration = !empty($group_settings['duration'])
                        ? max(1000, absint($group_settings['duration']))
                        : 5000;

                    ?>

                    <div class="patlis-slider-group" data-group="<?php echo esc_attr($slider_group); ?>">
                        <!-- GROUP HEADER -->
                        <button type="button" class="patlis-group-header">
                            <span><?php echo esc_html($slider_group_data['label']); ?></span>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>

                        <!-- GROUP CONTENT -->
                        <div class="patlis-group-content">

                            <!-- GROUP SETTINGS -->
                            <div class="patlis-group-settings">

                                <label>
                                    <strong><?php esc_html_e('Autoplay', 'patlis-core'); ?></strong>
                                    <input type="checkbox" name="sliders[<?php echo esc_attr($slider_group); ?>][settings][autoplay]"
                                        value="1" <?php checked($autoplay); ?>
                                    >
                                </label>

                                <label>
                                    <strong><?php esc_html_e('Default duration', 'patlis-core'); ?></strong>
                                    <input type="number" name="sliders[<?php echo esc_attr($slider_group); ?>][settings][duration]"
                                        value="<?php echo esc_attr($default_duration); ?>"
                                        min="1000" step="500">
                                    <span>ms</span>
                                </label>

                            </div>

                            <!-- SLIDES -->
                            <div class="patlis-slides-list">
                                <?php foreach ($group_slides as $slide_index => $slide) : ?>
                                    <?php patlis_render_slide_admin($slider_group, $slide_index, $slide); ?>
                                <?php endforeach; ?>
                            </div>

                            <!-- ADD SLIDE -->
                            <button type="button" class="button button-secondary patlis-add-slide">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php esc_html_e('Add slide', 'patlis-core'); ?>
                            </button>

                        </div>
                    </div>

                <?php endforeach; ?>
            </div>

            <p class="patlis-save-slides">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save slides', 'patlis-core'); ?></button>
            </p>

        </form>

    </div>

    <!-- SLIDE TEMPLATE -->
    <template id="patlis-slide-template"><?php patlis_render_slide_admin('__GROUP__', 0, [], true); ?></template>

    <style>
        .patlis-slider-groups {
            max-width: 1100px;
            margin-top: 25px;
        }

        .patlis-layout-previews {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 400px), 1fr));
            gap: 24px;
            max-width: 1100px;
            margin-top: 25px;
        }

        .patlis-layout-preview {min-width: 0;}

        .patlis-layout-preview h2 {
            margin: 0 0 10px;
            font-size: 16px;
            overflow-wrap: anywhere;
        }

        .patlis-layout-preview img {
            display: block;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #dcdcde;

        }

        .patlis-layout-preview p {
            margin: 10px 0 0;
            overflow-wrap: anywhere;
        }

        .patlis-language-field {}

        .patlis-language-label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
        }


        /* GROUP */
        .patlis-slider-group {
            background: #fff;
            border: 1px solid #dcdcde;
            margin-bottom: 12px;
        }

        .patlis-group-header {
            width: 100%;
            min-height: 58px;
            padding: 0 20px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            border: 0;
            background: #fff;

            font-size: 16px;
            font-weight: 600;

            cursor: pointer;
            text-align: left;
        }

        .patlis-group-header:hover {background: #f6f7f7;}

        .patlis-group-header .dashicons {transition: transform .2s ease;}

        .patlis-slider-group.is-open > .patlis-group-header .dashicons {transform: rotate(180deg);}

        .patlis-group-content {
            display: none;
            padding: 20px;
            border-top: 1px solid #dcdcde;
        }

        .patlis-slider-group.is-open > .patlis-group-content {display: block;}

        /* GROUP SETTINGS */
        .patlis-group-settings {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 30px;

            padding: 15px;
            margin-bottom: 20px;

            background: #f6f7f7;
        }

        .patlis-group-settings label {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* SLIDE */
        .patlis-slide {
            margin-bottom: 10px;
            border: 1px solid #dcdcde;
            background: #fff;
        }

        .patlis-slide-header {
            min-height: 50px;
            display: flex;
            align-items: center;
            background: #f6f7f7;
        }


        /* DRAG HANDLE */

        .patlis-slide-drag {
            width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: grab;
            color: #646970;
        }

        .patlis-slide-drag:active { cursor: grabbing;}

        /* SLIDE TOGGLE */
        .patlis-slide-toggle {
            flex: 1;
            min-height: 50px;
            padding: 0 12px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            border: 0;
            background: transparent;

            cursor: pointer;
            text-align: left;
            font-weight: 600;
        }

        .patlis-slide-toggle .dashicons {transition: transform .2s ease;}

        .patlis-slide.is-open
        .patlis-slide-toggle
        .dashicons {
            transform: rotate(180deg);
        }

        /* ENABLE */
        .patlis-slide-enabled {
            padding: 0 15px;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        /* CONTENT */
        .patlis-slide-content {
            display: none;
            padding: 20px;
            border-top: 1px solid #dcdcde;
        }

        .patlis-slide.is-open .patlis-slide-content {display: block;}

        /* FIELDS */
        .patlis-row {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 10px;
        }

        .patlis-field {margin-bottom: 20px;}

        .patlis-field > label {display: block;margin-bottom: 6px;}

        .patlis-layout-options label > span {display: block;margin-bottom: 6px;font-weight: 600;}

        .patlis-field .regular-text {
            width: 100%;
            max-width: 500px;
        }

        /* IMAGE */
        .patlis-image-preview img {
            display: block;
            width: auto;
            max-width: 260px;
            max-height: 180px;
            margin: 10px 0;
            border: 1px solid #dcdcde;
        }

        .patlis-image-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* CTA */
        .patlis-cta-group {
            margin: 25px 0;
            padding: 15px;
            border: 1px solid #dcdcde;
        }

        .patlis-cta-group legend {padding: 0 8px;}

        /* ACTIONS */
        .patlis-slide-actions {margin-top: 25px;}

        .patlis-add-slide {margin-top: 10px !important;}

        .patlis-add-slide .dashicons {
            vertical-align: middle;
            margin-top: 3px;
        }

        .patlis-save-slides {
            max-width: 1100px;
            margin-top: 20px;
        }

        /* SORTING */
        .patlis-slide.ui-sortable-helper {box-shadow: 0 5px 15px rgba(0,0,0,.15);}

        .patlis-slide-sort-placeholder {
            height: 52px;
            margin-bottom: 10px;
            border: 2px dashed #8c8f94;
            background: #f6f7f7;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const template = document.getElementById('patlis-slide-template');

            /** Re-index slides inside one group. */
            function reindexSlides(groupElement) {

                const group = groupElement.dataset.group;
                const slides = groupElement.querySelectorAll('.patlis-slide');

                slides.forEach(function (slide, index) {

                    slide.dataset.slideIndex = index;

                    slide
                        .querySelectorAll('[data-field]')
                        .forEach(function (field) {

                            const fieldName = field.dataset.field;
                            const language = field.dataset.language || '';

                            field.name =
                                'sliders[' +
                                group +
                                '][slides][' +
                                index +
                                '][' +
                                fieldName +
                                ']' +
                                (language ? '[' + language + ']' : '') +
                                (field.dataset.option ? '[' + field.dataset.layout + '][' + field.dataset.option + ']' : '');

                        });

                    const sort = slide.querySelector('[data-field="sort"]');

                    if (sort) {
                        sort.value = index + 1;
                    }

                    updateSlideTitle(slide, index);

                });
            }

            function updateLayoutOptions(slide) {
                const layout = slide.querySelector('[data-field="layout"]').value;

                slide.querySelectorAll('.patlis-layout-options').forEach(function (panel) {
                    const isActive = panel.dataset.layout === layout;
                    panel.hidden = !isActive;
                    panel.querySelectorAll('[data-field="options"]').forEach(function (field) {
                        field.disabled = !isActive;
                    });
                });
            }

            document.addEventListener('input', function (event) {
                if (event.target.matches('input[type="range"][data-field="options"]')) {
                    event.target.closest('.patlis-field').querySelector('.patlis-range-value').textContent = event.target.value;
                }
            });

            document.addEventListener('change', function (event) {
                if (event.target.matches('[data-field="layout"]')) {
                    updateLayoutOptions(event.target.closest('.patlis-slide'));
                }
            });

            /**
             * Internal name -> accordion title.
             * Fallback: Slide X.
             */
            function updateSlideTitle(slide, index = null) {

                const input = slide.querySelector('.patlis-slide-name');
                const title = slide.querySelector('.patlis-slide-title');

                if (!title) {
                    return;
                }

                if (input && input.value.trim() !== '') {
                    title.textContent = input.value.trim();
                    return;
                }

                if (index === null) {

                    const group = slide.closest('.patlis-slider-group');
                    const slides = Array.from(group.querySelectorAll('.patlis-slide'));

                    index = slides.indexOf(slide);
                }

                title.textContent = 'Slide ' + (index + 1);
            }

            /** GROUP ACCORDIONS */
            document
                .querySelectorAll('.patlis-group-header')
                .forEach(function (header) {

                    header.addEventListener('click', function () {

                        header
                            .closest('.patlis-slider-group')
                            .classList.toggle('is-open');

                    });
                });

            /**  ADD SLIDE */
            document
                .querySelectorAll('.patlis-add-slide')
                .forEach(function (button) {

                    button.addEventListener('click', function () {

                        const groupElement = button.closest('.patlis-slider-group');
                        const list = groupElement.querySelector('.patlis-slides-list');
                        const fragment = template.content.cloneNode(true);

                        list.appendChild(fragment);

                        const slide = list.lastElementChild;

                        reindexSlides(groupElement);
                        updateLayoutOptions(slide);

                        slide.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    });
                });


            /**  DELEGATED EVENTS */
            document.addEventListener('click', function (event) {

                /* SLIDE ACCORDION */
                const toggle = event.target.closest('.patlis-slide-toggle');

                if (toggle) {
                    toggle.closest('.patlis-slide').classList.toggle('is-open');
                    return;
                }


                /* REMOVE SLIDE */
                const removeSlide = event.target.closest('.patlis-remove-slide');

                if (removeSlide) {

                    const groupElement = removeSlide.closest('.patlis-slider-group');

                    removeSlide.closest('.patlis-slide').remove();
                    reindexSlides(groupElement);

                    return;
                }


                /* SELECT IMAGE */
                const selectImage = event.target.closest('.patlis-select-image');

                if (selectImage) {

                    const slide = selectImage.closest('.patlis-slide');
                    const imageId = slide.querySelector('.patlis-image-id');
                    const preview = slide.querySelector('.patlis-image-preview');
                    const removeImage = slide.querySelector('.patlis-remove-image');

                    const frame = wp.media({

                        title: '<?php echo esc_js(__('Select image', 'patlis-core')); ?>',

                        button: {
                            text: '<?php echo esc_js(__('Use this image', 'patlis-core')); ?>'
                        },

                        multiple: false
                    });

                    frame.on('select', function () {

                        const attachment = frame
                            .state()
                            .get('selection')
                            .first()
                            .toJSON();

                        imageId.value = attachment.id;

                        const imageUrl =
                            attachment.sizes && attachment.sizes.medium
                                ? attachment.sizes.medium.url
                                : attachment.url;

                        preview.innerHTML =
                            '<img src="' + imageUrl + '" alt="">';

                        removeImage.hidden = false;
                    });

                    frame.open();

                    return;
                }


                /* REMOVE IMAGE */
                const removeImage = event.target.closest('.patlis-remove-image');

                if (removeImage) {

                    const slide = removeImage.closest('.patlis-slide');

                    slide.querySelector('.patlis-image-id').value = '';
                    slide.querySelector('.patlis-image-preview').innerHTML = '';
                    removeImage.hidden = true;
                }
            });


            /** INTERNAL NAME -> ACCORDION TITLE */
            document.addEventListener('input', function (event) {

                if (!event.target.classList.contains('patlis-slide-name')) {
                    return;
                }

                const slide = event.target.closest('.patlis-slide');
                updateSlideTitle(slide);
            });

            /** DRAG & DROP SORT */
            jQuery('.patlis-slides-list').sortable({

                handle: '.patlis-slide-drag',
                placeholder: 'patlis-slide-sort-placeholder',
                forcePlaceholderSize: true,

                update: function () {

                    const groupElement = this.closest('.patlis-slider-group');
                    reindexSlides(groupElement);
                }
            });

            /**  NORMALIZE EXISTING SAVED SLIDES ON LOAD */
            document
                .querySelectorAll('.patlis-slider-group')
                .forEach(function (groupElement) {
                    reindexSlides(groupElement);
                });

        });
    </script>

    <?php
}