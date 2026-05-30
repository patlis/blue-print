<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manual list of meta fields that must stay synchronized across post translations.
 * 
 * Works with BOTH ACF fields AND regular post meta fields.
 * - ACF fields will also be hidden in non-default language edits (prevents accidental changes).
 * - Regular meta fields sync silently without UI modifications.
 * 
 * Add or remove keys here. Works with Polylang Free.
 */
function patlis_synced_meta_fields(): array
{
    return [
        //events
        'events_date_start','events_time_start',
        'events_date_end','events_time_end',
        'events_gallery_ids',

        //Offers & Packages
        'packages_valid_from','packages_valid_until',
        'packages_discount','package_order','package_enabled',
        'package_single_page','package_booking_url',
        'package_linked_rooms', // ???????

        //Services
        'create_service_page','service_sticky',
        'service_order','service_show',
        'services_gallery_ids',

        //timeline
        'timeline_sort','timeline_image',

        // menu items (regular post meta - not ACF)
        'pmi_itemnr','pmi_sort','pmi_show',
        'pmi_price','pmi_price2','pmi_price3',
        'pmi_allergies','pmi_carousel','pmi_vegetarian','pmi_vegan',

        // menu categories
        'pmc_sort','pmc_show','pmc_image_id',
        'pmc_day0a','pmc_day0b',
        'pmc_day1a','pmc_day1b',
        'pmc_day2a','pmc_day2b',
        'pmc_day3a','pmc_day3b',
        'pmc_day4a','pmc_day4b',
        'pmc_day5a','pmc_day5b',
        'pmc_day6a','pmc_day6b',

        // rooms
        'room_item_nr','room_beds','room_persons','room_count',
        'room_img_360_url','room_sticky','room_size_m2','room_video_url',

        // (term meta) amenities & facilities & services
        'patlis_is_highlight','patlis_order','amenity_show','patlis_icon',

        // rate periods       
        'hotel_rate_period_start_day','hotel_rate_period_start_month','hotel_rate_period_end_day',
        'hotel_rate_period_end_month','hotel_rate_period_active','hotel_rate_period_priority','hotel_rate_period_order', 
        
        //room rates
        'patlis_acc_period_id','patlis_acc_price','patlis_acc_price_type','patlis_acc_min_nights','patlis_acc_active',
        'patlis_acc_room_ids','patlis_acc_price_surfix',

    ];
}

/**
 * Return true when current admin edit context is in a non-default language.
 * Supports both posts and terms (taxonomy edit screens).
 */
function patlis_is_non_default_language_edit_context(): bool
{
    if (!is_admin()) {
        return false;
    }

    if (!function_exists('pll_default_language')) {
        return false;
    }

    $default_lang = pll_default_language();
    if (!is_string($default_lang) || $default_lang === '') {
        return false;
    }

    // 1) Post edit context (works in normal screens and ACF lifecycle hooks).
    if (function_exists('pll_get_post_language')) {
        $post_id = 0;

        if (isset($_GET['post'])) {
            $post_id = (int) $_GET['post'];
        } elseif (isset($_POST['post_ID'])) {
            $post_id = (int) $_POST['post_ID'];
        } elseif (isset($_POST['post_id'])) {
            $post_id = (int) $_POST['post_id'];
        } elseif (isset($GLOBALS['post']) && $GLOBALS['post'] instanceof WP_Post) {
            $post_id = (int) $GLOBALS['post']->ID;
        }

        if ($post_id > 0) {
            $post_lang = pll_get_post_language($post_id);
            if (is_string($post_lang) && $post_lang !== '') {
                return $post_lang !== $default_lang;
            }
        }
    }

    // 2) Term edit context (taxonomy screens).
    if (function_exists('pll_get_term_language')) {
        $term_id = 0;

        if (isset($_GET['tag_ID'])) {
            $term_id = (int) $_GET['tag_ID'];
        } elseif (isset($_POST['tag_ID'])) {
            $term_id = (int) $_POST['tag_ID'];
        } elseif (isset($_POST['term_id'])) {
            $term_id = (int) $_POST['term_id'];
        }

        if ($term_id > 0) {
            $term_lang = pll_get_term_language($term_id);
            if (is_string($term_lang) && $term_lang !== '') {
                return $term_lang !== $default_lang;
            }
        }
    }

    // 3) Last-resort fallback from current language in admin.
    if (isset($_GET['new_lang'])) {
        $requested_lang = sanitize_key((string) $_GET['new_lang']);
        if ($requested_lang !== '') {
            return $requested_lang !== $default_lang;
        }
    }

    if (isset($_GET['lang'])) {
        $requested_lang = sanitize_key((string) $_GET['lang']);
        if ($requested_lang !== '') {
            return $requested_lang !== $default_lang;
        }
    }

    if (isset($_POST['new_lang'])) {
        $requested_lang = sanitize_key((string) $_POST['new_lang']);
        if ($requested_lang !== '') {
            return $requested_lang !== $default_lang;
        }
    }

    if (isset($_POST['lang'])) {
        $requested_lang = sanitize_key((string) $_POST['lang']);
        if ($requested_lang !== '') {
            return $requested_lang !== $default_lang;
        }
    }

    // 4) Last-resort fallback from current language in admin.
    if (function_exists('pll_current_language')) {
        $current_lang = pll_current_language('slug');
        if (is_string($current_lang) && $current_lang !== '') {
            return $current_lang !== $default_lang;
        }
    }

    return false;
}

/**
 * Sync selected meta fields from the saved translation to all sibling translations.
 * Works with Polylang Free (no Pro features required).
 */
add_action('save_post', function ($post_id, $post) {
    if (!($post instanceof WP_Post)) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    if (!function_exists('pll_get_post_translations')) {
        return;
    }

    $fields = patlis_synced_meta_fields();
    if (empty($fields)) {
        return;
    }

    $translations = pll_get_post_translations((int) $post_id);
    if (!is_array($translations) || count($translations) < 2) {
        return;
    }

    foreach ($fields as $meta_key) {
        $meta_key = is_string($meta_key) ? trim($meta_key) : '';
        if ($meta_key === '') {
            continue;
        }

        $exists = metadata_exists('post', (int) $post_id, $meta_key);
        $value  = get_post_meta((int) $post_id, $meta_key, true);

        foreach ($translations as $translated_id) {
            $translated_id = (int) $translated_id;
            if ($translated_id <= 0 || $translated_id === (int) $post_id) {
                continue;
            }

            if (!$exists) {
                delete_post_meta($translated_id, $meta_key);
                continue;
            }

            update_post_meta($translated_id, $meta_key, $value);
        }
    }
}, 100, 2);

/**
 * Sync selected term meta fields from the saved translation to sibling translations.
 * Needed for taxonomy terms (e.g. menu_section categories).
 */
add_action('edited_term', function ($term_id, $tt_id, $taxonomy) {
    if (!is_admin()) {
        return;
    }

    if (!function_exists('pll_get_term_translations')) {
        return;
    }

    $fields = patlis_synced_meta_fields();
    if (empty($fields) || !is_array($fields)) {
        return;
    }

    $translations = pll_get_term_translations((int) $term_id);
    if (!is_array($translations) || count($translations) < 2) {
        return;
    }

    foreach ($fields as $meta_key) {
        $meta_key = is_string($meta_key) ? trim($meta_key) : '';
        if ($meta_key === '') {
            continue;
        }

        $exists = metadata_exists('term', (int) $term_id, $meta_key);
        $value  = get_term_meta((int) $term_id, $meta_key, true);

        foreach ($translations as $translated_term_id) {
            $translated_term_id = (int) $translated_term_id;
            if ($translated_term_id <= 0 || $translated_term_id === (int) $term_id) {
                continue;
            }

            if (!$exists) {
                delete_term_meta($translated_term_id, $meta_key);
                continue;
            }

            update_term_meta($translated_term_id, $meta_key, $value);
        }
    }
}, 100, 3);

/**
 * Mark synced fields for hiding via CSS class when not in default language.
 */
add_filter('acf/load_field', function ($field) {
    if (!is_admin()) {
        return $field;
    }

    if (!function_exists('pll_default_language')) {
        return $field;
    }

    $synced_fields = patlis_synced_meta_fields();
    if (empty($synced_fields) || !is_array($synced_fields)) {
        return $field;
    }

    $field_name = isset($field['name']) ? (string) $field['name'] : '';
    if ($field_name === '' || !in_array($field_name, $synced_fields, true)) {
        return $field;
    }

    if (patlis_is_non_default_language_edit_context()) {
        if (!isset($field['wrapper'])) {
            $field['wrapper'] = [];
        }
        if (!isset($field['wrapper']['class'])) {
            $field['wrapper']['class'] = '';
        }
        $field['wrapper']['class'] .= ' patlis-synced-field-hidden';
    }

    return $field;
}, 10, 1);

/**
 * Enqueue CSS and JavaScript to hide synced fields in non-default languages.
 * Works for BOTH ACF fields AND regular post meta input fields.
 */
add_action('admin_head', function () {
    if (!function_exists('pll_default_language')) {
        return;
    }

    if (!patlis_is_non_default_language_edit_context()) {
        return;
    }

    $synced_fields = patlis_synced_meta_fields();
    if (empty($synced_fields) || !is_array($synced_fields)) {
        return;
    }

    // JavaScript to hide regular meta fields (by input name attribute)
    // This handles non-ACF fields like pmi_vegetarian, pmi_vegan, etc.
    $fields_json = wp_json_encode($synced_fields);
    echo '<style>.patlis-synced-field-hidden{display:none !important;}</style>';
    echo "<script>
(function() {
    function hideFields() {
        var syncedFields = {$fields_json};

        function findElementsByFieldName(fieldName) {
            var selectors = [
                '[name=\"' + fieldName + '\"]',
                '[name=\"' + fieldName + '[]\"]',
                '[name^=\"' + fieldName + '[\"]'
            ];

            return document.querySelectorAll(selectors.join(','));
        }
        
        syncedFields.forEach(function(fieldName) {
            // Find standard inputs and array-style inputs (e.g. field[], field[key]).
            var elements = findElementsByFieldName(fieldName);
            elements.forEach(function(el) {
                // Find closest wrapper (could be .pm-field, tr, div, etc)
                var parent = el.closest('.pm-field') || el.closest('tr') || el.closest('.acf-field') || el.closest('.form-field');
                if (parent) {
                    parent.style.display = 'none';
                } else {
                    // If no parent found, hide the element itself and its siblings
                    el.style.display = 'none';
                    var label = document.querySelector('label[for=\"' + fieldName + '\"]');
                    if (label) label.style.display = 'none';
                }
            });
        });
    }
    
    // Run immediately and on document ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideFields);
    } else {
        hideFields();
    }

    // Also run on any dynamic content load (for admin JS events)
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('acf/append_field_group', hideFields);
        jQuery(document).on('acf/setup_fields', hideFields);
    }
})();
    </script>";
});
