<?php
if (!defined('ABSPATH')) exit;

const PATLIS_MEAL_PLAN_META_LABEL       = 'patlis_meal_plan_label';
const PATLIS_MEAL_PLAN_META_PRICE_ADULT = 'patlis_meal_plan_price_adult';


/* ============================================================
 * JS reload after AJAX add / delete (same pattern as amenities)
 * ============================================================ */
add_action('admin_footer-edit-tags.php', function () {
    if (!function_exists('get_current_screen')) return;
    $screen = get_current_screen();
    if (!$screen || ($screen->taxonomy ?? '') !== 'room_meal_plan') return;
    ?>
    <style>
    .term-description-wrap { display: none !important; }
    </style>
    <script>
    jQuery(document).ajaxComplete(function (event, xhr, settings) {
        if (!settings || !settings.data) return;
        if (typeof settings.data === 'string' &&
            (settings.data.includes('action=add-tag') || settings.data.includes('action=delete-tag')) &&
            (settings.data.includes('taxonomy=room_meal_plan') || settings.data.includes('screen=edit-room_meal_plan'))) {
            setTimeout(function () { window.location.reload(); }, 500);
        }
    });
    </script>
    <?php
});

/* ============================================================
 * Add form fields (new term)
 * ============================================================ */
add_action('room_meal_plan_add_form_fields', function () {
    ?>
    <div class="form-field">
        <label for="patlis_meal_plan_label">Frontend label</label>
        <input type="text" id="patlis_meal_plan_label" name="patlis_meal_plan_label" value="">
        <p class="description">Αυτό βλέπει ο επισκέπτης στο booking form (π.χ. "Half Board"). Αν αφεθεί κενό, χρησιμοποιείται το όνομα.</p>
    </div>

    <div class="form-field">
        <label for="patlis_meal_plan_price_adult">Extra price / adult / night (€)</label>
        <input type="number" id="patlis_meal_plan_price_adult" name="patlis_meal_plan_price_adult" value="0" min="0" step="0.01">
        <p class="description">0 = included in room rate (e.g. Room Only).</p>
    </div>


    <?php
});

/* ============================================================
 * Edit form fields (existing term)
 * ============================================================ */
add_action('room_meal_plan_edit_form_fields', function ($term) {
    $label   = (string) get_term_meta($term->term_id, PATLIS_MEAL_PLAN_META_LABEL,       true);
    $price_a = get_term_meta($term->term_id, PATLIS_MEAL_PLAN_META_PRICE_ADULT, true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="patlis_meal_plan_label">Frontend label</label></th>
        <td>
            <input type="text" id="patlis_meal_plan_label" name="patlis_meal_plan_label"
                   value="<?php echo esc_attr($label); ?>">
            <p class="description">Αυτό βλέπει ο επισκέπτης στο booking form. Αν αφεθεί κενό, χρησιμοποιείται το όνομα.</p>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row"><label for="patlis_meal_plan_price_adult">Extra price / adult / night (€)</label></th>
        <td>
            <input type="number" id="patlis_meal_plan_price_adult" name="patlis_meal_plan_price_adult"
                   value="<?php echo esc_attr($price_a !== '' ? $price_a : '0'); ?>" min="0" step="0.01">
            <p class="description">0 = included in room rate.</p>
        </td>
    </tr>


    <?php
}, 10, 1);

/* ============================================================
 * Save term meta on create / edit
 * ============================================================ */
$patlis_save_meal_plan_meta = function ($term_id) {
    $label   = sanitize_text_field((string) ($_POST['patlis_meal_plan_label']       ?? ''));
    $price_a = max(0.0, (float) ($_POST['patlis_meal_plan_price_adult'] ?? 0));

    update_term_meta($term_id, PATLIS_MEAL_PLAN_META_LABEL,       $label);
    update_term_meta($term_id, PATLIS_MEAL_PLAN_META_PRICE_ADULT, $price_a);

    // Clear rooms REST cache so meal plan data is up to date
    delete_transient('patlis_acc_rooms_list_v2');
};

add_action('created_room_meal_plan', $patlis_save_meal_plan_meta);
add_action('edited_room_meal_plan',  $patlis_save_meal_plan_meta);


