<?php
if (!defined('ABSPATH')) exit;

add_action('init', 'patlis_menu_register', 5);

function patlis_menu_register(): void
{
    // CPT: Menu Items
    register_post_type('menu_item', [
        'labels' => [
            'name'               => __('Food & drinks', 'patlis-menu'),
            'singular_name'      => __('Food or drink', 'patlis-menu'),
            'add_new_item'       => __('Add new food or drink', 'patlis-menu'),
            'edit_item'          => __('Edit food or drink', 'patlis-menu'),
            'new_item'           => __('New food or drink', 'patlis-menu'),
            'view_item'          => __('View food or drink', 'patlis-menu'),
            'search_items'       => __('Search food or drinks', 'patlis-menu'),
            'not_found'          => __('No food or drinks found', 'patlis-menu'),
            'not_found_in_trash' => __('No food or drinks found in trash', 'patlis-menu'),
            'all_items'          => __('All food or drinks', 'patlis-menu'),
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

    // CPT: Menu PDFs
    register_post_type('menu_pdf', [
        'labels' => [
            'name'               => 'PDF',
            'singular_name'      => 'PDF',
            'add_new_item'       => __('Add New PDF', 'patlis-menu'),
            'edit_item'          => __('Edit PDF', 'patlis-menu'),
            'new_item'           => __('New PDF', 'patlis-menu'),
            'view_item'          => __('View PDF', 'patlis-menu'),
            'search_items'       => __('Search PDFs', 'patlis-menu'),
            'not_found'          => __('No PDFs found', 'patlis-menu'),
            'not_found_in_trash' => __('No PDFs found in trash', 'patlis-menu'),
            'all_items'          => __('All PDFs', 'patlis-menu'),
        ],

        'public'              => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'has_archive'         => false,
        'rewrite'             => false,
        'query_var'           => false,
        'show_in_nav_menus'   => false,

        'show_ui'             => true,
        'show_in_rest'        => true,

        'supports'            => ['title'],
        'show_in_menu'        => false,
    ]);

    // Taxonomy: Menu Categories
    register_taxonomy('menu_section', ['menu_item'], [
        'labels' => [
            'name'          => __('Menu categories', 'patlis-menu'),
            'singular_name' => __('Menu category', 'patlis-menu'),
            'search_items'  => __('Search categories', 'patlis-menu'),
            'all_items'     => __('All categories', 'patlis-menu'),
            'edit_item'     => __('Edit category', 'patlis-menu'),
            'update_item'   => __('Update category', 'patlis-menu'),
            'add_new_item'  => __('Add new category', 'patlis-menu'),
            'new_item_name' => __('New category', 'patlis-menu'),
            'menu_name'     => __('Menu categories', 'patlis-menu'),
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
