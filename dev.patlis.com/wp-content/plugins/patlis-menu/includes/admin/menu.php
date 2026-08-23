<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'patlis_menu_admin_menu');

function patlis_menu_admin_menu(): void
{
    $capability  = 'patlis_manage';
    $parent_slug = 'patlis-menu';

    // Parent menu opens Options page
    add_menu_page(
        __('Menu', 'patlis-menu'),
        __('Menu', 'patlis-menu'),
        $capability,
        $parent_slug,
        'patlis_menu_render_options_page',
        'dashicons-food',
        28
    );

    // Options (so it appears as first submenu)
    add_submenu_page(
        $parent_slug,
        __('Options', 'patlis-menu'),
        __('Options', 'patlis-menu'),
        $capability,
        $parent_slug,
        'patlis_menu_render_options_page'
    );

    // CPT / taxonomy screens
    add_submenu_page(
        $parent_slug,
        __('Food & drinks', 'patlis-menu'),
        __('Food & drinks', 'patlis-menu'),
        $capability,
        'edit.php?post_type=menu_item',
        null
    );

    add_submenu_page(
        $parent_slug,
        __('Categories', 'patlis-menu'),
        __('Categories', 'patlis-menu'),
        $capability,
        'edit-tags.php?taxonomy=menu_section&post_type=menu_item',
        null
    );

    add_submenu_page(
        $parent_slug,
        __('PDF files', 'patlis-menu'),
        __('PDF files', 'patlis-menu'),
        $capability,
        'edit.php?post_type=menu_pdf',
        null
    );
    
    /* administrator only */
    add_submenu_page(
        $parent_slug,
        'Import Menu Items',
        'Import',
        'manage_options',
        Patlis_Menu_Admin_Import::SLUG,
        ['Patlis_Menu_Admin_Import', 'render_page']
    );


    add_submenu_page(
        $parent_slug,
        'Bulk Edit Menu Items',
        'Bulk Edit',
        'manage_options',
        Patlis_Menu_Admin_Bulk_Edit::SLUG,
        ['Patlis_Menu_Admin_Bulk_Edit', 'render_page']
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
}
