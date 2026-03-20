<?php
if (!defined('ABSPATH')) exit;

function patlis_reservations_page_slug(): string
{
    return 'patlis-reservations';
}

add_action('admin_menu', function () {
     $capability  = 'patlis_manage';

    add_menu_page(
        'Patlis Reservations',                 // Page title
        'My Reservations',                 // Menu title (άλλαξέ το σε "My Reservation" αν θες)
         $capability,
        patlis_reservations_page_slug(),
        'patlis_reservations_render_settings_page', // αυτή η function είναι στο pages/settings.php
        'dashicons-calendar-alt',
        29
    );
});
