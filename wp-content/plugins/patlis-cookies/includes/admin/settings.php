<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Cookies_Admin_Settings {

  public static function init(): void {
    add_action('admin_post_patlis_cookies_save_integrations', [__CLASS__, 'save_integrations']);
    add_action('admin_post_patlis_cookies_save_texts', [__CLASS__, 'save_texts']);
  }

  public static function save_integrations(): void {
    if (!current_user_can('patlis_manage')) wp_die('Not allowed.');
    check_admin_referer('patlis_cookies_save_integrations');

    $raw = isset($_POST['patlis_cookies_integrations'])
      ? wp_unslash($_POST['patlis_cookies_integrations'])
      : [];

    if (!is_array($raw)) $raw = [];

    $clean = patlis_cookies_sanitize_integrations($raw);
    update_option('patlis_cookies_integrations', $clean, false);

    wp_safe_redirect(admin_url('admin.php?page=patlis-cookies&patlis_saved=1'));
    exit;
  }

  public static function save_texts(): void {
    if (!current_user_can('patlis_manage')) wp_die('Not allowed.');
    check_admin_referer('patlis_cookies_save_texts');

    $raw = isset($_POST['patlis_cookies_texts'])
      ? wp_unslash($_POST['patlis_cookies_texts'])
      : [];

    if (!is_array($raw)) $raw = [];

    $clean = patlis_cookies_sanitize_texts($raw);
    update_option('patlis_cookies_texts', $clean, false);

    wp_safe_redirect(admin_url('admin.php?page=patlis-cookies-texts&patlis_saved=1'));
    exit;
  }
}

Patlis_Cookies_Admin_Settings::init();