<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'patlis_menu_admin_menu');

function patlis_menu_admin_menu(): void
{
    $capability  = 'patlis_manage';
    $parent_slug = 'patlis-menu';

    // Parent menu opens Options page
    add_menu_page(
        'My Menu',
        'My Menu',
        $capability,
        $parent_slug,
        'patlis_menu_render_options_page',
        'dashicons-food',
        28
    );

    // Options (so it appears as first submenu)
    add_submenu_page(
        $parent_slug,
        'Options',
        'Options',
        $capability,
        $parent_slug,
        'patlis_menu_render_options_page'
    );

    // CPT / taxonomy screens
    add_submenu_page(
        $parent_slug,
        'Menu Items',
        'Menu Items',
        $capability,
        'edit.php?post_type=menu_item',
        null
    );

    add_submenu_page(
        $parent_slug,
        'Categories',
        'Categories',
        $capability,
        'edit-tags.php?taxonomy=menu_section&post_type=menu_item',
        null
    );
}

/**
 * Options page render callback
 * (Θέλει να έχεις ήδη το class/renderer από το step 1,
 * αλλιώς βάλε εδώ προσωρινά ένα απλό echo).
 */
function patlis_menu_render_options_page(): void
{
    if (class_exists('Patlis_Menu_Admin_Page_Options') && method_exists('Patlis_Menu_Admin_Page_Options', 'render')) {
        Patlis_Menu_Admin_Page_Options::render();
        return;
    }

    // fallback (για να μην βγάζει λευκή σελίδα αν δεν έχεις βάλει ακόμα το step 1)
    echo '<div class="wrap"><h1>My Menu – Options</h1><p>Options page not loaded yet.</p></div>';
}
