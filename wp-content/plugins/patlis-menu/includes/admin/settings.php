<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Menu_Admin_Settings {

  public static function init(): void {
    add_action('admin_post_patlis_menu_save_options', [__CLASS__, 'handle_save_options']);
  }

  public static function handle_save_options(): void {
    if (!current_user_can('patlis_manage')) {wp_die('Not allowed.');}
    check_admin_referer('patlis_menu_save_options');

    $raw = isset($_POST[Patlis_Menu_Admin_Page_Options::OPTION_NAME])
      ? wp_unslash($_POST[Patlis_Menu_Admin_Page_Options::OPTION_NAME])
      : [];
      
    if (!is_array($raw)) $raw = [];

    $clean = Patlis_Menu_Admin_Page_Options::sanitize($raw);

    update_option(Patlis_Menu_Admin_Page_Options::OPTION_NAME, $clean);

    wp_safe_redirect(admin_url('admin.php?page=patlis-menu&patlis_saved=1'));
    exit;
  }
}

Patlis_Menu_Admin_Settings::init();