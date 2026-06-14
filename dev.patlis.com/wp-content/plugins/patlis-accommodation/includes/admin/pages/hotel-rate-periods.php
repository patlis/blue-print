<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', 'patlis_acc_hotel_rates_register_metabox');
function patlis_acc_hotel_rates_register_metabox() {
    if (!function_exists('patlis_accommodation_is_enabled_for_version') || !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    add_meta_box(
        'patlis_acc_rate_periods_fields',
        'Hotel Rate Period Details',
        'patlis_acc_hotel_rates_render_metabox',
        'hotel_rate_periods',
        'normal',
        'high'
    );
}

function patlis_acc_hotel_rates_render_metabox($post) {
    wp_nonce_field('patlis_acc_rate_periods_fields_save', 'patlis_acc_rate_periods_fields_nonce');

    $v = function ($key) use ($post) {
        return get_post_meta($post->ID, $key, true);
    };

    $start_day   = (int) $v('hotel_rate_period_start_day');
    $start_month = (int) $v('hotel_rate_period_start_month');
    $end_day     = (int) $v('hotel_rate_period_end_day');
    $end_month   = (int) $v('hotel_rate_period_end_month');
    $active      = (int) $v('hotel_rate_period_active');
    $priority    = (int) $v('hotel_rate_period_priority');
    $order       = (int) $v('hotel_rate_period_order');

    echo '<table class="form-table" role="presentation">';

    echo '<tr><th scope="row"><label for="hotel_rate_period_start_day">Start day</label></th>';
    echo '<td><input type="number" min="1" max="31" step="1" class="small-text" id="hotel_rate_period_start_day" name="hotel_rate_period_start_day" value="' . esc_attr($start_day) . '"></td></tr>';

    echo '<tr><th scope="row"><label for="hotel_rate_period_start_month">Start month</label></th>';
    echo '<td><input type="number" min="1" max="12" step="1" class="small-text" id="hotel_rate_period_start_month" name="hotel_rate_period_start_month" value="' . esc_attr($start_month) . '"></td></tr>';

    echo '<tr><th scope="row"><label for="hotel_rate_period_end_day">End day</label></th>';
    echo '<td><input type="number" min="1" max="31" step="1" class="small-text" id="hotel_rate_period_end_day" name="hotel_rate_period_end_day" value="' . esc_attr($end_day) . '"></td></tr>';

    echo '<tr><th scope="row"><label for="hotel_rate_period_end_month">End month</label></th>';
    echo '<td><input type="number" min="1" max="12" step="1" class="small-text" id="hotel_rate_period_end_month" name="hotel_rate_period_end_month" value="' . esc_attr($end_month) . '"></td></tr>';

    echo '<tr><th scope="row">Active</th>';
    echo '<td><label><input type="checkbox" id="hotel_rate_period_active" name="hotel_rate_period_active" value="1" ' . checked($active, 1, false) . '> Active</label></td></tr>';

    echo '<tr><th scope="row"><label for="hotel_rate_period_priority">Priority (1-10)</label></th>';
    echo '<td><input type="number" min="1" max="10" step="1" class="small-text" id="hotel_rate_period_priority" name="hotel_rate_period_priority" value="' . esc_attr($priority > 0 ? $priority : 1) . '"></td></tr>';

    echo '<tr><th scope="row"><label for="hotel_rate_period_order">Order</label></th>';
    echo '<td><input type="number" min="0" step="1" class="small-text" id="hotel_rate_period_order" name="hotel_rate_period_order" value="' . esc_attr($order) . '"></td></tr>';

    echo '</table>';
}

add_action('save_post_hotel_rate_periods', 'patlis_acc_hotel_rates_save_metabox');
function patlis_acc_hotel_rates_save_metabox($post_id) {
    if (!isset($_POST['patlis_acc_rate_periods_fields_nonce']) || !wp_verify_nonce($_POST['patlis_acc_rate_periods_fields_nonce'], 'patlis_acc_rate_periods_fields_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $clamp = function ($value, $min, $max) {
        $value = (int) $value;
        if ($value < $min) return $min;
        if ($value > $max) return $max;
        return $value;
    };

    $start_day = isset($_POST['hotel_rate_period_start_day']) ? $clamp($_POST['hotel_rate_period_start_day'], 1, 31) : 1;
    $start_month = isset($_POST['hotel_rate_period_start_month']) ? $clamp($_POST['hotel_rate_period_start_month'], 1, 12) : 1;
    $end_day = isset($_POST['hotel_rate_period_end_day']) ? $clamp($_POST['hotel_rate_period_end_day'], 1, 31) : 1;
    $end_month = isset($_POST['hotel_rate_period_end_month']) ? $clamp($_POST['hotel_rate_period_end_month'], 1, 12) : 1;
    $active = !empty($_POST['hotel_rate_period_active']) ? 1 : 0;
    $priority = isset($_POST['hotel_rate_period_priority']) ? $clamp($_POST['hotel_rate_period_priority'], 1, 10) : 1;
    $order = isset($_POST['hotel_rate_period_order']) ? max(0, (int) $_POST['hotel_rate_period_order']) : 0;

    update_post_meta($post_id, 'hotel_rate_period_start_day', $start_day);
    update_post_meta($post_id, 'hotel_rate_period_start_month', $start_month);
    update_post_meta($post_id, 'hotel_rate_period_end_day', $end_day);
    update_post_meta($post_id, 'hotel_rate_period_end_month', $end_month);
    update_post_meta($post_id, 'hotel_rate_period_active', $active);
    update_post_meta($post_id, 'hotel_rate_period_priority', $priority);
    update_post_meta($post_id, 'hotel_rate_period_order', $order);
}
