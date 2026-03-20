<?php
/*
Plugin Name: Custom Admin Styles
Description: Add custom CSS to the admin panel.
Author: Ioannis Patlis
*/

function custom_admin_css_enqueue() {
    echo '<style type="text/css">
    :root {
        --main:        #800305;
        --second:      #cc4202;
        --text:        #fff;
        --hover-text:  #000;
    }

    /* ── Main (patlis-basic) ───────────────────────────────── */
    a.toplevel_page_patlis-basic,
    a.toplevel_page_patlis-basic:hover,
    a.toplevel_page_patlis-basic.current,
    a.toplevel_page_patlis-basic.wp-has-current-submenu {
        background-color: var(--main) !important;
        color: var(--text) !important;
    }

    /* ── Secondary colour — all other custom items ─────────── */
    a.toplevel_page_patlis-menu,
    a.toplevel_page_patlis-reservations,
    a.toplevel_page_patlis-cookies,
    a.toplevel_page_patlis-accommodation,
    a.menu-icon-events,
    a.menu-icon-services,
    a.menu-icon-reviews,
    a.menu-icon-gallery_images,
    a.menu-icon-timeline_item {
        background-color: var(--second) !important;
    }

    a.toplevel_page_patlis-menu:hover, a.toplevel_page_patlis-menu.current, a.toplevel_page_patlis-menu.wp-has-current-submenu,
    a.toplevel_page_patlis-reservations:hover, a.toplevel_page_patlis-reservations.current, a.toplevel_page_patlis-reservations.wp-has-current-submenu,
    a.toplevel_page_patlis-cookies:hover,      a.toplevel_page_patlis-cookies.current,      a.toplevel_page_patlis-cookies.wp-has-current-submenu,
    a.toplevel_page_patlis-accommodation:hover, a.toplevel_page_patlis-accommodation.current, a.toplevel_page_patlis-accommodation.wp-has-current-submenu,
    a.menu-icon-events:hover, a.menu-icon-events.current, a.menu-icon-events.wp-has-current-submenu,
    a.menu-icon-services:hover, a.menu-icon-services.current, a.menu-icon-services.wp-has-current-submenu,
    a.menu-icon-reviews:hover, a.menu-icon-reviews.current, a.menu-icon-reviews.wp-has-current-submenu,
    a.menu-icon-gallery_images:hover, a.menu-icon-gallery_images.current, a.menu-icon-gallery_images.wp-has-current-submenu,
    a.menu-icon-timeline_item:hover, a.menu-icon-timeline_item.current, a.menu-icon-timeline_item.wp-has-current-submenu {
        background-color: var(--second) !important;
        color: var(--hover-text) !important;
    }

    /* ── Icon colour on hover/current ──────────────────────── */
    a.toplevel_page_patlis-menu:hover .wp-menu-image, a.toplevel_page_patlis-menu.current .wp-menu-image, a.toplevel_page_patlis-menu.wp-has-current-submenu .wp-menu-image,
    a.toplevel_page_patlis-reservations:hover .wp-menu-image, a.toplevel_page_patlis-reservations.current .wp-menu-image, a.toplevel_page_patlis-reservations.wp-has-current-submenu .wp-menu-image,
    a.toplevel_page_patlis-cookies:hover .wp-menu-image, a.toplevel_page_patlis-cookies.current .wp-menu-image, a.toplevel_page_patlis-cookies.wp-has-current-submenu .wp-menu-image,
    a.toplevel_page_patlis-accommodation:hover .wp-menu-image, a.toplevel_page_patlis-accommodation.current .wp-menu-image, a.toplevel_page_patlis-accommodation.wp-has-current-submenu .wp-menu-image,
    a.menu-icon-events:hover .wp-menu-image, a.menu-icon-events.current .wp-menu-image, a.menu-icon-events.wp-has-current-submenu .wp-menu-image,
    a.menu-icon-services:hover .wp-menu-image, a.menu-icon-services.current .wp-menu-image, a.menu-icon-services.wp-has-current-submenu .wp-menu-image,
    a.menu-icon-reviews:hover .wp-menu-image, a.menu-icon-reviews.current .wp-menu-image, a.menu-icon-reviews.wp-has-current-submenu .wp-menu-image,
    a.menu-icon-gallery_images:hover .wp-menu-image, a.menu-icon-gallery_images.current .wp-menu-image, a.menu-icon-gallery_images.wp-has-current-submenu .wp-menu-image,
    a.menu-icon-timeline_item:hover .wp-menu-image, a.menu-icon-timeline_item.current .wp-menu-image, a.menu-icon-timeline_item.wp-has-current-submenu .wp-menu-image,
    a.toplevel_page_patlis-menu:hover .wp-menu-image::before, a.toplevel_page_patlis-menu.current .wp-menu-image::before, a.toplevel_page_patlis-menu.wp-has-current-submenu .wp-menu-image::before,
    a.toplevel_page_patlis-reservations:hover .wp-menu-image::before, a.toplevel_page_patlis-reservations.current .wp-menu-image::before, a.toplevel_page_patlis-reservations.wp-has-current-submenu .wp-menu-image::before,
    a.toplevel_page_patlis-cookies:hover .wp-menu-image::before, a.toplevel_page_patlis-cookies.current .wp-menu-image::before, a.toplevel_page_patlis-cookies.wp-has-current-submenu .wp-menu-image::before,
    a.toplevel_page_patlis-accommodation:hover .wp-menu-image::before, a.toplevel_page_patlis-accommodation.current .wp-menu-image::before, a.toplevel_page_patlis-accommodation.wp-has-current-submenu .wp-menu-image::before,
    a.menu-icon-events:hover .wp-menu-image::before, a.menu-icon-events.current .wp-menu-image::before, a.menu-icon-events.wp-has-current-submenu .wp-menu-image::before,
    a.menu-icon-services:hover .wp-menu-image::before, a.menu-icon-services.current .wp-menu-image::before, a.menu-icon-services.wp-has-current-submenu .wp-menu-image::before,
    a.menu-icon-reviews:hover .wp-menu-image::before, a.menu-icon-reviews.current .wp-menu-image::before, a.menu-icon-reviews.wp-has-current-submenu .wp-menu-image::before,
    a.menu-icon-gallery_images:hover .wp-menu-image::before, a.menu-icon-gallery_images.current .wp-menu-image::before, a.menu-icon-gallery_images.wp-has-current-submenu .wp-menu-image::before,
    a.menu-icon-timeline_item:hover .wp-menu-image::before, a.menu-icon-timeline_item.current .wp-menu-image::before, a.menu-icon-timeline_item.wp-has-current-submenu .wp-menu-image::before {
        color: var(--hover-text) !important;
    }

    /* ── Body background reset ──────────────────────────────── */
    body.toplevel_page_patlis-basic,
    body.toplevel_page_patlis-menu,
    body.toplevel_page_patlis-reservations,
    body.toplevel_page_patlis-cookies,
    body.toplevel_page_patlis-accommodation,
    body.menu-icon-events,
    body.menu-icon-services,
    body.menu-icon-reviews,
    body.menu-icon-gallery_images,
    body.menu-icon-timeline_item {
        background-color: transparent !important;
    }
    </style>';
}
add_action( 'admin_head', 'custom_admin_css_enqueue' );

