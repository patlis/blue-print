<?php
if (!defined('ABSPATH')) exit;

/**
 * Rooms archive query tweaks
 * - posts_per_page from settings (0 = all)
 * - orderby menu_order ASC
 * - only affects main archive query: /rooms/
 */

add_action('pre_get_posts', function ($q) {

    // Safety
    if (!($q instanceof WP_Query)) return;
    if (is_admin()) return;
    if (!$q->is_main_query()) return;

    // Only the Room archive
    if (!is_post_type_archive('patlis_room')) return;

    // Get settings
    $per_page = 0;
    if (function_exists('patlis_accommodation_get_settings')) {
        $s = patlis_accommodation_get_settings();
        $per_page = isset($s['rooms_per_page']) ? (int)$s['rooms_per_page'] : 0;
    }

    // posts_per_page: 0 = all
    if ($per_page <= 0) {
        $q->set('posts_per_page', -1);
    } else {
        $q->set('posts_per_page', $per_page);
    }

    // Sorting: menu_order ASC
    $q->set('orderby', 'menu_order');
    $q->set('order', 'ASC');

    // (Optional) ignore sticky posts (CPT usually doesn't use WP sticky, but safe)
    $q->set('ignore_sticky_posts', 1);

}, 20);
