<?php
if (!defined('ABSPATH')) exit;

/* ============================================================
 * Admin menu (Accommodation)
 * ============================================================ */
add_action('admin_menu', 'patlis_accommodation_register_admin_menu');

function patlis_accommodation_register_admin_menu() {

    if (!patlis_accommodation_is_enabled_for_version()) return;

    $capability = 'patlis_manage';

    add_menu_page(
        'Accommodation',
        'Accommodation',
        $capability,
        'patlis-accommodation',
        'patlis_accommodation_render_placeholder', // placeholder (redirect happens in admin_init)
        'dashicons-building',
        29
    );

    // Rooms: make it the default submenu by using the SAME slug as parent
    add_submenu_page(
        'patlis-accommodation',
        'Rooms',
        'Rooms',
        $capability,
        'patlis-accommodation',
        'patlis_accommodation_render_placeholder'
    );


    // Amenities (core taxonomy screen)
    add_submenu_page(
        'patlis-accommodation',
        'Amenities',
        'Room Amenities',
        $capability,
        'edit-tags.php?taxonomy=room_amenity&post_type=patlis_room'
    );

    // Facilities
    add_submenu_page(
        'patlis-accommodation',
        'Facilities',
        'Property Facilities',
        $capability,
        'edit-tags.php?taxonomy=property_facility&post_type=patlis_room'
    );

    // Services
    add_submenu_page(
        'patlis-accommodation',
        'Services',
        'Property Services',
        $capability,
        'edit-tags.php?taxonomy=property_service&post_type=patlis_room'
    );

    // Experiences
    add_submenu_page(
        'patlis-accommodation',
        'Experiences',
        'Experiences',
        $capability,
        'edit.php?post_type=experience'
    );

    // Offers & Packages
    add_submenu_page(
        'patlis-accommodation',
        'Offers & Packages',
        'Offers & Packages',
        $capability,
        'edit.php?post_type=rates'
    );



    // Settings (separate slug)
    add_submenu_page(
        'patlis-accommodation',
        'Settings',
        'Settings',
        $capability,
        'patlis-accommodation-settings',
        'patlis_accommodation_render_settings_page'
    );
}

/**
 * Placeholder output so the menu page exists.
 * The real navigation to Rooms happens via admin_init redirect.
 */
function patlis_accommodation_render_placeholder() {
    echo '<div class="wrap"><p>Redirecting…</p></div>';
}

/**
 * Redirect top-level Accommodation page to Rooms list (CPT).
 * IMPORTANT: done in admin_init so headers can still be sent.
 */
add_action('admin_init', function () {

    if (!is_admin()) return;
    if (wp_doing_ajax()) return;

    if (!patlis_accommodation_is_enabled_for_version()) return;
    if (!current_user_can('patlis_manage')) return;

    if (isset($_GET['page']) && $_GET['page'] === 'patlis-accommodation') {
        wp_safe_redirect(admin_url('edit.php?post_type=patlis_room'));
        exit;
    }
});

/* ============================================================
 * Keep Accommodation menu active when browsing Rooms/Amenities
 * ============================================================ */
add_filter('parent_file', function ($parent_file) {
    global $current_screen;

    // CPT screens for patlis_room, experience, and rates
    if (
        !empty($current_screen->post_type)
        && in_array($current_screen->post_type, ['patlis_room', 'experience', 'rates'], true)
    ) {
        return 'patlis-accommodation';
    }

    return $parent_file;
});

add_filter('submenu_file', function ($submenu_file) {
    global $current_screen;

    if (empty($current_screen->post_type)) {
        return $submenu_file;
    }

    // Experience list / edit experience
    if ($current_screen->post_type === 'experience') {
        if ($current_screen->base === 'edit' || $current_screen->base === 'post') {
            return 'edit.php?post_type=experience';
        }

        return $submenu_file;
    }

    // Rates list / edit rate
    if ($current_screen->post_type === 'rates') {
        if ($current_screen->base === 'edit' || $current_screen->base === 'post') {
            return 'edit.php?post_type=rates';
        }

        return $submenu_file;
    }

    if ($current_screen->post_type !== 'patlis_room') {
        return $submenu_file;
    }

    // Rooms list / edit room
    if ($current_screen->base === 'edit' || $current_screen->base === 'post') {
        return 'patlis-accommodation'; // because Rooms submenu uses the same slug as parent
    }

    // Amenities taxonomy screens
    if ($current_screen->base === 'edit-tags' && !empty($current_screen->taxonomy)) {

        if ($current_screen->taxonomy === 'room_amenity') {
            return 'edit-tags.php?taxonomy=room_amenity&post_type=patlis_room';
        }

        if ($current_screen->taxonomy === 'property_facility') {
            return 'edit-tags.php?taxonomy=property_facility&post_type=patlis_room';
        }

        if ($current_screen->taxonomy === 'property_service') {
            return 'edit-tags.php?taxonomy=property_service&post_type=patlis_room';
        }
    }

    return $submenu_file;
});
