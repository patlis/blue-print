<?php
if (!defined('ABSPATH')) exit;

function patlis_acc_parse_room_ids($raw): array {
    if (is_array($raw)) {
        $ids = $raw;
    } elseif (is_string($raw)) {
        $parts = preg_split('/[\s,]+/', trim($raw));
        $ids = is_array($parts) ? $parts : [];
    } else {
        $ids = [];
    }

    $ids = array_map('absint', $ids);
    $ids = array_values(array_filter($ids, function ($id) {
        return $id > 0;
    }));

    return array_values(array_unique($ids));
}

function patlis_acc_room_id_to_default_language(int $room_id): int {
    if ($room_id <= 0) {
        return 0;
    }

    if (
        function_exists('pll_default_language')
        && function_exists('pll_get_post')
    ) {
        $default_lang = pll_default_language('slug');
        if (is_string($default_lang) && $default_lang !== '') {
            $default_room_id = (int) pll_get_post($room_id, $default_lang);
            if ($default_room_id > 0) {
                return $default_room_id;
            }
        }
    }

    return $room_id;
}

function patlis_acc_get_room_options_for_rates(): array {
    $args = [
        'post_type'      => 'patlis_room',
        'post_status'    => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ];

    if (function_exists('pll_default_language')) {
        $default_lang = pll_default_language('slug');
        if (is_string($default_lang) && $default_lang !== '') {
            $args['lang'] = $default_lang;
        }
    }

    $posts = get_posts($args);
    return is_array($posts) ? $posts : [];
}

add_action('add_meta_boxes', 'patlis_acc_room_rates_register_metabox');
function patlis_acc_room_rates_register_metabox() {
    if (!function_exists('patlis_accommodation_is_enabled_for_version') || !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    add_meta_box(
        'patlis_acc_room_rate_fields',
        'Room Rate Fields',
        'patlis_acc_room_rates_render_metabox',
        'patlis_room_rate',
        'normal',
        'high'
    );
}

function patlis_acc_room_rates_render_metabox($post) {
    wp_nonce_field('patlis_acc_room_rate_fields_save', 'patlis_acc_room_rate_fields_nonce');

    $v = function ($key) use ($post) {
        return get_post_meta($post->ID, $key, true);
    };

    $period_id = (int) $v('patlis_acc_period_id');
    $price = (string) $v('patlis_acc_price');
    $price_type = (int) $v('patlis_acc_price_type');
    $price_surfix = (int) $v('patlis_acc_price_surfix');
    $min_nights = (int) $v('patlis_acc_min_nights');
    $active = (int) $v('patlis_acc_active');

    $room_ids_raw = $v('patlis_acc_room_ids');
    $room_ids = patlis_acc_parse_room_ids($room_ids_raw);
    $room_ids = array_values(array_unique(array_filter(array_map('patlis_acc_room_id_to_default_language', $room_ids))));

    $periods = get_posts([
        'post_type'      => 'hotel_rate_periods',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ]);

    $rooms = patlis_acc_get_room_options_for_rates();

    echo '<table class="form-table" role="presentation">';

    echo '<tr><th scope="row"><label for="patlis_acc_period_id">Rate Period</label></th><td>';
    echo '<select id="patlis_acc_period_id" name="patlis_acc_period_id">';
    echo '<option value="0">Select period</option>';
    foreach ($periods as $period) {
        $pid = (int) $period->ID;
        echo '<option value="' . $pid . '" ' . selected($period_id, $pid, false) . '>' . esc_html(get_the_title($pid)) . '</option>';
    }
    echo '</select>';
    echo '</td></tr>';

    echo '<tr><th scope="row"><label for="patlis_acc_price">Price</label></th>';
    echo '<td><input type="text" class="small-text" id="patlis_acc_price" name="patlis_acc_price" value="' . esc_attr($price) . '"></td></tr>';

    echo '<tr><th scope="row"><label for="patlis_acc_price_type">Price Type</label></th><td>';
    echo '<select id="patlis_acc_price_type" name="patlis_acc_price_type">';
    echo '<option value="1" ' . selected($price_type, 1, false) . '>1 - fixed</option>';
    echo '<option value="2" ' . selected($price_type, 2, false) . '>2 - from</option>';
    echo '<option value="3" ' . selected($price_type, 3, false) . '>3 - on request</option>';
    echo '</select>';
    echo '</td></tr>';

    echo '<tr><th scope="row"><label for="patlis_acc_price_surfix">Price Surfix</label></th><td>';
    echo '<select id="patlis_acc_price_surfix" name="patlis_acc_price_surfix">';
    echo '<option value="1" ' . selected($price_surfix, 1, false) . '>1 - per night</option>';
    echo '<option value="2" ' . selected($price_surfix, 2, false) . '>2 - per person per night</option>';
    echo '<option value="3" ' . selected($price_surfix, 3, false) . '>3 - per stay</option>';
    echo '<option value="4" ' . selected($price_surfix, 4, false) . '>4 - per week</option>';
    echo '<option value="5" ' . selected($price_surfix, 5, false) . '>5 - per month</option>';
    echo '</select>';
    echo '</td></tr>';

    echo '<tr><th scope="row"><label for="patlis_acc_min_nights">Min Nights</label></th>';
    echo '<td><input type="number" min="0" step="1" class="small-text" id="patlis_acc_min_nights" name="patlis_acc_min_nights" value="' . esc_attr($min_nights) . '"></td></tr>';

    echo '<tr><th scope="row">Active</th>';
    echo '<td><label><input type="checkbox" id="patlis_acc_active" name="patlis_acc_active" value="1" ' . checked($active, 1, false) . '> Active</label></td></tr>';

    echo '<tr><th scope="row">Rooms</th><td>';
    if (!empty($rooms)) {
        echo '<fieldset style="max-height: 260px; overflow: auto; border: 1px solid #dcdcde; padding: 10px; border-radius: 4px;">';
        foreach ($rooms as $room) {
            $rid = (int) $room->ID;
            $label = get_the_title($rid);
            if (!is_string($label) || $label === '') {
                $label = '(no title)';
            }

            echo '<label style="display:block; margin-bottom:6px;">';
            echo '<input type="checkbox" name="patlis_acc_room_ids[]" value="' . $rid . '" ' . checked(in_array($rid, $room_ids, true), true, false) . '> ';
            echo esc_html($label) ;
            echo '</label>';
        }
        echo '</fieldset>';
    } else {
        echo '<p class="description">No rooms found.</p>';
    }

    $missing_room_ids = array_values(array_diff($room_ids, array_map(function ($r) {
        return (int) $r->ID;
    }, $rooms)));
    if (!empty($missing_room_ids)) {
        echo '<p class="description">Saved room IDs not in current list: ' . esc_html(implode(',', $missing_room_ids)) . '</p>';
    }
    echo '</td></tr>';

    echo '</table>';
}

add_action('save_post_patlis_room_rate', 'patlis_acc_room_rates_save_metabox');
function patlis_acc_room_rates_save_metabox($post_id) {
    if (!isset($_POST['patlis_acc_room_rate_fields_nonce']) || !wp_verify_nonce($_POST['patlis_acc_room_rate_fields_nonce'], 'patlis_acc_room_rate_fields_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Shared rate fields are synchronized across translations.
    // Never overwrite them from non-default language edit screens.
    if (function_exists('patlis_is_non_default_language_edit_context') && patlis_is_non_default_language_edit_context()) {
        return;
    }

    $clamp = function ($value, $min, $max) {
        $value = (int) $value;
        if ($value < $min) return $min;
        if ($value > $max) return $max;
        return $value;
    };

    $period_id = isset($_POST['patlis_acc_period_id']) ? max(0, (int) $_POST['patlis_acc_period_id']) : 0;
    $price = isset($_POST['patlis_acc_price']) ? sanitize_text_field((string) $_POST['patlis_acc_price']) : '';
    $price_type = isset($_POST['patlis_acc_price_type']) ? $clamp($_POST['patlis_acc_price_type'], 1, 3) : 1;
    $price_surfix = isset($_POST['patlis_acc_price_surfix']) ? $clamp($_POST['patlis_acc_price_surfix'], 1, 5) : 1;
    $min_nights = isset($_POST['patlis_acc_min_nights']) ? max(0, (int) $_POST['patlis_acc_min_nights']) : 0;
    $active = !empty($_POST['patlis_acc_active']) ? 1 : 0;

    $room_ids = patlis_acc_parse_room_ids($_POST['patlis_acc_room_ids'] ?? []);
    $room_ids = array_values(array_unique(array_filter(array_map('patlis_acc_room_id_to_default_language', $room_ids))));

    update_post_meta($post_id, 'patlis_acc_period_id', $period_id);
    update_post_meta($post_id, 'patlis_acc_price', $price);
    update_post_meta($post_id, 'patlis_acc_price_type', $price_type);
    update_post_meta($post_id, 'patlis_acc_price_surfix', $price_surfix);
    update_post_meta($post_id, 'patlis_acc_min_nights', $min_nights);
    update_post_meta($post_id, 'patlis_acc_active', $active);

    if (empty($room_ids)) {
        delete_post_meta($post_id, 'patlis_acc_room_ids');
    } else {
        update_post_meta($post_id, 'patlis_acc_room_ids', $room_ids);
    }
}

add_filter('manage_patlis_room_rate_posts_columns', function (array $columns): array {
    $out = [];

    foreach ($columns as $key => $label) {
        $out[$key] = $label;

        if ($key === 'title') {
            $out['patlis_acc_price_col'] = 'Price';
        }
    }

    if (!isset($out['patlis_acc_price_col'])) {
        $out['patlis_acc_price_col'] = 'Price';
    }

    return $out;
});

add_action('manage_patlis_room_rate_posts_custom_column', function (string $column, int $post_id): void {
    if ($column !== 'patlis_acc_price_col') {
        return;
    }

    $price = (string) get_post_meta($post_id, 'patlis_acc_price', true);
    if ($price === '') {
        echo '&mdash;';
        return;
    }

    if (function_exists('patlis_format_currency')) {
        $formatted = patlis_format_currency($price);
        echo esc_html($formatted !== '' ? $formatted : $price);
        return;
    }

    echo esc_html($price);
}, 10, 2);
