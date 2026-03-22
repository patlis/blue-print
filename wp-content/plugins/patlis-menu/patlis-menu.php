<?php
/**
 * Plugin Name: Patlis Menu
 * Description: Menu module (items + categories) for gastro/dining sites.
 * Version: 0.3.0
 * Author: Patlis Ioannis
 * Text Domain: patlis-menu
 */

if (!defined('ABSPATH')) {
  exit;
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

define('PATLIS_MENU_PATH', plugin_dir_path(__FILE__));
define('PATLIS_MENU_URL', plugin_dir_url(__FILE__));

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
