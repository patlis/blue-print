<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    
    $capability  = 'patlis_manage';

    add_menu_page(
        'Cookies',
        'Cookies',
        $capability,
        'patlis-cookies',
        'patlis_cookies_render_integrations_page',
        'dashicons-privacy',
        27
    );

    add_submenu_page(
        'patlis-cookies',
        'Integrations',
        'Integrations',
        $capability,
        'patlis-cookies',
        'patlis_cookies_render_integrations_page'
    );

    add_submenu_page(
        'patlis-cookies',
        'Texts',
        'Texts',
        $capability,
        'patlis-cookies-texts',
        'patlis_cookies_render_texts_page'
    );
});
