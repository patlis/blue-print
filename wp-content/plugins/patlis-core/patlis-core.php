<?php
/**
 * Plugin Name: Patlis Core
 * Description: Core settings & helpers for Patlis sites.
 * Version: 0.1.0
 * Author: Patlis Ioannis
 * Text Domain: patlis-core
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('PATLIS_CORE_PATH', plugin_dir_path(__FILE__));
define('PATLIS_CORE_URL',  plugin_dir_url(__FILE__));
define('PATLIS_CORE_VERSION', '0.1.0');

require_once PATLIS_CORE_PATH . 'includes/core.php';
require_once PATLIS_CORE_PATH . 'includes/helpers.php';
require_once PATLIS_CORE_PATH . 'includes/bricks-tags.php';
require_once PATLIS_CORE_PATH . 'includes/languages-visibility.php';

// Updater 
if (function_exists('patlis_register_plugin_updater')) {
    patlis_register_plugin_updater(__FILE__, 'patlis-core', PATLIS_CORE_VERSION);
}

if (is_admin()) {
    require_once PATLIS_CORE_PATH . 'includes/admin/menu.php';

    // settings registration must be loaded on EVERY admin request (also options.php)
    require_once PATLIS_CORE_PATH . 'includes/admin/settings.php';

    // admin pages (UI)
    require_once PATLIS_CORE_PATH . 'includes/admin/pages/basic.php';
    require_once PATLIS_CORE_PATH . 'includes/admin/pages/social.php';
    require_once PATLIS_CORE_PATH . 'includes/admin/pages/center-popup.php';
    require_once PATLIS_CORE_PATH . 'includes/admin/pages/notification-bar.php';
    require_once PATLIS_CORE_PATH . 'includes/admin/pages/opening-times.php';
    require_once PATLIS_CORE_PATH . '/includes/admin/pages/translations.php';
    
    require_once PATLIS_CORE_PATH . '/includes/editor-restrictions.php';
    require_once PATLIS_CORE_PATH . '/includes/admin/admin-columns.php';


}

add_action('plugins_loaded', function () {
  load_plugin_textdomain('patlis-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

Patlis_Core::init();