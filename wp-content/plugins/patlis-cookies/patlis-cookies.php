<?php
/**
 * Plugin Name: Patlis Cookies
 * Description: Cookie banner + blocking (iframes & external scripts) for Patlis sites.
 * Version: 0.1.0
 * Author: Patlis Ioannis
 * Text Domain: patlis-cookies
 */

if (!defined('ABSPATH')) exit;

define('PATLIS_COOKIES_PATH', plugin_dir_path(__FILE__));
define('PATLIS_COOKIES_URL',  plugin_dir_url(__FILE__));
require_once PATLIS_COOKIES_PATH . 'includes/admin/settings.php';

require_once PATLIS_COOKIES_PATH . 'includes/public/hooks.php';
require_once PATLIS_COOKIES_PATH . 'includes/frontend-integrations.php';



  require_once PATLIS_COOKIES_PATH . 'includes/admin/menu.php';
  require_once PATLIS_COOKIES_PATH . 'includes/admin/pages/integrations.php';
  require_once PATLIS_COOKIES_PATH . 'includes/admin/pages/texts.php';
/*
if (is_admin()) {
  require_once PATLIS_COOKIES_PATH . 'includes/admin/menu.php';
  require_once PATLIS_COOKIES_PATH . 'includes/admin/pages/integrations.php';
  require_once PATLIS_COOKIES_PATH . 'includes/admin/pages/texts.php';
}
*/
