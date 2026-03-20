<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'patlis_menu_register', 5);

function patlis_menu_register(): void
{
    // CPT: Menu Items
    register_post_type('menu_item', [
        'labels' => [
            'name'               => 'Menu Items',
            'singular_name'      => 'Menu Item',
            'add_new_item'       => 'Add New Menu Item',
            'edit_item'          => 'Edit Menu Item',
            'new_item'           => 'New Menu Item',
            'view_item'          => 'View Menu Item',
            'search_items'       => 'Search Menu Items',
            'not_found'          => 'No menu items found',
            'not_found_in_trash' => 'No menu items found in trash',
            'all_items'          => 'All Menu Items',
        ],

        // IMPORTANT: Bricks θέλει public post type για να το δείξει στο dropdown
        'public'              => true,

        // αλλά δεν θέλουμε frontend URLs
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,
        'rewrite'             => false,
        'query_var'           => false,
        'show_in_nav_menus'   => false,

        // admin + REST (Bricks χρησιμοποιεί REST σε αρκετά σημεία)
        'show_ui'             => true,
        'show_in_rest'        => true,

        'supports'            => ['title', 'thumbnail'],

        // Θα μπει κάτω από το δικό σου admin menu
        'show_in_menu'        => false,
    ]);

    // Taxonomy: Menu Categories
    register_taxonomy('menu_section', ['menu_item'], [
        'labels' => [
            'name'          => 'Menu Categories',
            'singular_name' => 'Menu Category',
            'search_items'  => 'Search Categories',
            'all_items'     => 'All Categories',
            'edit_item'     => 'Edit Category',
            'update_item'   => 'Update Category',
            'add_new_item'  => 'Add New Category',
            'new_item_name' => 'New Category Name',
            'menu_name'     => 'Menu Categories',
        ],

        // Για να εμφανίζεται άνετα σε Bricks terms loops
        'public'            => true,

        // αλλά χωρίς term URLs
        'publicly_queryable'=> false,
        'rewrite'           => false,
        'query_var'         => false,
        'show_in_nav_menus' => false,

        'show_ui'           => true,
        'show_in_rest'      => true,
        'hierarchical'      => true,
        'show_admin_column' => true,
    ]);
}
