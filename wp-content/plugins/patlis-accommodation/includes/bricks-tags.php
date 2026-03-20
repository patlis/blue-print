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
    $gOpt  = 'Patlis – Accommodation (Options)';
    $gProp = 'Patlis – Accommodation (Property)';

    // ROOM / Post tags
    $tags[] = ['name' => '{patlis_acc_room_id}',         'label' => 'Room: Post ID',        'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_title}',      'label' => 'Room: Title',          'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_image_id}',   'label' => 'Room: Featured image ID',  'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_image_url}',  'label' => 'Room: Featured image URL', 'group' => $gRoom];

    $tags[] = ['name' => '{patlis_acc_room_item_nr}',    'label' => 'Room: Item Nr',        'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_beds}',       'label' => 'Room: Beds',           'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_persons}',    'label' => 'Room: Persons',        'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_count}',      'label' => 'Room: Count',          'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_video_url}',  'label' => 'Room: Video URL',      'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_360_url}',    'label' => 'Room: 360 Image URL',  'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_book_url}',   'label' => 'Room: Booking URL',    'group' => $gRoom];
    $tags[] = ['name' => '{patlis_acc_room_sticky}',     'label' => 'Room: Sticky (1/0)',   'group' => $gRoom];

    // OPTIONS tags (from plugin settings)
    $tags[] = ['name' => '{patlis_acc_booking_mode}',         'label' => 'Options: Booking mode (0/1/2/3)', 'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_booking_email}',        'label' => 'Options: Booking email',          'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_booking_days_before}',  'label' => 'Options: Days before',           'group' => $gOpt];
    $tags[] = ['name' => '{patlis_acc_booking_redirect_url}', 'label' => 'Options: Redirect URL',          'group' => $gOpt];
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
    $rawLower = strtolower($raw);
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
        if (!$p) return '';
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

            return $v;
        }

        return '';
    }

    return '';
}


/**
 * Room amenities:
 * - TOP: flat <ul> with highlighted assigned terms  * - ALL: grouped by parent categories (for assigned child terms)
 */
function patlis_acc_render_room_amenities_html(int $room_id, bool $top_only): string
{
    $terms = get_the_terms($room_id, 'room_amenity');
    if (is_wp_error($terms) || empty($terms)) return '';

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
            $html .= '<li class="patlis-feature" data-term-id="' . (int)$t->term_id . '">' . esc_html($t->name) . '</li>';
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
            $html .= '<li class="patlis-feature" data-term-id="' . (int)$t->term_id . '">' . esc_html($t->name) . '</li>';
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
            $html .= '<li class="patlis-feature" data-term-id="' . (int)$c->term_id . '">' . esc_html($c->name) . '</li>';
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

    $parents = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'parent' => 0]);
    $parents = array_values(array_filter((array)$parents, fn($t) => $t && !is_wp_error($t)));
    if (empty($parents)) return '';

    $highlight_key = 'patlis_is_highlight';
    $order_key     = 'patlis_order';

    // TOP
    if ($top_only) {
        $items = [];

        foreach ($parents as $p) {
            $children = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'parent' => (int)$p->term_id]);
            foreach ((array)$children as $c) {
                if (!$c || is_wp_error($c)) continue;
                if ((int) get_term_meta($c->term_id, $highlight_key, true) === 1) {
                    $items[] = $c;
                }
            }
        }

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
            $html .= '<li class="patlis-feature" data-term-id="' . (int)$t->term_id . '">' . esc_html($t->name) . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    // ALL grouped
    uasort($parents, function($a, $b) use ($order_key) {
        $oa = (int) get_term_meta($a->term_id, $order_key, true);
        $ob = (int) get_term_meta($b->term_id, $order_key, true);
        if ($oa !== $ob) return $oa <=> $ob;
        return strcasecmp((string)$a->name, (string)$b->name);
    });

    $html = '<div class="patlis-features-grouped patlis-features-grouped--' . esc_attr($taxonomy) . '">';

    foreach ($parents as $p) {
        $children = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'parent' => (int)$p->term_id]);
        $children = array_values(array_filter((array)$children, fn($t) => $t && !is_wp_error($t)));
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
            $html .= '<li class="patlis-feature" data-term-id="' . (int)$c->term_id . '">' . esc_html($c->name) . '</li>';
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
