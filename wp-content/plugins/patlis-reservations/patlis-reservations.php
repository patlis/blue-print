<?php
/**
 * Plugin Name: Patlis Reservations
 * Description: Reservation module for gastronomy sites (settings + integrations + pro features).
 * Version: 0.1.0
 * Author: Patlis Ioannis
 * Text Domain: patlis-reservations
 */

if (!defined('ABSPATH')) exit;

if (
    !function_exists('patlis_version_has_gastro') ||
    !function_exists('patlis_version_has_dining') ||
    (!patlis_version_has_gastro() && !patlis_version_has_dining())
) {
    return;
}

define('PATLIS_RESERVATIONS_PATH', plugin_dir_path(__FILE__));
define('PATLIS_RESERVATIONS_URL',  plugin_dir_url(__FILE__));

require_once PATLIS_RESERVATIONS_PATH . 'includes/settings.php';
require_once PATLIS_RESERVATIONS_PATH . 'includes/bricks-tags.php';
if (is_admin()) {
    require_once PATLIS_RESERVATIONS_PATH . 'includes/admin/menu.php';
    require_once PATLIS_RESERVATIONS_PATH . 'includes/admin/settings.php';
    require_once PATLIS_RESERVATIONS_PATH . 'includes/admin/pages/settings.php';
}