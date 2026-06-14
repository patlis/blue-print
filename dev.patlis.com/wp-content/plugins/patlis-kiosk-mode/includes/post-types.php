<?php
/**
 * Register CPT for Kiosk Slides.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Custom Post Type Kiosk Slide
 */
function patlis_register_kiosk_slide_cpt() {

	$labels = array(
		'name'                  => _x( 'Kiosk Slides', 'Post Type General Name', 'patlis-kiosk-mode' ),
		'singular_name'         => _x( 'Kiosk Slide', 'Post Type Singular Name', 'patlis-kiosk-mode' ),
		'menu_name'             => __( 'Kiosk Slides', 'patlis-kiosk-mode' ),
		'name_admin_bar'        => __( 'Kiosk Slide', 'patlis-kiosk-mode' ),
		'archives'              => __( 'Slide Archives', 'patlis-kiosk-mode' ),
		'attributes'            => __( 'Slide Attributes', 'patlis-kiosk-mode' ),
		'parent_item_colon'     => __( 'Parent Slide:', 'patlis-kiosk-mode' ),
		'all_items'             => __( 'All Slides', 'patlis-kiosk-mode' ),
		'add_new_item'          => __( 'Add New Slide', 'patlis-kiosk-mode' ),
		'add_new'               => __( 'Add New', 'patlis-kiosk-mode' ),
		'new_item'              => __( 'New Slide', 'patlis-kiosk-mode' ),
		'edit_item'             => __( 'Edit Slide', 'patlis-kiosk-mode' ),
		'update_item'           => __( 'Update Slide', 'patlis-kiosk-mode' ),
		'view_item'             => __( 'View Slide', 'patlis-kiosk-mode' ),
		'view_items'            => __( 'View Slides', 'patlis-kiosk-mode' ),
		'search_items'          => __( 'Search Slide', 'patlis-kiosk-mode' ),
		'not_found'             => __( 'Not found', 'patlis-kiosk-mode' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'patlis-kiosk-mode' ),
		'featured_image'        => __( 'Background/Image', 'patlis-kiosk-mode' ),
		'set_featured_image'    => __( 'Set background/image', 'patlis-kiosk-mode' ),
		'remove_featured_image' => __( 'Remove background/image', 'patlis-kiosk-mode' ),
		'use_featured_image'    => __( 'Use as background/image', 'patlis-kiosk-mode' ),
		'insert_into_item'      => __( 'Insert into slide', 'patlis-kiosk-mode' ),
		'uploaded_to_this_item' => __( 'Uploaded to this slide', 'patlis-kiosk-mode' ),
		'items_list'            => __( 'Slides list', 'patlis-kiosk-mode' ),
		'items_list_navigation' => __( 'Slides list navigation', 'patlis-kiosk-mode' ),
		'filter_items_list'     => __( 'Filter slides list', 'patlis-kiosk-mode' ),
	);
	$args   = array(
		'label'                 => __( 'Kiosk Slide', 'patlis-kiosk-mode' ),
		'description'           => __( 'Slides for Kiosk Mode Displays', 'patlis-kiosk-mode' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'thumbnail', 'page-attributes' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => 'patlis-kiosk-mode',
		'menu_position'         => 10,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'rewrite'               => false,
		'capability_type'       => 'post',
		'show_in_rest'          => true, // Needed for Gutenberg Editor if you want to use it, or ACF Blocks.
	);
	register_post_type( 'kiosk_slide', $args );

}
add_action( 'init', 'patlis_register_kiosk_slide_cpt', 0 );
