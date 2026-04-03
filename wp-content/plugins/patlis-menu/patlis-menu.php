<?php
/**
 * Plugin Name: Patlis Menu
 * Description: Menu module for gastro/dining sites.
 * Version: 0.1.0
 * Author: Patlis Ioannis
 * Text Domain: patlis-menu
 * Update URI: https://updates.patlis.com/patlis-menu/
 */

if (!defined('ABSPATH')) { exit; }

define('PATLIS_MENU_PATH', plugin_dir_path(__FILE__));
define('PATLIS_MENU_URL', plugin_dir_url(__FILE__));
define('PATLIS_MENU_VERSION', '0.1.0');

// Updater — πριν το gating ωστε να ελεγχει updates παντα
if (function_exists('patlis_register_plugin_updater')) {
    patlis_register_plugin_updater(__FILE__, 'patlis-menu', PATLIS_MENU_VERSION);
}

/**
 * Load only on gastro OR dining.
 * Uses MU-plugin functions: patlis_version_has_gastro(), patlis_version_has_dining()
 */
if (
    !function_exists('patlis_version_has_gastro') ||
    !function_exists('patlis_version_has_dining') ||
    (!patlis_version_has_gastro() && !patlis_version_has_dining())
) {
    return;
}

require_once PATLIS_MENU_PATH . 'includes/post-types.php';
require_once PATLIS_MENU_PATH . 'includes/categories.php';
require_once PATLIS_MENU_PATH . 'includes/menu-items.php';
require_once PATLIS_MENU_PATH . 'includes/menu-pdfs.php';
require_once PATLIS_MENU_PATH . 'includes/bricks-tags.php';
require_once PATLIS_MENU_PATH . 'includes/query.php';

if (is_admin()) {
  require_once PATLIS_MENU_PATH . 'includes/admin/menu.php';
  require_once PATLIS_MENU_PATH . 'includes/admin/settings.php'; // ΝΕΟ
  require_once PATLIS_MENU_PATH . 'includes/admin/pages/options.php';
  require_once PATLIS_MENU_PATH . 'includes/admin/bulk-import.php';
  require_once PATLIS_MENU_PATH . 'includes/admin/bulk-edit.php';
}