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
        __('Menu item details', 'patlis-menu'),
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
            <label for="pmi_itemnr"><?php esc_html_e('Item No', 'patlis-menu'); ?></label>
            <input type="text" id="pmi_itemnr" name="pmi_itemnr" value="<?php echo esc_attr($itemnr); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_sort"><?php esc_html_e('Display order (optional)', 'patlis-menu'); ?></label>
            <input type="number" step="1" id="pmi_sort" name="pmi_sort" value="<?php echo esc_attr($sort); ?>">
            <div class="pm-note"><?php esc_html_e('If empty, items are sorted by item number, then by name.', 'patlis-menu'); ?></div>
        </div>

        <div class="pm-field">
            <label for="pmi_show"><?php esc_html_e('Show', 'patlis-menu'); ?></label>
            <label style="font-weight:normal;">
                <input type="checkbox" id="pmi_show" name="pmi_show" value="1" <?php checked($show, '1'); ?>>
                <?php esc_html_e('Enabled', 'patlis-menu'); ?>
            </label>
        </div>

        <div class="pm-field">
            <label for="pmi_price"><?php esc_html_e('Price 1', 'patlis-menu'); ?></label>
            <input type="number" step="0.01" id="pmi_price" name="pmi_price" value="<?php echo esc_attr($price); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_price2"><?php esc_html_e('Price 2', 'patlis-menu'); ?></label>
            <input type="number" step="0.01" id="pmi_price2" name="pmi_price2" value="<?php echo esc_attr($price2); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_price3"><?php esc_html_e('Price 3', 'patlis-menu'); ?></label>
            <input type="number" step="0.01" id="pmi_price3" name="pmi_price3" value="<?php echo esc_attr($price3); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_size1"><?php esc_html_e('Size 1', 'patlis-menu'); ?></label>
            <input type="text" id="pmi_size1" name="pmi_size1" value="<?php echo esc_attr($size1); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_size2"><?php esc_html_e('Size 2', 'patlis-menu'); ?></label>
            <input type="text" id="pmi_size2" name="pmi_size2" value="<?php echo esc_attr($size2); ?>">
        </div>

        <div class="pm-field">
            <label for="pmi_size3"><?php esc_html_e('Size 3', 'patlis-menu'); ?></label>
            <input type="text" id="pmi_size3" name="pmi_size3" value="<?php echo esc_attr($size3); ?>">
        </div>
    </div>

    <div class="pm-row">
        <div class="pm-field">
            <label for="pmi_allergies"><?php esc_html_e('Allergens', 'patlis-menu'); ?></label>
            <input type="text" id="pmi_allergies" name="pmi_allergies" value="<?php echo esc_attr($allergies); ?>">
        </div>

        <div class="pm-field" style="margin-top:14px;">
            <label for="pmi_description"><?php esc_html_e('Description', 'patlis-menu'); ?></label>
            <textarea id="pmi_description" name="pmi_description" rows="4"><?php echo esc_textarea($desc); ?></textarea>
        </div>

        <div class="pm-checks">
            <label><input type="checkbox" name="pmi_vegetarian" value="1" <?php checked($vegetarian); ?>> <?php esc_html_e('Vegetarian', 'patlis-menu'); ?></label>
            <label><input type="checkbox" name="pmi_vegan" value="1" <?php checked($vegan); ?>> <?php esc_html_e('Vegan', 'patlis-menu'); ?></label>
            <label><input type="checkbox" name="pmi_carousel" value="1" <?php checked($carousel); ?>> <?php esc_html_e('Add to carousel', 'patlis-menu'); ?></label>
        </div>
    </div>
    <?php
}

/* ------------------------------------------------------------
 * Save meta
 * ------------------------------------------------------------ */
add_action('save_post_menu_item', 'patlis_menu_items_save', 10, 2);
function patlis_menu_sanitize_basic_description_html(string $html): string
{
    $allowed = [
        'p' => [],
        'b' => [],
        'strong' => [],
        'ul' => [],
        'li' => [],
        'br' => [],
    ];

    return wp_kses($html, $allowed);
}

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

    update_post_meta(
        $post_id,
        'pmi_description',
        patlis_menu_sanitize_basic_description_html((string)($_POST['pmi_description'] ?? ''))
    );

    update_post_meta($post_id, 'pmi_vegetarian', isset($_POST['pmi_vegetarian']) ? '1' : '0');
    update_post_meta($post_id, 'pmi_vegan',      isset($_POST['pmi_vegan']) ? '1' : '0');

    update_post_meta($post_id, 'pmi_carousel',   isset($_POST['pmi_carousel']) ? '1' : '0');
}

/* ------------------------------------------------------------
 * Admin list filter: Menu Category (menu_section)
 * ------------------------------------------------------------ */
add_action('restrict_manage_posts', function (): void {
    global $typenow;

    if ($typenow !== 'menu_item') {
        return;
    }

    $taxonomy = 'menu_section';
    if (!taxonomy_exists($taxonomy)) {
        return;
    }

    $selected = isset($_GET[$taxonomy]) ? absint($_GET[$taxonomy]) : 0;

    wp_dropdown_categories([
        'show_option_all' => __('All categories', 'patlis-menu'),
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'orderby'         => 'name',
        'hierarchical'    => true,
        'hide_empty'      => false,
        'selected'        => $selected,
        'value_field'     => 'term_id',
    ]);
    $show_filter = isset($_GET['pmi_show_filter']) ? sanitize_key((string) $_GET['pmi_show_filter']) : '';
    $carousel_filter = isset($_GET['pmi_carousel_filter']) ? sanitize_key((string) $_GET['pmi_carousel_filter']) : '';
    ?>
    <select name="pmi_show_filter">
        <option value=""><?php esc_html_e('Enabled', 'patlis-menu'); ?></option>
        <option value="enabled" <?php selected($show_filter, 'enabled'); ?>><?php esc_html_e('Yes', 'patlis-menu'); ?></option>
        <option value="disabled" <?php selected($show_filter, 'disabled'); ?>><?php esc_html_e('No', 'patlis-menu'); ?></option>
    </select>
    <select name="pmi_carousel_filter">
        <option value=""><?php esc_html_e('Carousel', 'patlis-menu'); ?></option>
        <option value="included" <?php selected($carousel_filter, 'included'); ?>><?php esc_html_e('Yes', 'patlis-menu'); ?></option>
        <option value="excluded" <?php selected($carousel_filter, 'excluded'); ?>><?php esc_html_e('No', 'patlis-menu'); ?></option>
    </select>
    <?php
}, 10, 0);

add_action('pre_get_posts', function (WP_Query $q): void {
    if (!is_admin() || !$q->is_main_query()) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'edit-menu_item') {
        return;
    }

    $term_id = isset($_GET['menu_section']) ? absint($_GET['menu_section']) : 0;
    if ($term_id > 0) {
        $q->set('tax_query', [
            [
                'taxonomy' => 'menu_section',
                'field'    => 'term_id',
                'terms'    => [$term_id],
            ],
        ]);
    }
    $show_filter = isset($_GET['pmi_show_filter']) ? sanitize_key((string) $_GET['pmi_show_filter']) : '';
    $carousel_filter = isset($_GET['pmi_carousel_filter']) ? sanitize_key((string) $_GET['pmi_carousel_filter']) : '';
    $meta_query = [];

    if ($show_filter === 'enabled') {
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => 'pmi_show',
                'value'   => '1',
                'compare' => '=',
            ],
            [
                'key'     => 'pmi_show',
                'compare' => 'NOT EXISTS',
            ],
        ];
    } elseif ($show_filter === 'disabled') {
        $meta_query[] = [
            'key'     => 'pmi_show',
            'value'   => '0',
            'compare' => '=',
        ];
    }

    if ($carousel_filter === 'included') {
        $meta_query[] = [
            'key'     => 'pmi_carousel',
            'value'   => '1',
            'compare' => '=',
        ];
    } elseif ($carousel_filter === 'excluded') {
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => 'pmi_carousel',
                'value'   => '0',
                'compare' => '=',
            ],
            [
                'key'     => 'pmi_carousel',
                'compare' => 'NOT EXISTS',
            ],
        ];
    }

    if ($meta_query) {
        $q->set('meta_query', $meta_query);
    }
});

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
    unset($cols['date']);

    $new = [];
    foreach ($cols as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['pmi_sort']       = __('Display order', 'patlis-menu');
            $new['pmi_itemnr']     = __('Item No', 'patlis-menu');
            $new['pmi_price']      = __('Price 1', 'patlis-menu');
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

/* ------------------------------------------------------------
 * Admin search: include Item Nr (pmi_itemnr)
 * ------------------------------------------------------------ */
add_filter('posts_search', function ($search, WP_Query $q) {
    if (!is_admin()) return $search;
    if (!$q->is_main_query()) return $search;
    if (!$q->is_search()) return $search;
    if ($q->get('post_type') !== 'menu_item') return $search;

    $term = trim((string) $q->get('s'));
    if ($term === '') return $search;

    global $wpdb;

    $meta_condition = $wpdb->prepare(
        "EXISTS (
            SELECT 1
            FROM {$wpdb->postmeta} pmi_itemnr_search
            WHERE pmi_itemnr_search.post_id = {$wpdb->posts}.ID
              AND pmi_itemnr_search.meta_key = %s
              AND pmi_itemnr_search.meta_value LIKE %s
        )",
        'pmi_itemnr',
        '%' . $wpdb->esc_like($term) . '%'
    );

    // Keep default WP text search and extend it with Item Nr matches.
    if ($search !== '') {
        return preg_replace('/\)\s*$/', " OR {$meta_condition})", $search, 1) ?: $search;
    }

    return " AND ({$meta_condition}) ";
}, 10, 2);


/* prosthethei ena button sto header , back to all menu   */ 

add_action('admin_head', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen) return;

    if ($screen->id === 'edit-menu_item') {
        echo '<style>#posts-filter select[name="m"] { display: none; }</style>';
        return;
    }

    // Edit screen για το CPT menu_item: τόσο edit όσο και add-new
    if (!in_array($screen->base, ['post', 'post-new'], true)) return;
    if ($screen->post_type !== 'menu_item') return;

    $back_url = admin_url('edit.php?post_type=menu_item');
    $label = __('← Back to Menu Items', 'patlis-menu');

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
            a.href = <?php echo wp_json_encode($back_url); ?>;
            a.textContent = <?php echo wp_json_encode($label); ?>;

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