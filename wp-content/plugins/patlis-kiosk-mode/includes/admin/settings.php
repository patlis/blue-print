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
}

function patlis_kiosk_section_callback() {
    echo '<p>Configure the kiosk mode settings for your touch screen displays:</p>';
}

function patlis_kiosk_field_inactivity_timeout() {
    $timeout = get_option('patlis_kiosk_inactivity_timeout', 60);
    ?>
    <input type="number" name="patlis_kiosk_inactivity_timeout" min="10" max="600" value="<?php echo esc_attr($timeout); ?>" class="small-text">
    <p class="description">Time in seconds before page redirects due to inactivity (10-600 seconds)</p>
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
    <p class="description">Select only the default-language page. The plugin automatically resolves translated target URLs for each active language.</p>
    <?php
}
