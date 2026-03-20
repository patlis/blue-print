<?php
if (!defined('ABSPATH')) exit;

/**
 * Bricks Dynamic Tags for Patlis Menu (FINAL CLEAN VERSION)
 */

/* ============================================================
 * 1) Register tags in Bricks UI
 * ============================================================ */
add_filter('bricks/dynamic_tags_list', function ($tags) {

    $gCat  = 'Patlis – Menu (Category)';
    $gItem = 'Patlis – Menu (Item)';
    $gOpt  = 'Patlis – Menu (Options)';

    // Category / Term tags
    $tags[] = ['name' => '{patlis_menu_cat_id}',          'label' => 'Category: Term ID',      'group' => $gCat];
    $tags[] = ['name' => '{patlis_menu_cat_name}',        'label' => 'Category: Name',         'group' => $gCat];
    $tags[] = ['name' => '{patlis_menu_cat_description}', 'label' => 'Category: Description',  'group' => $gCat];
    $tags[] = ['name' => '{patlis_menu_cat_sort}',        'label' => 'Category: Sort',         'group' => $gCat];
    $tags[] = ['name' => '{patlis_menu_cat_show}',        'label' => 'Category: Show',         'group' => $gCat];
    $tags[] = ['name' => '{patlis_menu_cat_image_id}',    'label' => 'Category: Image ID',     'group' => $gCat];
    $tags[] = ['name' => '{patlis_menu_cat_image_url}',   'label' => 'Category: Image URL',    'group' => $gCat];

    for ($d = 0; $d <= 6; $d++) {
        $tags[] = ['name' => '{patlis_menu_cat_day' . $d . 'a}', 'label' => 'Category: day' . $d . ' from', 'group' => $gCat];
        $tags[] = ['name' => '{patlis_menu_cat_day' . $d . 'b}', 'label' => 'Category: day' . $d . ' to',   'group' => $gCat];
    }

    $tags[] = ['name' => '{patlis_menu_cat_open_now}', 'label' => 'Category: Open now', 'group' => $gCat];

    // Item / Post tags
    $tags[] = ['name' => '{patlis_menu_item_id}',           'label' => 'Item: Post ID',            'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_title}',        'label' => 'Item: Title',              'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_image_id}',     'label' => 'Item: Featured image ID',  'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_image_url}',    'label' => 'Item: Featured image URL', 'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_itemnr}',       'label' => 'Item: Item Nr',            'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_sort}',         'label' => 'Item: Sort',               'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_show}',         'label' => 'Item: Show',               'group' => $gItem];

    $tags[] = ['name' => '{patlis_menu_item_price}',        'label' => 'Item: Price',              'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_price2}',       'label' => 'Item: Price 2',            'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_price3}',       'label' => 'Item: Price 3',            'group' => $gItem];

    $tags[] = ['name' => '{patlis_menu_item_size1}',        'label' => 'Item: Size 1',             'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_size2}',        'label' => 'Item: Size 2',             'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_size3}',        'label' => 'Item: Size 3',             'group' => $gItem];

    // Formatted price tags
    $tags[] = ['name' => '{patlis_menu_item_price_currency}',  'label' => 'Item: Price (currency)',   'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_price2_currency}', 'label' => 'Item: Price 2 (currency)', 'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_price3_currency}', 'label' => 'Item: Price 3 (currency)', 'group' => $gItem];

    $tags[] = ['name' => '{patlis_menu_item_price_number}',  'label' => 'Item: Price (number)',   'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_price2_number}', 'label' => 'Item: Price 2 (number)', 'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_price3_number}', 'label' => 'Item: Price 3 (number)', 'group' => $gItem];

    $tags[] = ['name' => '{patlis_menu_item_allergies}',    'label' => 'Item: Allergies',          'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_description}',  'label' => 'Item: Description',        'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_vegetarian}',   'label' => 'Item: Vegetarian',         'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_vegan}',        'label' => 'Item: Vegan',              'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_carousel}',     'label' => 'Item: Add to carousel',    'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_categories}',   'label' => 'Item: Category names',     'group' => $gItem];
    $tags[] = ['name' => '{patlis_menu_item_category_ids}', 'label' => 'Item: Category IDs',       'group' => $gItem];

    // Options tag (for Bricks conditions)
    $tags[] = ['name' => '{patlis_menu_show_prices}', 'label' => 'Options: Show prices (1/0)', 'group' => $gOpt];
    $tags[] = ['name' => '{patlis_menu_display_mode}',  'label' => 'Options: Display mode (normal/centered)', 'group' => $gOpt];
    $tags[] = ['name' => '{patlis_menu_show_veg_filters}',  'label' => 'Options: Show veg filters (1/0)',     'group' => $gOpt];
    $tags[] = ['name' => '{patlis_menu_show_allergies}', 'label' => 'Options: Show allergies (1/0)', 'group' => $gOpt];
    $tags[] = ['name' => '{patlis_menu_allergies_description}', 'label' => 'Options: Allergies description (HTML)', 'group' => $gOpt];

    return $tags;
});

/* ============================================================
 * 2) Render tag values
 * ============================================================ */
add_filter('bricks/dynamic_data/render_tag', function ($value, $tag, $post = null, $context = null) {
    if (!is_string($tag)) return $value;
    $tag = trim($tag, '{}');
    if (strpos(strtolower($tag), 'patlis_menu_') !== 0) return $value;

    return patlis_menu_bricks_get_value(strtolower($tag), $post, $context);
}, 20, 4);

add_filter('bricks/dynamic_data/render_content', function ($content, $post, $context = 'text') {
    return patlis_menu_bricks_replace_in_string($content, $post, $context);
}, 20, 3);

/* ============================================================
 * Core value resolver
 * ============================================================ */
function patlis_menu_bricks_get_value(string $tag, $post = null, $context = null): string
{
    // --- OPTIONS TAGS ---
    if ($tag === 'patlis_menu_show_prices') {
        $opt = get_option('patlis_menu_options', []);
        if (!is_array($opt)) $opt = [];

        $show = !array_key_exists('show_catalog_prices', $opt) || !empty($opt['show_catalog_prices']);
        return $show ? '1' : '0';
    }

    if ($tag === 'patlis_menu_display_mode') {
        $opt = get_option('patlis_menu_options', []);
        if (!is_array($opt)) $opt = [];

        $mode = isset($opt['display_mode']) ? (string) $opt['display_mode'] : 'normal';
        $mode = trim($mode);
        if ($mode === '') $mode = 'normal';

        return $mode; // "normal" ή "centered" (ή future modes)
    }
    
    if ($tag === 'patlis_menu_show_veg_filters') {
        $opt = get_option('patlis_menu_options', []);
        if (!is_array($opt)) $opt = [];

        // Default NO αν δεν έχει οριστεί
        $show = !empty($opt['show_veg_filters']);

        return $show ? '1' : '0';
    }
    
    if ($tag === 'patlis_menu_show_allergies') {
        $opt = get_option('patlis_menu_options', []);
        if (!is_array($opt)) $opt = [];
    
        // Default YES αν δεν έχει οριστεί
        $show = !array_key_exists('show_allergies', $opt) || !empty($opt['show_allergies']);
    
        return $show ? '1' : '0';
    }
    
    if ($tag === 'patlis_menu_allergies_description') {
        $opt = get_option('patlis_menu_options', []);
        if (!is_array($opt)) $opt = [];

        $html = $opt['allergies_description_html'] ?? '';

        // Backward compatibility: παλιό format = απλό string
        if (is_string($html)) {
            return $html;
        }

        if (!is_array($html) || empty($html)) {
            return '';
        }

        // Current language
        $current_lang = '';
        if (function_exists('pll_current_language')) {
            $current_lang = pll_current_language('slug');
            $current_lang = is_string($current_lang) ? trim($current_lang) : '';
        }

        if ($current_lang !== '' && !empty($html[$current_lang]) && is_string($html[$current_lang])) {
            return $html[$current_lang];
        }

        // Default language
        $default_lang = '';
        if (function_exists('pll_default_language')) {
            $default_lang = pll_default_language('slug');
            $default_lang = is_string($default_lang) ? trim($default_lang) : '';
        }

        if ($default_lang !== '' && !empty($html[$default_lang]) && is_string($html[$default_lang])) {
            return $html[$default_lang];
        }

        // Fallback: first non-empty translation
        foreach ($html as $value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return '';
    }



    // --- CATEGORY TAGS ---
    if (strpos($tag, 'patlis_menu_cat_') === 0) {

        $term = patlis_menu_resolve_term_context($context);
        if (!$term) $term = patlis_menu_resolve_term_context($post);

        if (!$term) return '';
        $tid = (int) $term->term_id;

        if ($tag === 'patlis_menu_cat_id')          return (string) $tid;
        if ($tag === 'patlis_menu_cat_name')        return (string) $term->name;
        if ($tag === 'patlis_menu_cat_description') return (string) $term->description;
        if ($tag === 'patlis_menu_cat_show')        return patlis_menu_term_meta($tid, 'pmc_show');
        if ($tag === 'patlis_menu_cat_sort')        return patlis_menu_term_meta($tid, 'pmc_sort');

        if ($tag === 'patlis_menu_cat_image_id') {
            $id = get_term_meta($tid, 'pmc_image_id', true);
            return $id ? (string) $id : '';
        }

        if ($tag === 'patlis_menu_cat_image_url') {
            $id = get_term_meta($tid, 'pmc_image_id', true);
            if (!$id) return '';
            $url = wp_get_attachment_image_url($id, 'full');
            return $url ? (string) $url : '';
        }

        if (preg_match('/^patlis_menu_cat_day([0-6])(a|b)$/', $tag, $mm)) {
            return patlis_menu_term_meta($tid, 'pmc_day' . $mm[1] . $mm[2]);
        }

        if ($tag === 'patlis_menu_cat_open_now') {
            return (function_exists('patlis_menu_section_is_open_now') && patlis_menu_section_is_open_now($tid)) ? '1' : '0';
        }

        return '';
    }

    // --- ITEM TAGS ---
    if (strpos($tag, 'patlis_menu_item_') === 0) {
        $p = patlis_menu_resolve_post_context($post);
        if (!$p) return '';
        $pid = (int) $p->ID;

        if ($tag === 'patlis_menu_item_id')    return (string) $pid;
        if ($tag === 'patlis_menu_item_title') return (string) get_the_title($pid);

        if ($tag === 'patlis_menu_item_image_id') {
            $img_id = get_post_thumbnail_id($pid);
            return $img_id ? (string) $img_id : '';
        }

        if ($tag === 'patlis_menu_item_image_url') {
            $img_id = get_post_thumbnail_id($pid);
            return $img_id ? (string) wp_get_attachment_image_url($img_id, 'full') : '';
        }

        // ---- Formatted currency prices (requires patlis-core helpers) ----
        if ($tag === 'patlis_menu_item_price_currency') {
            $raw = patlis_menu_post_meta($pid, 'pmi_price');
            return function_exists('patlis_format_currency') ? patlis_format_currency($raw) : $raw;
        }

        if ($tag === 'patlis_menu_item_price2_currency') {
            $raw = patlis_menu_post_meta($pid, 'pmi_price2');
            return function_exists('patlis_format_currency') ? patlis_format_currency($raw) : $raw;
        }

        if ($tag === 'patlis_menu_item_price3_currency') {
            $raw = patlis_menu_post_meta($pid, 'pmi_price3');
            return function_exists('patlis_format_currency') ? patlis_format_currency($raw) : $raw;
        }

        // ---- Formatted number prices (requires patlis-core helpers) ----
        if ($tag === 'patlis_menu_item_price_number') {
            $raw = patlis_menu_post_meta($pid, 'pmi_price');
            return function_exists('patlis_format_number') ? patlis_format_number($raw) : $raw;
        }

        if ($tag === 'patlis_menu_item_price2_number') {
            $raw = patlis_menu_post_meta($pid, 'pmi_price2');
            return function_exists('patlis_format_number') ? patlis_format_number($raw) : $raw;
        }

        if ($tag === 'patlis_menu_item_price3_number') {
            $raw = patlis_menu_post_meta($pid, 'pmi_price3');
            return function_exists('patlis_format_number') ? patlis_format_number($raw) : $raw;
        }

        $meta_map = [
            'itemnr' => 'pmi_itemnr', 'show' => 'pmi_show', 'sort' => 'pmi_sort',
            'price' => 'pmi_price', 'price2' => 'pmi_price2', 'price3' => 'pmi_price3',
            'size1' => 'pmi_size1', 'size2' => 'pmi_size2', 'size3' => 'pmi_size3',
            'allergies' => 'pmi_allergies', 'description' => 'pmi_description',
            'vegetarian' => 'pmi_vegetarian', 'vegan' => 'pmi_vegan', 'carousel' => 'pmi_carousel'
        ];

        $sub = str_replace('patlis_menu_item_', '', $tag);
        if (isset($meta_map[$sub])) return patlis_menu_post_meta($pid, $meta_map[$sub]);

        if ($tag === 'patlis_menu_item_categories' || $tag === 'patlis_menu_item_category_ids') {
            $terms = get_the_terms($pid, 'menu_section');
            if (is_wp_error($terms) || empty($terms)) return '';
            $fn = ($tag === 'patlis_menu_item_category_ids')
                ? function ($t) { return $t->term_id; }
                : function ($t) { return $t->name; };
            return implode(', ', array_map($fn, $terms));
        }
    }

    return '';
}

/* ============================================================
 * Helpers
 * ============================================================ */
function patlis_menu_bricks_replace_in_string($content, $post = null, $context = null)
{
    if (!is_string($content) || strpos($content, '{patlis_menu_') === false) return $content;

    return preg_replace_callback('/{(patlis_menu_[a-z0-9_]+)}/i', function ($m) use ($post, $context) {
        return patlis_menu_bricks_get_value(strtolower($m[1]), $post, $context);
    }, $content);
}

function patlis_menu_post_meta($pid, $key)
{
    $v = get_post_meta($pid, $key, true);
    return is_scalar($v) ? (string) $v : '';
}

function patlis_menu_term_meta($tid, $key)
{
    $v = get_term_meta($tid, $key, true);
    return is_scalar($v) ? (string) $v : '';
}

function patlis_menu_resolve_post_context($obj)
{
    if ($obj instanceof WP_Post) return $obj;
    if (is_numeric($obj)) return get_post($obj);
    return get_post();
}

function patlis_menu_resolve_term_context($obj)
{
    // 1. Direct Term Object
    if ($obj instanceof WP_Term) return $obj;

    // 2. Correct Bricks API Check
    if (class_exists('\Bricks\Query')) {
        $loop_obj = \Bricks\Query::get_loop_object();

        if ($loop_obj instanceof WP_Term) return $loop_obj;

        if ($loop_obj instanceof WP_Post) {
            $terms = get_the_terms($loop_obj->ID, 'menu_section');
            if (!is_wp_error($terms) && !empty($terms)) return $terms[0];
        }
    }

    // 3. Global Loop Object Fallback
    global $bricks_loop_object;
    if ($bricks_loop_object instanceof WP_Term) return $bricks_loop_object;

    // 4. Input Object Fallbacks
    if (is_object($obj) && isset($obj->term_id)) return get_term($obj->term_id);
    if (is_array($obj) && isset($obj['term_id'])) return get_term($obj['term_id']);

    // 5. Fallback if $obj is a Post
    if ($obj instanceof WP_Post) {
        $terms = get_the_terms($obj->ID, 'menu_section');
        if (!is_wp_error($terms) && !empty($terms)) return $terms[0];
    }

    // 6. Queried Object
    $qo = get_queried_object();
    if ($qo instanceof WP_Term) return $qo;

    return null;
}
