<?php
/*
Plugin Name: Custom Admin Styles
Description: Προσθέτει προσαρμοσμένο CSS στο admin panel.
Author: Το Ονομά σας
*/

function custom_admin_css_enqueue() {
    echo '<style type="text/css">
    :root{
        --main:#800305;
        --second:#cc4202;
        --text:#fff
    }

        a.toplevel_page_patlis-basic {background-color: var(--main) !important;}
        a.toplevel_page_patlis-basic:hover, a.toplevel_page_patlis-basic.current, a.toplevel_page_patlis-basic.wp-has-current-submenu {
            background-color: var(--main) !important; color: var(--text) !important;
        }
        
        body.toplevel_page_patlis-basic {background-color: transparent !important;}
        
        /*------------Menu-----------*/
        a.toplevel_page_patlis-menu {background-color: var(--second) !important;}
        a.toplevel_page_patlis-menu:hover, a.toplevel_page_patlis-menu.current, a.toplevel_page_patlis-menu.wp-has-current-submenu {
            background-color: var(--second) !important;  color: var(--text) !important;
        }
        body.toplevel_page_patlis-menu {background-color: transparent !important;}        
        
        
         /*----------Reservations-------------*/
        a.toplevel_page_patlis-reservations {background-color: var(--second) !important;}
        a.toplevel_page_patlis-reservations:hover, a.toplevel_page_patlis-reservations.current, a.toplevel_page_patlis-reservations.wp-has-current-submenu {
            background-color: var(--second) !important; color: var(--text) !important;
        }
        body.toplevel_page_patlis-reservations {background-color: transparent !important;}          
        
         /*----------Events-------------*/
        a.menu-icon-events {background-color: var(--second) !important;}
        a.menu-icon-events:hover, a.menu-icon-events.current, a.menu-icon-events.wp-has-current-submenu {
            background-color: var(--second) !important; color: var(--text) !important;
        }
        body.menu-icon-events {background-color: transparent !important;}          
        
         /*----------Services-------------*/
        a.menu-icon-services {background-color: var(--second) !important;}
        a.menu-icon-services:hover, a.menu-icon-services.current, a.menu-icon-services.wp-has-current-submenu {
            background-color: var(--second) !important; color: var(--text) !important;
        }
        body.menu-icon-services {background-color: transparent !important;}                
        
         /*----------Reviews-------------*/
        a.menu-icon-reviews {background-color: var(--second) !important;}
        a.menu-icon-reviews:hover, a.menu-icon-reviews.current, a.menu-icon-reviews.wp-has-current-submenu {
            background-color: var(--second) !important; color: var(--text) !important;
        }
         /*----------Cookies-------------*/
        a.toplevel_page_patlis-cookies {background-color: var(--second) !important;}
        a.toplevel_page_patlis-cookies:hover, a.toplevel_page_patlis-cookies.current, a.toplevel_page_patlis-cookies.wp-has-current-submenu {
            background-color: var(--second) !important; color: var(--text) !important;
        }        


         /*----------Accomodation-------------*/
        a.toplevel_page_patlis-accommodation {background-color: var(--second) !important;}
        a.toplevel_page_patlis-accommodation:hover, a.toplevel_page_patlis-accommodation.current, a.toplevel_page_patlis-accommodation.wp-has-current-submenu {
            background-color: var(--second) !important; color: var(--text) !important;
        } 
        
        
        /* reset /
        body.menu-icon-reviews {background-color: transparent !important;}        
        

        /* ... οι υπόλοιποι 9 selectors ... */
    </style>';
}
add_action( 'admin_head', 'custom_admin_css_enqueue' );

