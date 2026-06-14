<?php
/**
 * Plugin Name: Patlis Reservations
 * Description: Reservation module for gastronomy sites (settings + integrations + pro features).
 * Version: 0.1.0
 * Author: Patlis Ioannis
 * Text Domain: patlis-reservations
 * Update URI: https://updates.patlis.com/patlis-reservations/
 */

if (!defined('ABSPATH')) exit;

define('PATLIS_RESERVATIONS_PATH', plugin_dir_path(__FILE__));
define('PATLIS_RESERVATIONS_URL',  plugin_dir_url(__FILE__));
define('PATLIS_RESERVATIONS_VERSION', '0.1.0');

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

require_once PATLIS_RESERVATIONS_PATH . 'includes/settings.php';
require_once PATLIS_RESERVATIONS_PATH . 'includes/bricks-tags.php';
if (is_admin()) {
    require_once PATLIS_RESERVATIONS_PATH . 'includes/admin/menu.php';
    require_once PATLIS_RESERVATIONS_PATH . 'includes/admin/settings.php';
    require_once PATLIS_RESERVATIONS_PATH . 'includes/admin/pages/settings.php';
}