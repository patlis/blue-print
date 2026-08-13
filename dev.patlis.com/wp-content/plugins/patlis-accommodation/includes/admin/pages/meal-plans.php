<?php
if (!defined('ABSPATH')) exit;

const PATLIS_MEAL_PLAN_META_PRICE_ADULT = 'patlis_meal_plan_price_adult';
const PATLIS_MEAL_PLAN_META_IS_DEFAULT  = 'patlis_meal_plan_is_default';
const PATLIS_MEAL_PLAN_META_DESCRIPTION = 'patlis_meal_plan_description';

add_action('add_meta_boxes', function () {
    if (!function_exists('patlis_accommodation_is_enabled_for_version') || !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    add_meta_box(
        'patlis_meal_plan_fields',
        'Meal Plan Details',
        'patlis_acc_meal_plans_render_metabox',
        'patlis_meal_plan',
        'normal',
        'high'
    );
});

function patlis_acc_meal_plans_render_metabox($post): void
{
    wp_nonce_field('patlis_acc_meal_plan_fields_save', 'patlis_acc_meal_plan_fields_nonce');

    $price_a = get_post_meta((int) $post->ID, PATLIS_MEAL_PLAN_META_PRICE_ADULT, true);
    $is_default = (int) get_post_meta((int) $post->ID, PATLIS_MEAL_PLAN_META_IS_DEFAULT, true);
    $desc = (string) get_post_meta((int) $post->ID, PATLIS_MEAL_PLAN_META_DESCRIPTION, true);

    echo '<table class="form-table" role="presentation">';

    echo '<tr><th scope="row"><label for="patlis_meal_plan_price_adult">Extra price / adult / night (€)</label></th><td>';
    echo '<input type="number" id="patlis_meal_plan_price_adult" name="patlis_meal_plan_price_adult" value="' . esc_attr($price_a !== '' ? $price_a : '0') . '" min="0" step="0.01">';
    echo '<p class="description">0 = included in room rate.</p>';
    echo '</td></tr>';

    echo '<tr><th scope="row">Default</th><td>';
    echo '<label><input type="checkbox" id="patlis_meal_plan_is_default" name="patlis_meal_plan_is_default" value="1" ' . checked($is_default, 1, false) . '> Pre-selected in the booking form.</label>';
    echo '</td></tr>';

    echo '<tr><th scope="row"><label for="patlis_meal_plan_description">Description</label></th><td>';
    echo '<textarea id="patlis_meal_plan_description" name="patlis_meal_plan_description" rows="4" class="large-text">' . esc_textarea($desc) . '</textarea>';
    echo '<p class="description">Short text shown below the booking form (e.g. "Buffet breakfast, 07:00-10:30").</p>';
    echo '</td></tr>';

    echo '</table>';
}

add_action('save_post_patlis_meal_plan', function ($post_id) {
    if (!isset($_POST['patlis_acc_meal_plan_fields_nonce']) || !wp_verify_nonce($_POST['patlis_acc_meal_plan_fields_nonce'], 'patlis_acc_meal_plan_fields_save')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $price_a = max(0.0, (float) ($_POST['patlis_meal_plan_price_adult'] ?? 0));
    $is_default = !empty($_POST['patlis_meal_plan_is_default']) ? 1 : 0;
    $description = sanitize_textarea_field((string) ($_POST['patlis_meal_plan_description'] ?? ''));

    if ($is_default) {
        $ids_args = [
            'post_type'      => 'patlis_meal_plan',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'post__not_in'   => [(int) $post_id],
            'no_found_rows'  => true,
        ];

        if (function_exists('pll_get_post_language')) {
            $lang = pll_get_post_language((int) $post_id, 'slug');
            if (is_string($lang) && $lang !== '') {
                $ids_args['lang'] = $lang;
            }
        }

        $other_ids = get_posts($ids_args);
        if (is_array($other_ids)) {
            foreach ($other_ids as $other_id) {
                update_post_meta((int) $other_id, PATLIS_MEAL_PLAN_META_IS_DEFAULT, 0);
            }
        }
    }

    update_post_meta((int) $post_id, PATLIS_MEAL_PLAN_META_PRICE_ADULT, $price_a);
    update_post_meta((int) $post_id, PATLIS_MEAL_PLAN_META_IS_DEFAULT, $is_default);
    update_post_meta((int) $post_id, PATLIS_MEAL_PLAN_META_DESCRIPTION, $description);

    if (function_exists('patlis_acc_rooms_list_cache_clear')) {
        patlis_acc_rooms_list_cache_clear();
    }
}, 10, 1);

add_filter('manage_patlis_meal_plan_posts_columns', function ($columns) {
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'title') {
            $new['meal_plan_price'] = 'Extra price / night';
            $new['meal_plan_default'] = 'Default';
        }
    }
    return $new;
});

add_action('manage_patlis_meal_plan_posts_custom_column', function ($column, $post_id) {
    if ($column === 'meal_plan_price') {
        $price = (float) get_post_meta((int) $post_id, PATLIS_MEAL_PLAN_META_PRICE_ADULT, true);
        echo $price > 0 ? '+ €' . esc_html(number_format($price, 2)) : '0';
        return;
    }

    if ($column === 'meal_plan_default') {
        $is_default = (int) get_post_meta((int) $post_id, PATLIS_MEAL_PLAN_META_IS_DEFAULT, true);
        echo $is_default ? '<span style="color:#2271b1;font-weight:600;">&#9679; Default</span>' : '—';
    }
}, 10, 2);

add_filter('manage_edit-patlis_meal_plan_sortable_columns', function ($columns) {
    $columns['meal_plan_price'] = 'meal_plan_price';
    $columns['meal_plan_default'] = 'meal_plan_default';
    return $columns;
});

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !($query instanceof WP_Query) || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'patlis_meal_plan') return;

    $orderby = $query->get('orderby');
    if ($orderby === 'meal_plan_price') {
        $query->set('meta_key', PATLIS_MEAL_PLAN_META_PRICE_ADULT);
        $query->set('orderby', 'meta_value_num');
        return;
    }

    if ($orderby === 'meal_plan_default') {
        $query->set('meta_key', PATLIS_MEAL_PLAN_META_IS_DEFAULT);
        $query->set('orderby', 'meta_value_num');
    }
});
