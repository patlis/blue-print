<?php
if (!defined('ABSPATH')) exit;

/* ============================================================
 * Taxonomy: room_meal_plan
 * ============================================================ */
add_action('init', function () {

    if (function_exists('patlis_accommodation_is_enabled_for_version') && !patlis_accommodation_is_enabled_for_version()) {
        return;
    }

    register_taxonomy('room_meal_plan', ['patlis_room'], [
        'labels' => [
            'name'          => __('Meal Plans', 'patlis-accommodation'),
            'singular_name' => __('Meal Plan', 'patlis-accommodation'),
            'search_items'  => __('Search meal plans', 'patlis-accommodation'),
            'all_items'     => __('All meal plans', 'patlis-accommodation'),
            'edit_item'     => __('Edit meal plan', 'patlis-accommodation'),
            'update_item'   => __('Update meal plan', 'patlis-accommodation'),
            'add_new_item'  => __('Add new meal plan', 'patlis-accommodation'),
            'new_item_name' => __('New meal plan name', 'patlis-accommodation'),
            'menu_name'     => __('Meal Plans', 'patlis-accommodation'),
        ],
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'hierarchical'      => true,
        'rewrite'           => false,
        'meta_box_cb'       => 'post_categories_meta_box',
    ]);

}, 5);

/* ============================================================
 * Helper: get meal plans assigned to a room, sorted by order.
 * Used by REST endpoint (booking-form.php) and Bricks tags.
 * ============================================================ */
function patlis_acc_get_room_meal_plans(int $room_id): array
{
    $terms = get_the_terms($room_id, 'room_meal_plan');
    if (is_wp_error($terms) || empty($terms)) return [];

    $plans = [];
    foreach ($terms as $t) {
        $label = (string) get_term_meta($t->term_id, 'patlis_meal_plan_label', true);
        $plans[] = [
            'id'          => (int)    $t->term_id,
            'name'        => (string) $t->name,
            'label'       => $label !== '' ? $label : (string) $t->name,
            'price_adult' => (float)  get_term_meta($t->term_id, 'patlis_meal_plan_price_adult', true),
        ];
    }

    usort($plans, fn($a, $b) => $a['price_adult'] <=> $b['price_adult']);

    return $plans;
}
