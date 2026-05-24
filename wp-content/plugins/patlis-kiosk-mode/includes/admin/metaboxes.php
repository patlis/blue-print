<?php
if (!defined('ABSPATH')) exit;

/**
 * Add meta box
 */
function patlis_kiosk_add_meta_boxes() {
    add_meta_box(
        'patlis_kiosk_slide_settings',
        __('Slide Settings', 'patlis-kiosk-mode'),
        'patlis_kiosk_slide_settings_html',
        'kiosk_slide',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'patlis_kiosk_add_meta_boxes');

/**
 * Render meta box HTML
 */
function patlis_kiosk_slide_settings_html($post) {
    wp_nonce_field('patlis_kiosk_save_meta', 'patlis_kiosk_meta_nonce');

    $slide_type = get_post_meta($post->ID, '_slide_type', true) ?: 'image';
    $video_url = get_post_meta($post->ID, '_video_url', true);
    $html_content = get_post_meta($post->ID, '_html_content', true);
    $html_position = get_post_meta($post->ID, '_html_position', true) ?: 'center-center';
    $html_theme = get_post_meta($post->ID, '_html_theme', true) ?: 'light';
    $html_bg_color = get_post_meta($post->ID, '_html_bg_color', true) ?: '#000000';
    $html_bg_image = get_post_meta($post->ID, '_html_bg_image', true);
    $html_overlay_opacity = get_post_meta($post->ID, '_html_overlay_opacity', true) ?: '50';
    $slide_duration = get_post_meta($post->ID, '_slide_duration', true) ?: '5';

    ?>
    <style>
        .patlis-kiosk-field { margin-bottom: 20px; }
        .patlis-kiosk-field label { font-weight: bold; display: block; margin-bottom: 5px; }
        .patlis-kiosk-field select, .patlis-kiosk-field input[type="text"], .patlis-kiosk-field input[type="number"] { width: 100%; max-width: 400px; }
        .kiosk-conditional-field { display: none; padding: 20px; background: #fafafa; border: 1px solid #ddd; margin-top: 15px; border-radius: 4px; }
    </style>
    <script>
        jQuery(document).ready(function($){
            function toggleFields() {
                var type = $('#slide_type').val();
                $('.kiosk-conditional-field').hide();
                if(type === 'video') {
                    $('#kiosk_video_fields').fadeIn(200);
                } else if(type === 'html') {
                    $('#kiosk_html_fields').fadeIn(200);
                } else if(type === 'image') {
                    $('#kiosk_image_fields').fadeIn(200);
                }
            }
            $('#slide_type').change(toggleFields);
            toggleFields();

            // Image uploader
            var frame;
            $('.kiosk-upload-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var target = $(btn.data('target'));
                var preview = $(btn.data('preview'));

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: 'Select Image',
                    button: { text: 'Use this image' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    target.val(attachment.url);
                    preview.attr('src', attachment.url).show();
                });

                frame.open();
            });

            // Video uploader
            var videoFrame;
            $('.kiosk-video-upload-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                var target = $(btn.data('target'));

                if (videoFrame) {
                    videoFrame.open();
                    return;
                }

                videoFrame = wp.media({
                    title: 'Select Video',
                    button: { text: 'Use this video' },
                    library: { type: 'video' },
                    multiple: false
                });

                videoFrame.on('select', function() {
                    var attachment = videoFrame.state().get('selection').first().toJSON();
                    target.val(attachment.url);
                });

                videoFrame.open();
            });

            $('.kiosk-remove-btn').on('click', function(e){
                e.preventDefault();
                var btn = $(this);
                var target = $(btn.data('target'));
                var preview = $(btn.data('preview'));
                target.val('');
                preview.hide().attr('src', '');
            });

            // Color picker
            if($.fn.wpColorPicker) {
                $('.kiosk-color-picker').wpColorPicker();
            }
        });
    </script>

    <div class="patlis-kiosk-field">
        <label for="slide_type"><?php _e('Slide Type', 'patlis-kiosk-mode'); ?></label>
        <select name="slide_type" id="slide_type">
            <option value="image" <?php selected($slide_type, 'image'); ?>>Image (Standard)</option>
            <option value="video" <?php selected($slide_type, 'video'); ?>>Video</option>
            <option value="html" <?php selected($slide_type, 'html'); ?>>HTML (Complex)</option>
        </select>
        <p class="description">Select the visual format of this slide.</p>
    </div>

    <!-- Duration -->
    <div class="patlis-kiosk-field">
        <label for="slide_duration"><?php _e('Slide Duration (seconds)', 'patlis-kiosk-mode'); ?></label>
        <input type="number" name="slide_duration" id="slide_duration" value="<?php echo esc_attr($slide_duration); ?>" step="1" min="1"/>
        <p class="description">How long should this slide stay on screen? (e.g., 5 seconds)</p>
    </div>

    <!-- Order -->
    <div class="patlis-kiosk-field">
        <label for="menu_order"><?php _e('Display Order', 'patlis-kiosk-mode'); ?></label>
        <input type="number" name="menu_order" id="menu_order" value="<?php echo esc_attr($post->menu_order); ?>" step="1"/>
        <p class="description">Lower numbers display first (e.g. 1 displays before 5). This can also be changed using drag and drop in the Slides list if you use a reordering plugin.</p>
    </div>

    <!-- Image Fields Note -->
    <div id="kiosk_image_fields" class="kiosk-conditional-field">
        <p><strong>Image Slide:</strong> Simply set the <em>Featured Image</em> on the right sidebar and it will be used as the slide background.</p>
    </div>

    <!-- Video Fields -->
    <div id="kiosk_video_fields" class="kiosk-conditional-field">
        <div class="patlis-kiosk-field">
            <label for="video_url"><?php _e('Video URL', 'patlis-kiosk-mode'); ?></label>
            <div style="display: flex; gap: 10px; align-items: flex-start;">
                <input type="text" name="video_url" id="video_url" value="<?php echo esc_attr($video_url); ?>" placeholder="https://..." style="flex: 1; max-width: 100%;" />
                <a href="#" class="button kiosk-video-upload-btn" data-target="#video_url">Select Video</a>
            </div>
            <p class="description">Provide a YouTube URL, Vimeo URL, or select/upload a local MP4 file.</p>
        </div>
    </div>

    <!-- HTML Fields -->
    <div id="kiosk_html_fields" class="kiosk-conditional-field">
        
        <div style="display:flex; gap: 20px; flex-wrap: wrap;">
            <div class="patlis-kiosk-field" style="flex: 1; min-width: 250px;">
                <label for="html_position"><?php _e('Text Position', 'patlis-kiosk-mode'); ?></label>
                <select name="html_position" id="html_position">
                    <option value="top-left" <?php selected($html_position, 'top-left'); ?>>Top Left</option>
                    <option value="top-center" <?php selected($html_position, 'top-center'); ?>>Top Center</option>
                    <option value="top-right" <?php selected($html_position, 'top-right'); ?>>Top Right</option>
                    <option value="center-left" <?php selected($html_position, 'center-left'); ?>>Center Left</option>
                    <option value="center-center" <?php selected($html_position, 'center-center'); ?>>Center</option>
                    <option value="center-right" <?php selected($html_position, 'center-right'); ?>>Center Right</option>
                    <option value="bottom-left" <?php selected($html_position, 'bottom-left'); ?>>Bottom Left</option>
                    <option value="bottom-center" <?php selected($html_position, 'bottom-center'); ?>>Bottom Center</option>
                    <option value="bottom-right" <?php selected($html_position, 'bottom-right'); ?>>Bottom Right</option>
                </select>
            </div>

            <div class="patlis-kiosk-field" style="flex: 1; min-width: 250px;">
                <label for="html_theme"><?php _e('Text Theme', 'patlis-kiosk-mode'); ?></label>
                <select name="html_theme" id="html_theme">
                    <option value="light" <?php selected($html_theme, 'light'); ?>>Light Text (for dark backgrounds)</option>
                    <option value="dark" <?php selected($html_theme, 'dark'); ?>>Dark Text (for light backgrounds)</option>
                </select>
            </div>
        </div>

        <div style="display:flex; gap: 20px; flex-wrap: wrap;">
            <div class="patlis-kiosk-field" style="flex: 1; min-width: 250px;">
                <label for="html_bg_color"><?php _e('Background Color', 'patlis-kiosk-mode'); ?></label>
                <input type="text" name="html_bg_color" id="html_bg_color" value="<?php echo esc_attr($html_bg_color); ?>" class="kiosk-color-picker" />
            </div>

            <div class="patlis-kiosk-field" style="flex: 1; min-width: 250px;">
                <label for="html_overlay_opacity"><?php _e('Background Overlay Opacity', 'patlis-kiosk-mode'); ?></label>
                <select name="html_overlay_opacity" id="html_overlay_opacity">
                    <option value="0" <?php selected($html_overlay_opacity, '0'); ?>>0% (No color)</option>
                    <option value="25" <?php selected($html_overlay_opacity, '25'); ?>>25%</option>
                    <option value="50" <?php selected($html_overlay_opacity, '50'); ?>>50%</option>
                    <option value="75" <?php selected($html_overlay_opacity, '75'); ?>>75%</option>
                    <option value="100" <?php selected($html_overlay_opacity, '100'); ?>>100% (Solid color)</option>
                </select>
            </div>
        </div>

        <div class="patlis-kiosk-field">
            <label for="html_bg_image"><?php _e('Background Image', 'patlis-kiosk-mode'); ?></label>
            <input type="hidden" name="html_bg_image" id="html_bg_image" value="<?php echo esc_attr($html_bg_image); ?>" />
            <img id="html_bg_image_preview" src="<?php echo esc_attr($html_bg_image); ?>" style="max-width: 200px; display: <?php echo $html_bg_image ? 'block' : 'none'; ?>; margin-bottom: 10px; border-radius: 4px; box-shadow: 0 0 5px rgba(0,0,0,0.1);" />
            <a href="#" class="button kiosk-upload-btn" data-target="#html_bg_image" data-preview="#html_bg_image_preview">Select Image</a>
            <a href="#" class="button kiosk-remove-btn" data-target="#html_bg_image" data-preview="#html_bg_image_preview">Remove Image</a>
        </div>

        <div class="patlis-kiosk-field">
            <label><?php _e('HTML Content', 'patlis-kiosk-mode'); ?></label>
            <?php 
            wp_editor($html_content, 'html_content', array(
                'textarea_name' => 'html_content',
                'media_buttons' => true,
                'textarea_rows' => 10,
                'teeny'         => false
            )); 
            ?>
        </div>
    </div>
    <?php
}

/**
 * Save meta box data
 */
function patlis_kiosk_save_meta_box($post_id) {
    if (!isset($_POST['patlis_kiosk_meta_nonce']) || !wp_verify_nonce($_POST['patlis_kiosk_meta_nonce'], 'patlis_kiosk_save_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array(
        'slide_type'           => 'sanitize_text_field',
        'video_url'            => 'esc_url_raw',
        'html_position'        => 'sanitize_text_field',
        'html_theme'           => 'sanitize_text_field',
        'html_bg_color'        => 'sanitize_hex_color',
        'html_bg_image'        => 'esc_url_raw',
        'html_overlay_opacity' => 'sanitize_text_field',
        'slide_duration'       => 'absint'
    );

    foreach ($fields as $field => $sanitize_func) {
        if (isset($_POST[$field])) {
            $value = $_POST[$field];
            
            // Allow hex color validation to fallback gracefully instead of returning nothing if empty
            if ($field === 'html_bg_color' && empty($value)) {
                $sanitized = '';
            } else {
                $sanitized = function_exists($sanitize_func) ? call_user_func($sanitize_func, $value) : sanitize_text_field($value);
            }
            
            update_post_meta($post_id, '_' . $field, $sanitized);
        }
    }

    if (isset($_POST['html_content'])) {
        update_post_meta($post_id, '_html_content', wp_kses_post($_POST['html_content']));
    }

    if (isset($_POST['menu_order'])) {
        $menu_order = intval($_POST['menu_order']);
        if ((int) get_post_field('menu_order', $post_id) !== $menu_order) {
            remove_action('save_post_kiosk_slide', 'patlis_kiosk_save_meta_box');
            wp_update_post(array(
                'ID'         => $post_id,
                'menu_order' => $menu_order,
            ));
            add_action('save_post_kiosk_slide', 'patlis_kiosk_save_meta_box');
        }
    }
}
add_action('save_post_kiosk_slide', 'patlis_kiosk_save_meta_box');

/**
 * Add custom columns to Kiosk Slides list.
 */
function patlis_kiosk_admin_columns($columns) {
    $new_columns = array();

    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;

        if ($key === 'title') {
            $new_columns['slide_type'] = __('Slide Type', 'patlis-kiosk-mode');
            $new_columns['slide_duration'] = __('Duration', 'patlis-kiosk-mode');
            $new_columns['menu_order'] = __('Order', 'patlis-kiosk-mode');
        }
    }

    return $new_columns;
}
add_filter('manage_kiosk_slide_posts_columns', 'patlis_kiosk_admin_columns');

/**
 * Render custom column values.
 */
function patlis_kiosk_admin_column_content($column, $post_id) {
    if ($column === 'slide_type') {
        $slide_type = get_post_meta($post_id, '_slide_type', true);

        if ($slide_type === 'video') {
            echo esc_html__('Video', 'patlis-kiosk-mode');
        } elseif ($slide_type === 'html') {
            echo esc_html__('HTML', 'patlis-kiosk-mode');
        } else {
            echo esc_html__('Image', 'patlis-kiosk-mode');
        }
    }

    if ($column === 'slide_duration') {
        $duration = (int) get_post_meta($post_id, '_slide_duration', true);
        echo esc_html($duration > 0 ? $duration . 's' : '-');
    }

    if ($column === 'menu_order') {
        echo esc_html((string) get_post_field('menu_order', $post_id));
    }
}
add_action('manage_kiosk_slide_posts_custom_column', 'patlis_kiosk_admin_column_content', 10, 2);

/**
 * Make order column sortable.
 */
function patlis_kiosk_sortable_columns($columns) {
    $columns['menu_order'] = 'menu_order';
    return $columns;
}
add_filter('manage_edit-kiosk_slide_sortable_columns', 'patlis_kiosk_sortable_columns');

/**
 * Default sort in Kiosk Slides admin list by menu_order ASC.
 */
function patlis_kiosk_default_admin_sort($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    global $pagenow;

    if ($pagenow !== 'edit.php') {
        return;
    }

    if ($query->get('post_type') !== 'kiosk_slide') {
        return;
    }

    if (!empty($query->get('orderby'))) {
        return;
    }

    $query->set('orderby', 'menu_order');
    $query->set('order', 'ASC');
}
add_action('pre_get_posts', 'patlis_kiosk_default_admin_sort');

/**
 * Enqueue scripts for WP Admin Meta Boxes
 */
function patlis_kiosk_admin_scripts($hook) {
    global $post_type;
    if ($hook == 'post-new.php' || $hook == 'post.php') {
        if ($post_type === 'kiosk_slide') {
            wp_enqueue_media();
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
    }
}
add_action('admin_enqueue_scripts', 'patlis_kiosk_admin_scripts');