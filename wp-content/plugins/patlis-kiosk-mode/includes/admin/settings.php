<?php
if (!defined('ABSPATH')) exit;

/**
 * Register plugin settings
 */
function patlis_kiosk_register_settings() {
    register_setting('patlis_kiosk_settings', 'patlis_kiosk_inactivity_timeout', [
        'sanitize_callback' => 'patlis_kiosk_sanitize_inactivity_timeout',
    ]);

    register_setting('patlis_kiosk_settings', 'patlis_kiosk_target_page_id', [
        'sanitize_callback' => 'patlis_kiosk_sanitize_target_page_id',
    ]);

    register_setting('patlis_kiosk_settings', 'patlis_kiosk_slide_mode', [
        'sanitize_callback' => 'patlis_kiosk_sanitize_slide_mode',
    ]);

    register_setting('patlis_kiosk_settings', 'patlis_kiosk_single_slide_id', [
        'sanitize_callback' => 'patlis_kiosk_sanitize_single_slide_id',
    ]);

    register_setting('patlis_kiosk_settings', 'patlis_kiosk_single_mode_start', [
        'sanitize_callback' => 'patlis_kiosk_sanitize_single_mode_datetime',
    ]);

    register_setting('patlis_kiosk_settings', 'patlis_kiosk_single_mode_end', [
        'sanitize_callback' => 'patlis_kiosk_sanitize_single_mode_datetime',
    ]);

    // Add settings section
    add_settings_section(
        'patlis_kiosk_main',
        'Kiosk Mode Settings',
        'patlis_kiosk_section_callback',
        'patlis_kiosk_settings'
    );

    // Add fields
    add_settings_field(
        'patlis_kiosk_inactivity_timeout',
        'Inactivity Timeout (seconds)',
        'patlis_kiosk_field_inactivity_timeout',
        'patlis_kiosk_settings',
        'patlis_kiosk_main'
    );

    add_settings_field(
        'patlis_kiosk_target_page_id',
        'Target Page',
        'patlis_kiosk_field_target_page_id',
        'patlis_kiosk_settings',
        'patlis_kiosk_main'
    );

    add_settings_field(
        'patlis_kiosk_slide_mode',
        'Slides Mode',
        'patlis_kiosk_field_slide_mode',
        'patlis_kiosk_settings',
        'patlis_kiosk_main'
    );

    add_settings_field(
        'patlis_kiosk_single_slide_id',
        'Single Slide',
        'patlis_kiosk_field_single_slide_id',
        'patlis_kiosk_settings',
        'patlis_kiosk_main'
    );

    add_settings_field(
        'patlis_kiosk_single_mode_start',
        'Single Mode Start',
        'patlis_kiosk_field_single_mode_start',
        'patlis_kiosk_settings',
        'patlis_kiosk_main'
    );

    add_settings_field(
        'patlis_kiosk_single_mode_end',
        'Single Mode End',
        'patlis_kiosk_field_single_mode_end',
        'patlis_kiosk_settings',
        'patlis_kiosk_main'
    );
}

function patlis_kiosk_section_callback() {
    return;
}

function patlis_kiosk_field_inactivity_timeout() {
    $timeout = get_option('patlis_kiosk_inactivity_timeout', 60);
    ?>
    <input type="number" name="patlis_kiosk_inactivity_timeout" min="10" max="600" value="<?php echo esc_attr($timeout); ?>" class="small-text">
    <p class="description">Time in seconds before page redirects due to inactivity</p>
    <?php
}

function patlis_kiosk_field_target_page_id() {
    $target_page_id = (int) get_option('patlis_kiosk_target_page_id', 0);

    $pages_args = [
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ];

    if (function_exists('pll_default_language')) {
        $default_lang = pll_default_language('slug');
        if (is_string($default_lang) && $default_lang !== '') {
            $pages_args['lang'] = $default_lang;
        }
    }

    $page_ids = get_posts($pages_args);
    if (!is_array($page_ids)) {
        $page_ids = [];
    }

    echo '<select name="patlis_kiosk_target_page_id" id="patlis_kiosk_target_page_id">';
    echo '<option value="0">Default: /kiosk/</option>';

    foreach ($page_ids as $page_id) {
        $page_id = (int) $page_id;
        if ($page_id <= 0) {
            continue;
        }

        $title = get_the_title($page_id);
        if (!is_string($title) || $title === '') {
            $title = '(no title)';
        }

        printf(
            '<option value="%1$d" %2$s>%3$s</option>',
            $page_id,
            selected($target_page_id, $page_id, false),
            esc_html($title)
        );
    }

    echo '</select>';
    ?>
    <p class="description">Select the target page when clicking a language</p>
    <?php
}

function patlis_kiosk_field_slide_mode() {
    $slide_mode = get_option('patlis_kiosk_slide_mode', 'normal');
    ?>
    <select name="patlis_kiosk_slide_mode" id="patlis_kiosk_slide_mode">
        <option value="normal" <?php selected($slide_mode, 'normal'); ?>>Normal Mode (all published slides)</option>
        <option value="single" <?php selected($slide_mode, 'single'); ?>>Single Slide Mode</option>
    </select>
    <p class="description">Use Single Slide Mode only for emergency/manual override.</p>
    <?php
}

function patlis_kiosk_field_single_slide_id() {
    $selected_slide_id = (int) get_option('patlis_kiosk_single_slide_id', 0);

    $slide_ids = get_posts([
        'post_type'      => 'kiosk_slide',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ]);

    if (!is_array($slide_ids)) {
        $slide_ids = [];
    }

    echo '<select name="patlis_kiosk_single_slide_id" id="patlis_kiosk_single_slide_id">';
    echo '<option value="0">- Select slide -</option>';

    foreach ($slide_ids as $slide_id) {
        $slide_id = (int) $slide_id;
        if ($slide_id <= 0) {
            continue;
        }

        $title = get_the_title($slide_id);
        if (!is_string($title) || $title === '') {
            $title = '(no title)';
        }

        printf(
            '<option value="%1$d" %2$s>#%1$d - %3$s</option>',
            $slide_id,
            selected($selected_slide_id, $slide_id, false),
            esc_html($title)
        );
    }

    echo '</select>';
    ?>
    <p class="description">Shown only when Slides Mode is set to Single Slide Mode.</p>
    <?php
}

function patlis_kiosk_field_single_mode_start() {
    $start_value = (string) get_option('patlis_kiosk_single_mode_start', '');
    $timezone = wp_timezone_string();
    if (!is_string($timezone) || $timezone === '') {
        $timezone = 'UTC';
    }
    ?>
    <input type="datetime-local" name="patlis_kiosk_single_mode_start" id="patlis_kiosk_single_mode_start" value="<?php echo esc_attr($start_value); ?>">
    <p class="description">Optional. Uses Website timezone (<?php echo esc_html($timezone); ?>).</p>
    <?php
}

function patlis_kiosk_field_single_mode_end() {
    $end_value = (string) get_option('patlis_kiosk_single_mode_end', '');
    $timezone = wp_timezone_string();
    if (!is_string($timezone) || $timezone === '') {
        $timezone = 'UTC';
    }
    ?>
    <input type="datetime-local" name="patlis_kiosk_single_mode_end" id="patlis_kiosk_single_mode_end" value="<?php echo esc_attr($end_value); ?>">
    <p class="description">Optional. If both Start and End are empty, scheduled Single mode is disabled (Normal mode stays Normal).</p>
    <p class="description">Uses Website timezone (<?php echo esc_html($timezone); ?>).</p>
    <?php
}
