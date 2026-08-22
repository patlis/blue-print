<?php
/**
 * Plugin Name: Patlis Reservations
 * Description: Reservation module for gastronomy sites (settings + integrations + pro features).
 * Version: 0.1.1
 * Author: Patlis Ioannis
 * Text Domain: patlis-reservations
 * Update URI: https://updates.patlis.com/patlis-reservations/
 */

if (!defined('ABSPATH')) exit;

define('PATLIS_RESERVATIONS_PATH', plugin_dir_path(__FILE__));
define('PATLIS_RESERVATIONS_URL',  plugin_dir_url(__FILE__));
define('PATLIS_RESERVATIONS_VERSION', '0.1.1');

add_action('plugins_loaded', function (): void {
    load_plugin_textdomain('patlis-reservations', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Updater — πριν το gating ωστε να ελεγχει updates παντα
if (function_exists('patlis_register_plugin_updater')) {
    patlis_register_plugin_updater(__FILE__, 'patlis-reservations', PATLIS_RESERVATIONS_VERSION);
}

if (
    !function_exists('patlis_version_has_gastro') ||
    !function_exists('patlis_version_has_dining') ||
    (!patlis_version_has_gastro() && !patlis_version_has_dining())
) {
    return;
}

add_action('wp_enqueue_scripts', function (): void {
    if (!is_page()) {
        return;
    }

    $page_id = (int) get_queried_object_id();
    if ($page_id <= 0) {
        return;
    }

    $taxonomy = function_exists('patlis_version_get_page_template_taxonomy')
        ? patlis_version_get_page_template_taxonomy()
        : 'template';

    if (!taxonomy_exists($taxonomy) || !has_term('reservation', $taxonomy, $page_id)) {
        return;
    }

    wp_enqueue_script(
        'patlis-reservations-calendar',
        PATLIS_RESERVATIONS_URL . 'assets/calendar.js',
        [],
        PATLIS_RESERVATIONS_VERSION,
        true
    );
});

require_once PATLIS_RESERVATIONS_PATH . 'includes/settings.php';
require_once PATLIS_RESERVATIONS_PATH . 'includes/bricks-tags.php';
require_once PATLIS_RESERVATIONS_PATH . 'includes/database.php';
require_once PATLIS_RESERVATIONS_PATH . 'includes/form-handlers.php';

register_activation_hook(__FILE__, 'patlis_reservations_create_or_update_tables');
if (is_admin()) {
    require_once PATLIS_RESERVATIONS_PATH . 'includes/admin/menu.php';
    require_once PATLIS_RESERVATIONS_PATH . 'includes/admin/settings.php';
    require_once PATLIS_RESERVATIONS_PATH . 'includes/admin/pages/settings.php';
}