<?php
if (!defined('ABSPATH')) exit;

/**
 * Menu Item post meta (Option B – items = pmi_*)
 *
 *  pmi_itemnr, pmi_show,
 *  pmi_sort (optional int),
 *  pmi_price, pmi_price2, pmi_price3,
 *  pmi_size1, pmi_size2, pmi_size3,
 *  pmi_allergies,
 *  pmi_description,
 *  pmi_vegan, pmi_vegetarian, pmi_carousel
 */

/**
 * Safety: ensure editor is not shown even if supports had it earlier.
 */
add_action('init', function () {
    remove_post_type_support('menu_item', 'editor');
}, 20);

/* ------------------------------------------------------------
 * Meta box
 * ------------------------------------------------------------ */
add_action('add_meta_boxes', 'patlis_menu_items_add_metaboxes');
function patlis_menu_items_add_metaboxes(): void
{
    add_meta_box(
        'patlis_menu_item_details',
        'Menu Item Details',
        'patlis_menu_items_metabox_render',
        'menu_item',
        'normal',
        'high'
    );
}

function patlis_menu_items_metabox_render(WP_Post $post): void
{
    wp_nonce_field('patlis_menu_item_save', 'patlis_menu_item_nonce');

    $itemnr     = (string) get_post_meta($post->ID, 'pmi_itemnr', true);

    $show       = get_post_meta($post->ID, 'pmi_show', true);
    $show       = ($show === '' ? '1' : (string)$show); // default show

    $sort       = (string) get_post_meta($post->ID, 'pmi_sort', true);

    $price      = (string) get_post_meta($post->ID, 'pmi_price', true);
    $price2     = (string) get_post_meta($post->ID, 'pmi_price2', true);
    $price3     = (string) get_post_meta($post->ID, 'pmi_price3', true);

    $size1      = (string) get_post_meta($post->ID, 'pmi_size1', true);
    $size2      = (string) get_post_meta($post->ID, 'pmi_size2', true);
    $size3      = (string) get_post_meta($post->ID, 'pmi_size3', true);

    $allergies  = (string) get_post_meta($post->ID, 'pmi_allergies', true);
    $desc       = (string) get_post_meta($post->ID, 'pmi_description', true);

    $vegan      = get_post_meta($post->ID, 'pmi_vegan', true) === '1';
    $vegetarian = get_post_meta($post->ID, 'pmi_vegetarian', true) === '1';
    $carousel   = get_post_meta($post->ID, 'pmi_carousel', true) === '1';

    ?>
    <style>
        .pm-grid { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:14px; max-width: 980px; }
        .pm-field label { font-weight:600; display:block; margin-bottom:6px; }
        .pm-field input[type="text"],
        .pm-field input[type="number"],
        .pm-field textarea { width:100%; }
        .pm-row { margin-top: 14px; max-width: 980px; }
        .pm-checks { display:flex; gap:18px; align-items:center; margin-top: 12px; flex-wrap: wrap; }
        .pm-checks label { font-weight:600; }
        .pm-note { color:#666; font-size:12px; }
    </style>

    <div class="pm-grid">
        <div class="pm-field">
            <label for="pmi_itemnr">Item Nr</label>
            <input type="text" id="pmi_itemnr" name="pmi_itemnr" value="<?php echo esc_attr($itemnr); ?>">
            <div class="pm-note">Μπορεί να έχει και γράμματα (π.χ. 12a).</div>
        </div>

        <div class="pm-field">
            <label for="pmi_sort">Display order (optional)</label>
            <input type="number" step="1" id="pmi_sort" name="pmi_sort" value="<?php echo esc_attr($sort); ?>">
            <div class="pm-note">Αν είναι κενό, θα γίνει sort με Item Nr → Name.</div>
        </div>

        <div class="pm-field">
            <label for="pmi_show">Show</label>
            <label style="font-weight:normal;">
                <input type="checkbox" id="pmi_show" name="pmi_show" value="1" <?php checked($show, '1'); ?>>
                Visible
            </label>
        </div>

        <div class="pm-field">
            <label for="pmi_price">Price</label>
            <input type="number" step="0.01" id="pmi_price" name="pmi_price" value="<?php echo esc_attr($price); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_price2">Price 2</label>
            <input type="number" step="0.01" id="pmi_price2" name="pmi_price2" value="<?php echo esc_attr($price2); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_price3">Price 3</label>
            <input type="number" step="0.01" id="pmi_price3" name="pmi_price3" value="<?php echo esc_attr($price3); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_size1">Size 1</label>
            <input type="text" id="pmi_size1" name="pmi_size1" value="<?php echo esc_attr($size1); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_size2">Size 2</label>
            <input type="text" id="pmi_size2" name="pmi_size2" value="<?php echo esc_attr($size2); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_size3">Size 3</label>
            <input type="text" id="pmi_size3" name="pmi_size3" value="<?php echo esc_attr($size3); ?>">
        </div>
    </div>

    <div class="pm-row">
        <div class="pm-field">
            <label for="pmi_allergies">Allergies</label>
            <input type="text" id="pmi_allergies" name="pmi_allergies" value="<?php echo esc_attr($allergies); ?>">
        </div>

        <div class="pm-field" style="margin-top:14px;">
            <label for="pmi_description">Description</label>
            <textarea id="pmi_description" name="pmi_description" rows="4"><?php echo esc_textarea($desc); ?></textarea>
        </div>

        <div class="pm-checks">
            <label><input type="checkbox" name="pmi_vegetarian" value="1" <?php checked($vegetarian); ?>> Vegetarian</label>
            <label><input type="checkbox" name="pmi_vegan" value="1" <?php checked($vegan); ?>> Vegan</label>
            <label><input type="checkbox" name="pmi_carousel" value="1" <?php checked($carousel); ?>> Add to carousel</label>
        </div>
    </div>
    <?php
}

/* ------------------------------------------------------------
 * Save meta
 * ------------------------------------------------------------ */
add_action('save_post_menu_item', 'patlis_menu_items_save', 10, 2);
function patlis_menu_items_save(int $post_id, WP_Post $post): void
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (!isset($_POST['patlis_menu_item_nonce']) || !wp_verify_nonce((string)$_POST['patlis_menu_item_nonce'], 'patlis_menu_item_save')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    update_post_meta($post_id, 'pmi_show', isset($_POST['pmi_show']) ? '1' : '0');

    $itemnr = isset($_POST['pmi_itemnr']) ? sanitize_text_field((string)$_POST['pmi_itemnr']) : '';
    update_post_meta($post_id, 'pmi_itemnr', $itemnr);

    // pmi_sort optional
    $sort_raw = isset($_POST['pmi_sort']) ? trim((string)$_POST['pmi_sort']) : '';
    if ($sort_raw === '') {
        delete_post_meta($post_id, 'pmi_sort');
    } else {
        update_post_meta($post_id, 'pmi_sort', (string)intval($sort_raw));
    }

    update_post_meta($post_id, 'pmi_price',  patlis_menu_sanitize_price($_POST['pmi_price']  ?? ''));
    update_post_meta($post_id, 'pmi_price2', patlis_menu_sanitize_price($_POST['pmi_price2'] ?? ''));
    update_post_meta($post_id, 'pmi_price3', patlis_menu_sanitize_price($_POST['pmi_price3'] ?? ''));

    update_post_meta($post_id, 'pmi_size1', sanitize_text_field((string)($_POST['pmi_size1'] ?? '')));
    update_post_meta($post_id, 'pmi_size2', sanitize_text_field((string)($_POST['pmi_size2'] ?? '')));
    update_post_meta($post_id, 'pmi_size3', sanitize_text_field((string)($_POST['pmi_size3'] ?? '')));

    update_post_meta($post_id, 'pmi_allergies', sanitize_text_field((string)($_POST['pmi_allergies'] ?? '')));

    update_post_meta($post_id, 'pmi_description', sanitize_textarea_field((string)($_POST['pmi_description'] ?? '')));

    update_post_meta($post_id, 'pmi_vegetarian', isset($_POST['pmi_vegetarian']) ? '1' : '0');
    update_post_meta($post_id, 'pmi_vegan',      isset($_POST['pmi_vegan']) ? '1' : '0');

    update_post_meta($post_id, 'pmi_carousel',   isset($_POST['pmi_carousel']) ? '1' : '0');
}

function patlis_menu_sanitize_price($v): string
{
    $v = is_string($v) ? trim($v) : '';
    if ($v === '') return '';
    $v = str_replace(',', '.', $v);
    $v = preg_replace('/[^0-9.]/', '', $v);
    if ($v === '' || $v === '.') return '';
    return $v;
}

/* ------------------------------------------------------------
 * Admin columns for Menu Items list
 * ------------------------------------------------------------ */
add_filter('manage_menu_item_posts_columns', function ($cols) {
    $new = [];
    foreach ($cols as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['pmi_sort']     = 'Sort';
            $new['pmi_itemnr']   = 'Item Nr';
            $new['pmi_size1']      = 'Size 1';
            $new['pmi_price']    = 'Price';
            $new['pmi_carousel'] = 'Carousel';
            $new['pmi_vegetarian'] = 'Vegetarian';
            $new['pmi_vegan']      = 'Vegan';
            $new['pmi_show']     = 'Show';
        }
    }
    return $new;
});

/* ------------------------------------------------------------
 * Admin columns for sorting
 * ------------------------------------------------------------ */
add_filter('manage_edit-menu_item_sortable_columns', function ($cols) {
    $cols['pmi_sort']     = 'pmi_sort';
    $cols['pmi_itemnr']   = 'pmi_itemnr';
    $cols['pmi_price']    = 'pmi_price';
    $cols['pmi_size1']    = 'pmi_size1';
    $cols['pmi_show']     = 'pmi_show';
    $cols['pmi_carousel'] = 'pmi_carousel';
    $cols['pmi_vegan']    = 'pmi_vegan';
    $cols['pmi_vegetarian']= 'pmi_vegetarian';

    return $cols;
});

add_action('pre_get_posts', function (WP_Query $q) {

    if (!is_admin()) return;
    if (!$q->is_main_query()) return;

    // Μόνο στη λίστα του CPT
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'edit-menu_item') return;

    $orderby = (string) $q->get('orderby');
    if ($orderby === '') return;

    $numeric = ['pmi_sort', 'pmi_price', 'pmi_price2', 'pmi_price3'];
    $bool    = ['pmi_show', 'pmi_carousel', 'pmi_vegan', 'pmi_vegetarian'];

    // orderby θα είναι το ίδιο string που βάλαμε στο sortable_columns
    $allowed = array_merge($numeric, $bool, [
        'pmi_itemnr', 'pmi_size1', 'pmi_size2', 'pmi_size3', 'pmi_allergies'
    ]);

    if (!in_array($orderby, $allowed, true)) return;

    $q->set('meta_key', $orderby);

    if (in_array($orderby, $numeric, true)) {
        $q->set('orderby', 'meta_value_num');   // σωστό για αριθμούς
    } else {
        $q->set('orderby', 'meta_value');       // σωστό για text / yes-no
    }

});
/* ------------------------------------------------------------
 * Admin columns for sorting END
 * ------------------------------------------------------------ */

add_action('manage_menu_item_posts_custom_column', function ($col, $post_id) {
    if ($col === 'pmi_sort') {
        echo esc_html((string)get_post_meta($post_id, 'pmi_sort', true));
        return;
    }
    if ($col === 'pmi_itemnr') {
        echo esc_html((string)get_post_meta($post_id, 'pmi_itemnr', true));
        return;
    }
    if ($col === 'pmi_price') {
        echo esc_html((string)get_post_meta($post_id, 'pmi_price', true));
        return;
    }
    if ($col === 'pmi_carousel') {
        echo get_post_meta($post_id, 'pmi_carousel', true) === '1' ? 'Yes' : 'No';
        return;
    }
    if ($col === 'pmi_show') {
        echo get_post_meta($post_id, 'pmi_show', true) === '0' ? 'No' : 'Yes';
        return;
    }
}, 10, 2);

/* ------------------------------------------------------------
 * Ordering helper: pmi_sort (set ones first) -> itemnr -> title
 *
 * Apply ONLY when query var 'patlis_menu_order' is set.
 * ------------------------------------------------------------ */
add_action('pre_get_posts', function (WP_Query $q) {
    $flag = $q->get('patlis_menu_order');
    if (!$flag) return;

    if ($q->get('post_type') !== 'menu_item') return;

    $q->set('orderby', 'none'); // we'll override with SQL
});

add_filter('posts_join', function ($join, WP_Query $q) {
    if (!$q->get('patlis_menu_order')) return $join;
    if ($q->get('post_type') !== 'menu_item') return $join;

    global $wpdb;

    $join .= " LEFT JOIN {$wpdb->postmeta} pmi_sort_meta ON (pmi_sort_meta.post_id = {$wpdb->posts}.ID AND pmi_sort_meta.meta_key = 'pmi_sort') ";
    $join .= " LEFT JOIN {$wpdb->postmeta} pmi_itemnr_meta ON (pmi_itemnr_meta.post_id = {$wpdb->posts}.ID AND pmi_itemnr_meta.meta_key = 'pmi_itemnr') ";

    return $join;
}, 10, 2);

add_filter('posts_orderby', function ($orderby, WP_Query $q) {
    if (!$q->get('patlis_menu_order')) return $orderby;
    if ($q->get('post_type') !== 'menu_item') return $orderby;

    global $wpdb;

    $orderby = "
        (pmi_sort_meta.meta_value IS NULL OR pmi_sort_meta.meta_value = '') ASC,
        CAST(pmi_sort_meta.meta_value AS UNSIGNED) ASC,
        pmi_itemnr_meta.meta_value ASC,
        {$wpdb->posts}.post_title ASC
    ";

    return $orderby;
}, 10, 2);

add_filter('posts_distinct', function ($distinct, WP_Query $q) {
    if (!$q->get('patlis_menu_order')) return $distinct;
    if ($q->get('post_type') !== 'menu_item') return $distinct;
    return 'DISTINCT';
}, 10, 2);


/* prosthethei ena button sto header , back to all menu   */ 

add_action('admin_head', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen) return;

    // Edit screen για το CPT menu_item: τόσο edit όσο και add-new
    if (!in_array($screen->base, ['post', 'post-new'], true)) return;
    if ($screen->post_type !== 'menu_item') return;

    $back_url = admin_url('edit.php?post_type=menu_item');
    $label = '← Back to Menu Items';

    ?>
    <script>
    (function () {
        function addBackBtn() {
            var h1 = document.querySelector('.wrap h1.wp-heading-inline');
            if (!h1) return;

            // αν υπάρχει ήδη, μην το ξαναβάζεις
            if (document.getElementById('patlis-back-to-menu-items')) return;

            var a = document.createElement('a');
            a.id = 'patlis-back-to-menu-items';
            a.className = 'page-title-action';
            a.href = <?php echo json_encode($back_url); ?>;
            a.textContent = <?php echo json_encode($label); ?>;

            // βάλτο αμέσως μετά το H1, πριν/μετά το Add New (όπως βολεύει)
            var addNew = document.querySelector('.wrap .page-title-action');
            if (addNew) {
                addNew.insertAdjacentElement('afterend', a);
            } else {
                h1.insertAdjacentElement('afterend', a);
            }
        }

        // Σε μερικά admin screens το DOM “χτίζεται” λίγο πιο μετά
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', addBackBtn);
        } else {
            addBackBtn();
        }
    })();
    </script>
    <?php
});