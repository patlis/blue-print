<?php
if (!defined('ABSPATH')) exit;

/**
 * Bricks Dynamic Tags for Patlis Accommodation (Rooms + Options + Features HTML lists)
 *
 * IMPORTANT:
 * - One single file handles:
 *   - tags registration
 *   - render_tag resolver
 *   - render_content replace (Rich Text)
 * - Unknown tags are NOT deleted anymore (so other handlers can still replace them).
 */

/* ============================================================
 * 1) Register tags in Bricks UI
 * ============================================================ */
add_filter('bricks/dynamic_tags_list', function ($tags) {

    $gRoom = 'Patlis – Accommodation (Room)';
    $gRate = 'Patlis – Accommodation (Offer/Package)';
    $gOpt  = 'Patlis – Accommodation (Options)';
    $gProp = 'Patlis – Accommodation (Property)';

    // ROOM / Post tags
    $tags[] = ['name' => '{patlis_acc_room_id}',         'label' => 'Room: Post ID',        'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_title}',      'label' => 'Room: Title',          'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_image_id}',   'label' => 'Room: Featured image ID',  'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_image_url}',  'label' => 'Room: Featured image URL', 'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_gallery_json}','label' => 'Room: Gallery JSON (ids + urls + meta)', 'group' => $gRoom];

    $tags[] = ['name' => '{patlis_acc_room_item_nr}',    'label' => 'Room: Item Nr',        'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_beds}',       'label' => 'Room: Beds',           'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_persons}',    'label' => 'Room: Persons',        'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_count}',      'label' => 'Room: Count',          'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_video_url}',  'label' => 'Room: Video URL',      'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_360_url}',    'label' => 'Room: 360 Image URL',  'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_book_url}',   'label' => 'Room: Booking URL',    'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_sticky}',     'label' => 'Room: Sticky (1/0)',   'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_package_booking_url}', 'label' => 'Offer/Package: Booking URL (ACF + fallback)', 'group' => $gRate];
    $tags[] = ['name' => '{patlis_acc_room_rates_json}', 'label' => 'Room Rates: JSON array (room periods + prices)', 'group' => $gRate];
    $tags[] = ['name' => '{patlis_acc_room_rates_count}', 'label' => 'Room Rates: Count for current room', 'group' => $gRate];

    // OPTIONS tags (from plugin settings)
    $tags[] = ['name' => '{patlis_acc_booking_mode}',         'label' => 'Options: Booking mode (0/1/2/3)', 'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_booking_email}',        'label' => 'Options: Booking email',          'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_booking_days_before}',  'label' => 'Options: Days before',           'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_booking_redirect_url}', 'label' => 'Options: Fallback redirect URL',  'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_booking_3party_code}',  'label' => 'Options: 3rd-party code (HTML)', 'group' => $gOpt];

    $tags[] = ['name' => '{patlis_acc_rooms_per_page}', 'label' => 'Options: Rooms per page (0=all)', 'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_show_prices}',    'label' => 'Options: Show prices (1/0)',     'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_prices_text}',    'label' => 'Options: Prices text',           'group' => $gOpt];

    $tags[] = ['name' => '{patlis_acc_rooms_options}',     'label' => 'Booking: Rooms options (Label|ID)',  'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_selected_room_id}',  'label' => 'Booking: Selected room_id from URL', 'group' => $gOpt];

    $tags[] = ['name' => '{patlis_acc_room_short_desc}', 'label' => 'Room: Short description', 'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_size_m2}',    'label' => 'Room: Size (m²)',         'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_bed_type}',   'label' => 'Room: Bed type',          'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_view}',       'label' => 'Room: View',              'group' => $gRoom];

    $tags[] = ['name' => '{patlis_acc_room_amenities_top}',        'label' => 'Room: Amenities TOP (HTML list)',     'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_amenities_all}',        'label' => 'Room: Amenities ALL (grouped HTML)',  'group' => $gRoom];

    $tags[] = ['name' => '{patlis_acc_property_services_top}',     'label' => 'Property: Services TOP (HTML list)',   'group' => $gProp];
    $tags[] = ['name' => '{patlis_acc_property_services_all}',     'label' => 'Property: Services ALL (grouped HTML)','group' => $gProp];

    $tags[] = ['name' => '{patlis_acc_property_facilities_top}',   'label' => 'Property: Facilities TOP (HTML list)', 'group' => $gProp];
    $tags[] = ['name' => '{patlis_acc_property_facilities_all}',   'label' => 'Property: Facilities ALL (grouped HTML)','group' => $gProp];

    return $tags;
});


/* ============================================================
 * 2) Render tag values (dynamic data)
 * ============================================================ */
add_filter('bricks/dynamic_data/render_tag', function ($value, $tag, $post = null, $context = null) {
    if (!is_string($tag)) return $value;

    $tag = trim($tag, '{}');
    if (strpos(strtolower($tag), 'patlis_acc_') !== 0) return $value;

    $resolved = patlis_acc_bricks_get_value(strtolower($tag), $post, $context);

    // IMPORTANT: do NOT override already resolved value if we don't know this tag
    return $resolved;
}, 20, 4);

add_filter('bricks/dynamic_data/render_content', function ($content, $post, $context = 'text') {
    return patlis_acc_bricks_replace_in_string($content, $post, $context);
}, 20, 3);


/* ============================================================
 * Core value resolver
 * ============================================================ */
function patlis_acc_bricks_get_value(string $tag, $post = null, $context = null): string
{
    // Room rates payload for Query Array loops inside room pages/templates.
    if ($tag === 'patlis_acc_room_rates_json' || $tag === 'patlis_acc_room_rates_count') {
        $room_id = 0;
        $p = patlis_acc_resolve_post_context($post);

        if ($p && get_post_type($p) === 'patlis_room') {
            $room_id = (int) $p->ID;
        }

        $rows = function_exists('patlis_acc_get_room_rates_payload')
            ? patlis_acc_get_room_rates_payload($room_id > 0 ? $room_id : null)
            : [];

        if ($tag === 'patlis_acc_room_rates_count') {
            return (string) count($rows);
        }

        return wp_json_encode($rows);
    }

    // Offer/Package ACF booking URL with fallback to global booking_redirect_url
    if ($tag === 'patlis_acc_package_booking_url') {
        $p = patlis_acc_resolve_post_context($post);
        if (!$p || get_post_type($p) !== 'rates') return '';

        $v = patlis_acc_post_meta((int) $p->ID, 'package_booking_url');
        if (!empty($v)) return $v;

        $s = function_exists('patlis_accommodation_get_settings') ? patlis_accommodation_get_settings() : [];
        return isset($s['booking_redirect_url']) ? (string) $s['booking_redirect_url'] : '';
    }

    // Room amenities TOP/ALL (HTML)
    if ($tag === 'patlis_acc_room_amenities_top' || $tag === 'patlis_acc_room_amenities_all') {
        $p = patlis_acc_resolve_post_context($post);
        if (!$p) return '';
        return patlis_acc_render_room_amenities_html((int) $p->ID, $tag === 'patlis_acc_room_amenities_top');
    }

    // Property services TOP/ALL (HTML)
    if ($tag === 'patlis_acc_property_services_top' || $tag === 'patlis_acc_property_services_all') {
        return patlis_acc_render_property_terms_html('property_service', $tag === 'patlis_acc_property_services_top');
    }

    // Property facilities TOP/ALL (HTML)
    if ($tag === 'patlis_acc_property_facilities_top' || $tag === 'patlis_acc_property_facilities_all') {
        return patlis_acc_render_property_terms_html('property_facility', $tag === 'patlis_acc_property_facilities_top');
    }

    /* ----------------------------
     * OPTIONS
     * ---------------------------- */
    if (strpos($tag, 'patlis_acc_') === 0 && strpos($tag, 'patlis_acc_room_') !== 0) {

        $s = function_exists('patlis_accommodation_get_settings') ? patlis_accommodation_get_settings() : [];
        if (!is_array($s)) $s = [];

        if ($tag === 'patlis_acc_booking_mode')         return (string) (int) ($s['booking_mode'] ?? 0);
        if ($tag === 'patlis_acc_booking_email')        return (string) ($s['booking_email'] ?? '');
        if ($tag === 'patlis_acc_booking_days_before')  return (string) (int) ($s['booking_days_before'] ?? 0);
        if ($tag === 'patlis_acc_booking_redirect_url') return (string) ($s['booking_redirect_url'] ?? '');
        if ($tag === 'patlis_acc_booking_3party_code')  return (string) ($s['booking_3party_code'] ?? '');

        if ($tag === 'patlis_acc_rooms_per_page') return (string) (int) ($s['rooms_per_page'] ?? 0);
        if ($tag === 'patlis_acc_show_prices')    return !empty($s['show_prices'] ?? 0) ? '1' : '0';
        if ($tag === 'patlis_acc_prices_text')    return (string) ($s['prices_text'] ?? '');

        if ($tag === 'patlis_acc_rooms_options') {
            return function_exists('patlis_acc_get_rooms_options_string') ? patlis_acc_get_rooms_options_string() : '';
        }

        if ($tag === 'patlis_acc_selected_room_id') {
            return function_exists('patlis_acc_get_selected_room_id_from_url') ? patlis_acc_get_selected_room_id_from_url() : '';
        }

        return '';
    }

    /* ----------------------------
     * ROOM / POST
     * ---------------------------- */
    if (strpos($tag, 'patlis_acc_room_') === 0) {

        $p = patlis_acc_resolve_post_context($post);
        if ((!$p || get_post_type($p) !== 'patlis_room') && function_exists('get_queried_object_id')) {
            $queried_id = (int) get_queried_object_id();
            if ($queried_id > 0 && get_post_type($queried_id) === 'patlis_room') {
                $p = get_post($queried_id);
            }
        }

        if (!$p || get_post_type($p) !== 'patlis_room') return '';
        $pid = (int) $p->ID;

        if ($tag === 'patlis_acc_room_id')    return (string) $pid;
        if ($tag === 'patlis_acc_room_title') return (string) get_the_title($pid);

        if ($tag === 'patlis_acc_room_image_id') {
            $img_id = get_post_thumbnail_id($pid);
            return $img_id ? (string) $img_id : '';
        }

        if ($tag === 'patlis_acc_room_image_url') {
            $img_id = get_post_thumbnail_id($pid);
            return $img_id ? (string) wp_get_attachment_image_url($img_id, 'full') : '';
        }

        if ($tag === 'patlis_acc_room_gallery_json') {
            $gallery = patlis_acc_get_room_gallery_items($pid);
            return wp_json_encode($gallery);
        }

        // Meta keys (ΜΟΝΟ δικά μας — όχι cf_*)
        $meta_map = [
            'item_nr'    => 'room_item_nr',
            'beds'       => 'room_beds',
            'persons'    => 'room_persons',
            'count'      => 'room_count',
            'video_url'  => 'room_video_url',
            '360_url'    => 'room_img_360_url',
            'book_url'   => 'room_book_url',
            'sticky'     => 'room_sticky',
            'short_desc' => 'room_short_desc',
            'size_m2'    => 'room_size_m2',
            'bed_type'   => 'room_bed_type',
            'view'       => 'room_view',
        ];

        $sub = str_replace('patlis_acc_room_', '', $tag);
        if (isset($meta_map[$sub])) {
            $v = patlis_acc_post_meta($pid, $meta_map[$sub]);

            // Sticky: πάντα 1/0
            if ($sub === 'sticky') return ((int)$v) ? '1' : '0';

            // FALLBACK: if room_book_url is empty, use global booking_redirect_url
            if ($sub === 'book_url' && empty($v)) {
                $s = function_exists('patlis_accommodation_get_settings') ? patlis_accommodation_get_settings() : [];
                $fallback = isset($s['booking_redirect_url']) ? (string) $s['booking_redirect_url'] : '';
                return $fallback;
            }

            return $v;
        }

        return '';
    }

    return '';
}


/**
 * Room amenities:
 * - TOP: flat <ul> with highlighted assigned terms  * - ALL: grouped by parent categories (for assigned child terms)
 * 
 * Works with Polylang: if room is translated, fetches amenities from default language
 * and translates the term IDs to current language.
 */
function patlis_acc_render_room_amenities_html(int $room_id, bool $top_only): string
{
    // Polylang: if this is a translated room, get amenities from default language
    if (function_exists('pll_get_post_language') && function_exists('pll_default_language')) {
        $current_lang = pll_get_post_language((int) $room_id);
        $default_lang = pll_default_language();
        
        if (is_string($current_lang) && is_string($default_lang) && $current_lang !== $default_lang && function_exists('pll_get_post')) {
            $default_room_id = pll_get_post((int) $room_id, $default_lang);
            if ($default_room_id > 0) {
                $room_id = (int) $default_room_id;
            }
        }
    }

    $terms = get_the_terms($room_id, 'room_amenity');
    if (is_wp_error($terms) || empty($terms)) return '';
    
    // Polylang: translate term IDs to current language if needed
    if (function_exists('pll_get_term_language') && function_exists('pll_get_term') && function_exists('pll_current_language')) {
        $current_lang = pll_current_language('slug');
        
        if (is_string($current_lang) && $current_lang !== '') {
            $translated_terms = [];
            
            foreach ($terms as $t) {
                $term_lang = pll_get_term_language((int) $t->term_id);
                
                // If term is in default language (or no language info), try to get translation
                if ($term_lang && is_string($term_lang)) {
                    $translated_term_id = pll_get_term((int) $t->term_id, $current_lang);
                    if ($translated_term_id > 0) {
                        $translated_term = get_term((int) $translated_term_id, 'room_amenity');
                        if ($translated_term && !is_wp_error($translated_term)) {
                            $translated_terms[] = $translated_term;
                        }
                    } else {
                        // No translation, keep original
                        $translated_terms[] = $t;
                    }
                } else {
                    $translated_terms[] = $t;
                }
            }
            
            if (!empty($translated_terms)) {
                $terms = $translated_terms;
            }
        }
    }

    $highlight_key = defined('PATLIS_AMENITY_META_HIGHLIGHT') ? PATLIS_AMENITY_META_HIGHLIGHT : 'patlis_is_highlight';
    $order_key     = defined('PATLIS_AMENITY_META_ORDER') ? PATLIS_AMENITY_META_ORDER : 'patlis_order';

    // TOP: highlighted, flat list
    if ($top_only) {
        $top = array_filter($terms, function($t) use ($highlight_key) {
            return (int) get_term_meta($t->term_id, $highlight_key, true) === 1;
        });

        if (empty($top)) return '';

        // keep it simple: alphabetical (as before)
        usort($top, fn($a, $b) => strcasecmp((string)$a->name, (string)$b->name));

        $html = '<ul class="patlis-features check-list patlis-features--top patlis-features--amenities">';
        foreach ($top as $t) {
            $html .= '<li>' . esc_html($t->name) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    // ALL: grouped under parent categories
    $children = array_values(array_filter($terms, function($t) {
        return $t && !is_wp_error($t) && (int)$t->parent !== 0;
    }));

    if (empty($children)) {
        // fallback: if user assigned only parent terms, show flat
        usort($terms, fn($a, $b) => strcasecmp((string)$a->name, (string)$b->name));
        $html = '<ul class="patlis-features check-list patlis-features--amenities">';
        foreach ($terms as $t) {
            $html .= '<li>' . esc_html($t->name) . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    // Build groups by parent
    $groups = []; // parent_id => ['parent'=>term, 'children'=>[]]

    foreach ($children as $c) {
        $pid = (int) $c->parent;
        if (!isset($groups[$pid])) {
            $p = get_term($pid, 'room_amenity');
            if ($p && !is_wp_error($p)) {
                $groups[$pid] = ['parent' => $p, 'children' => []];
            }
        }
        if (isset($groups[$pid])) {
            $groups[$pid]['children'][] = $c;
        }
    }

    if (empty($groups)) return '';

    // Sort parent groups by order meta then name
    uasort($groups, function($ga, $gb) use ($order_key) {
        $oa = (int) get_term_meta($ga['parent']->term_id, $order_key, true);
        $ob = (int) get_term_meta($gb['parent']->term_id, $order_key, true);
        if ($oa !== $ob) return $oa <=> $ob;
        return strcasecmp((string)$ga['parent']->name, (string)$gb['parent']->name);
    });

    // Sort children in each group by order meta then name
    foreach ($groups as &$g) {
        usort($g['children'], function($a, $b) use ($order_key) {
            $oa = (int) get_term_meta($a->term_id, $order_key, true);
            $ob = (int) get_term_meta($b->term_id, $order_key, true);
            if ($oa !== $ob) return $oa <=> $ob;
            return strcasecmp((string)$a->name, (string)$b->name);
        });
    }
    unset($g);

    $html = '<div class="patlis-features-grouped patlis-features-grouped--amenities">';

    foreach ($groups as $g) {
        if (empty($g['children'])) continue;
        
        $parent = $g['parent'] ?? null;

        $icon_html = '';
        
        if ($parent && !is_wp_error($parent) && !empty($parent->term_id)) {
        
            $icon_key = defined('PATLIS_AMENITY_META_ICON') ? PATLIS_AMENITY_META_ICON : 'patlis_icon';
            $icon_key = 'patlis_icon';        
            $icon = (string) get_term_meta((int) $parent->term_id, $icon_key, true);
            $icon = trim($icon);
        
              if ($icon !== '') {
                $icon_html = '<i class="fa-lg ' . esc_attr($icon) . ' patlis-features-title-icon" aria-hidden="true"></i> ';
              }
        }
        
        //amenities
        
        $html .= '<section class="patlis-features-group" data-parent-id="' . (int)$g['parent']->term_id . '">';
        $html .= '<p class="patlis-features-title">' . $icon_html . esc_html($g['parent']->name) . '</p>';
        $desc = isset($g['parent']->description) ? trim((string) $g['parent']->description) : '';
        if ($desc !== '') {
            $html .= '<p class="patlis-features-desc">' . esc_html($desc) . '</p>';
        }

        $html .= '<ul class="patlis-features check-list patlis-features--amenities">';

        foreach ($g['children'] as $c) {
            $html .= '<li>' . esc_html($c->name) . '</li>';
        }

        $html .= '</ul></section>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Property terms (Services / Facilities):
 * - TOP: highlighted children, flat list  * - ALL: grouped under parent categories
 */
function patlis_acc_render_property_terms_html(string $taxonomy, bool $top_only): string
{
    // If taxonomy doesn't exist, return empty (safe)
    if (!taxonomy_exists($taxonomy)) return '';

    $pll_ready    = function_exists('pll_current_language') && function_exists('pll_default_language') && function_exists('pll_get_term');
    $current_lang = $pll_ready ? (string) pll_current_language('slug') : '';
    $default_lang = $pll_ready ? (string) pll_default_language('slug') : '';
    $source_lang  = ($pll_ready && $current_lang !== '' && $default_lang !== '' && $current_lang !== $default_lang)
        ? $default_lang
        : $current_lang;

    $parent_query = [
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'parent'     => 0,
    ];

    if ($source_lang !== '') {
        $parent_query['lang'] = $source_lang;
    }

    $source_parents = get_terms($parent_query);
    $source_parents = array_values(array_filter((array) $source_parents, fn($t) => $t && !is_wp_error($t)));
    if (empty($source_parents)) return '';

    $map_term_to_current = function ($term) use ($taxonomy, $pll_ready, $current_lang, $source_lang) {
        if (!$term || is_wp_error($term)) {
            return null;
        }

        if (!$pll_ready || $current_lang === '' || $source_lang === '' || $current_lang === $source_lang) {
            return $term;
        }

        $translated_id = (int) pll_get_term((int) $term->term_id, $current_lang);
        if ($translated_id <= 0) {
            // Missing translation -> keep source term as fallback.
            return $term;
        }

        $translated = get_term($translated_id, $taxonomy);
        if (!$translated || is_wp_error($translated)) {
            return $term;
        }

        return $translated;
    };

    $highlight_key = 'patlis_is_highlight';
    $order_key     = 'patlis_order';

    // TOP
    if ($top_only) {
        $items = [];

        foreach ($source_parents as $source_parent) {
            $child_query = [
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'parent'     => (int) $source_parent->term_id,
            ];
            if ($source_lang !== '') {
                $child_query['lang'] = $source_lang;
            }

            $children = get_terms($child_query);
            foreach ((array)$children as $c) {
                if (!$c || is_wp_error($c)) continue;
                if ((int) get_term_meta($c->term_id, $highlight_key, true) === 1) {
                    $items[] = $map_term_to_current($c);
                }
            }
        }

        $items = array_values(array_filter((array) $items, fn($t) => $t && !is_wp_error($t)));
        $unique_items = [];
        foreach ($items as $item) {
            $unique_items[(int) $item->term_id] = $item;
        }
        $items = array_values($unique_items);

        if (empty($items)) return '';

        // simple: order meta then name
        usort($items, function($a, $b) use ($order_key) {
            $oa = (int) get_term_meta($a->term_id, $order_key, true);
            $ob = (int) get_term_meta($b->term_id, $order_key, true);
            if ($oa !== $ob) return $oa <=> $ob;
            return strcasecmp((string)$a->name, (string)$b->name);
        });

        $html = '<ul class="patlis-features check-list patlis-features--top patlis-features--' . esc_attr($taxonomy) . '">';
        foreach ($items as $t) {
            $html .= '<li>' . esc_html($t->name) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    // ALL grouped
    $groups = [];

    foreach ($source_parents as $source_parent) {
        $render_parent = $map_term_to_current($source_parent);
        if (!$render_parent || is_wp_error($render_parent)) {
            continue;
        }

        $group_key = (int) $render_parent->term_id;
        if (!isset($groups[$group_key])) {
            $groups[$group_key] = [
                'parent' => $render_parent,
                'children' => [],
            ];
        }

        $child_query = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => (int) $source_parent->term_id,
        ];
        if ($source_lang !== '') {
            $child_query['lang'] = $source_lang;
        }

        $source_children = get_terms($child_query);
        $source_children = array_values(array_filter((array) $source_children, fn($t) => $t && !is_wp_error($t)));

        foreach ($source_children as $source_child) {
            $render_child = $map_term_to_current($source_child);
            if (!$render_child || is_wp_error($render_child)) {
                continue;
            }

            $groups[$group_key]['children'][(int) $render_child->term_id] = $render_child;
        }
    }

    if (empty($groups)) return '';

    foreach ($groups as &$group) {
        $group['children'] = array_values($group['children']);
    }
    unset($group);

    $parents = array_map(fn($g) => $g['parent'], array_values($groups));

    uasort($parents, function($a, $b) use ($order_key) {
        $oa = (int) get_term_meta($a->term_id, $order_key, true);
        $ob = (int) get_term_meta($b->term_id, $order_key, true);
        if ($oa !== $ob) return $oa <=> $ob;
        return strcasecmp((string)$a->name, (string)$b->name);
    });

    $html = '<div class="patlis-features-grouped patlis-features-grouped--' . esc_attr($taxonomy) . '">';

    foreach ($parents as $p) {
        $group_key = (int) $p->term_id;
        $children = isset($groups[$group_key]) ? (array) $groups[$group_key]['children'] : [];
        if (empty($children)) continue;

        usort($children, function($a, $b) use ($order_key) {
            $oa = (int) get_term_meta($a->term_id, $order_key, true);
            $ob = (int) get_term_meta($b->term_id, $order_key, true);
            if ($oa !== $ob) return $oa <=> $ob;
            return strcasecmp((string)$a->name, (string)$b->name);
        });
        
        $icon_html = '';
        $icon_key = defined('PATLIS_AMENITY_META_ICON') ? PATLIS_AMENITY_META_ICON : 'patlis_icon';
        $icon     = (string) get_term_meta((int) $p->term_id, $icon_key, true);
        $icon     = trim($icon);
        
        if ($icon !== '') {
          $icon_html = '<i class="fa-lg ' . esc_attr($icon) . ' patlis-features-title-icon" aria-hidden="true"></i> ';
        }

        $html .= '<section class="patlis-features-group" data-parent-id="' . (int)$p->term_id . '">';
        $html .= '<p class="patlis-features-title">' . $icon_html . esc_html($p->name) . '</p>';
        $desc = isset($p->description) ? trim((string) $p->description) : '';
        if ($desc !== '') {
            $html .= '<p class="patlis-features-desc">' . esc_html($desc) . '</p>';
        }
        $html .= '<ul class="patlis-features check-list patlis-features--' . esc_attr($taxonomy) . '">';

        foreach ($children as $c) {
            $html .= '<li>' . esc_html($c->name) . '</li>';
        }

        $html .= '</ul></section>';
    }

    $html .= '</div>';

    return $html;
}


/* ============================================================
 * Helpers (same pattern as patlis-menu)
 * ============================================================ */
function patlis_acc_bricks_replace_in_string($content, $post = null, $context = null)
{
    if (!is_string($content) || strpos($content, '{patlis_acc_') === false) return $content;

    return preg_replace_callback('/{(patlis_acc_[a-z0-9_]+)}/i', function ($m) use ($post, $context) {
        $resolved = patlis_acc_bricks_get_value(strtolower($m[1]), $post, $context);

        // IMPORTANT: keep unknown tags intact so others (or future) can still replace them
        if ($resolved !== '') return $resolved;

        // Αν είναι δικό μας (γνωστό) tag και είναι άδειο -> σβήστο
        if (patlis_acc_is_known_tag(strtolower($m[1]))) return '';
        
        // Αλλιώς άφησέ το (άγνωστο tag)
        return $m[0];

    }, $content);
}

function patlis_acc_post_meta($pid, $key): string
{
    $v = get_post_meta($pid, $key, true);
    return is_scalar($v) ? (string) $v : '';
}

function patlis_acc_resolve_post_context($obj)
{
    if ($obj instanceof WP_Post) return $obj;
    if (is_numeric($obj)) return get_post($obj);
    return get_post();
}

function patlis_acc_get_room_gallery_items(int $post_id): array
{
    if ($post_id <= 0) {
        return [];
    }

    $resolve_ids = function (int $id): array {
        $raw_ids = get_post_meta($id, 'room_gallery_ids', true);
        if (is_array($raw_ids)) {
            $ids = $raw_ids;
        } elseif (is_string($raw_ids)) {
            $parts = preg_split('/[\s,]+/', trim($raw_ids));
            $ids = is_array($parts) ? $parts : [];
        } else {
            $ids = [];
        }

        return array_values(array_unique(array_filter(array_map('absint', $ids), function ($item_id) {
            return $item_id > 0;
        })));
    };

    $ids = $resolve_ids($post_id);

    if (
        empty($ids)
        && function_exists('pll_get_post_language')
        && function_exists('pll_default_language')
        && function_exists('pll_get_post')
    ) {
        $current_lang = pll_get_post_language($post_id, 'slug');
        $default_lang = pll_default_language('slug');

        if (
            is_string($current_lang)
            && is_string($default_lang)
            && $current_lang !== ''
            && $default_lang !== ''
            && $current_lang !== $default_lang
        ) {
            $default_post_id = (int) pll_get_post($post_id, $default_lang);
            if ($default_post_id > 0 && $default_post_id !== $post_id) {
                $ids = $resolve_ids($default_post_id);
            }
        }
    }

    if (empty($ids)) {
        return [];
    }

    $items = [];

    foreach ($ids as $id) {
        $full = wp_get_attachment_image_src($id, 'full');
        if (!is_array($full) || empty($full[0])) {
            continue;
        }

        $caption = wp_get_attachment_caption($id);
        $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
        $thumb_url = wp_get_attachment_image_url($id, 'thumbnail') ?: '';
        $medium_url = wp_get_attachment_image_url($id, 'medium') ?: '';
        $large_url = wp_get_attachment_image_url($id, 'large') ?: '';
        $full_url = (string) $full[0];

        $items[] = [
            'id' => (int) $id,
            'title' => (string) get_the_title($id),
            'alt' => is_string($alt) ? $alt : '',
            'caption' => is_string($caption) ? $caption : '',
            'url' => $full_url,
            'width' => (int) ($full[1] ?? 0),
            'height' => (int) ($full[2] ?? 0),
            // Flat size keys for Bricks query_array access: {query_array @key:'thumbnail'}
            'thumbnail' => $thumb_url,
            'medium' => $medium_url,
            'large' => $large_url,
            'full' => $full_url,
            'sizes' => [
                'thumbnail' => $thumb_url,
                'medium' => $medium_url,
                'large' => $large_url,
                'full' => $full_url,
            ],
        ];
    }

    return $items;
}

function patlis_acc_is_known_tag(string $tag): bool
{
    static $known = [
        // room html
        'patlis_acc_room_amenities_top',
        'patlis_acc_room_amenities_all',

        // property html
        'patlis_acc_property_services_top',
        'patlis_acc_property_services_all',
        'patlis_acc_property_facilities_top',
        'patlis_acc_property_facilities_all',

        // (αν κρατήσεις κι άλλα tags, τα βάζεις εδώ)
    ];

    return strpos($tag, 'patlis_acc_') === 0;
}

/**
 * Bricks {echo:...} wrappers for room rates payload.
 */
if (!function_exists('patlis_acc_room_rates_json')) {
    function patlis_acc_room_rates_json(?int $room_id = null): string
    {
        if (!function_exists('patlis_acc_get_room_rates_payload')) {
            return '[]';
        }

        $rows = patlis_acc_get_room_rates_payload($room_id !== null ? (int) $room_id : null);
        return wp_json_encode($rows);
    }
}

if (!function_exists('patlis_acc_room_rates_count')) {
    function patlis_acc_room_rates_count(?int $room_id = null): string
    {
        if (!function_exists('patlis_acc_get_room_rates_payload')) {
            return '0';
        }

        $rows = patlis_acc_get_room_rates_payload($room_id !== null ? (int) $room_id : null);
        return (string) count($rows);
    }
}

add_filter('bricks/code/echo_function_names', function ($functions) {
    if (empty($functions)) {
        $functions = [];
    } elseif (is_string($functions)) {
        $functions = array_map('trim', explode(',', $functions));
    } elseif (!is_array($functions)) {
        $functions = [];
    }

    $functions[] = 'patlis_acc_room_rates_json';
    $functions[] = 'patlis_acc_room_rates_count';

    return array_values(array_unique($functions));
});
