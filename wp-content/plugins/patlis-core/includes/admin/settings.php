<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Settings {

    public static function init(): void {
      add_action('admin_post_patlis_save_social', [__CLASS__, 'handle_save_social']);
      add_action('admin_post_patlis_save_basic', [__CLASS__, 'handle_save_basic']);
      add_action('admin_post_patlis_save_center_popup', [__CLASS__, 'handle_save_center_popup']);
      add_action('admin_post_patlis_save_notification_bar', [__CLASS__, 'handle_save_notification_bar']);
      add_action('admin_post_patlis_save_opening',           [__CLASS__, 'handle_save_opening']);
      add_action('admin_post_patlis_save_homepage',           [__CLASS__, 'handle_save_homepage']);
      add_action('admin_post_patlis_save_white_label',        [__CLASS__, 'handle_save_white_label']);
    }

    public static function handle_save_social(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.');}
      check_admin_referer('patlis_save_social');

      $raw = isset($_POST[Patlis_Core::OPTION_SOCIAL]) ? wp_unslash($_POST[Patlis_Core::OPTION_SOCIAL]) : [];
      $clean = Patlis_Admin_Page_Social::sanitize($raw);

      update_option(Patlis_Core::OPTION_SOCIAL, $clean);

      wp_safe_redirect(admin_url('admin.php?page=patlis-social&patlis_saved=1'));
      exit;
    }
  
    public static function handle_save_basic(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.');}
      check_admin_referer('patlis_save_basic');
    
      $raw = isset($_POST[Patlis_Core::OPTION_BASIC]) ? wp_unslash($_POST[Patlis_Core::OPTION_BASIC]) : [];
      $clean = Patlis_Admin_Page_Basic::sanitize($raw);
    
      update_option(Patlis_Core::OPTION_BASIC, $clean);
    
      wp_safe_redirect(admin_url('admin.php?page=patlis-basic&patlis_saved=1'));
      exit;
    }

    public static function handle_save_center_popup(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.');}
      check_admin_referer('patlis_save_center_popup');
    
      $raw = isset($_POST[Patlis_Core::OPTION_CENTER_POPUP]) ? wp_unslash($_POST[Patlis_Core::OPTION_CENTER_POPUP]) : [];
      $clean = Patlis_Admin_Page_Center_Popup::sanitize($raw);
    
      update_option(Patlis_Core::OPTION_CENTER_POPUP, $clean);
    
      wp_safe_redirect(admin_url('admin.php?page=patlis-center-popup&patlis_saved=1'));
      exit;
    }

    public static function handle_save_notification_bar(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.');}
      check_admin_referer('patlis_save_notification_bar');
    
      $raw = isset($_POST[Patlis_Core::OPTION_NOTIFICATION_BAR]) ? wp_unslash($_POST[Patlis_Core::OPTION_NOTIFICATION_BAR]) : [];
      $clean = Patlis_Admin_Page_Notification_Bar::sanitize($raw);
    
      update_option(Patlis_Core::OPTION_NOTIFICATION_BAR, $clean);
    
      wp_safe_redirect(admin_url('admin.php?page=patlis-notification-bar&patlis_saved=1'));
      exit;
    }

    public static function handle_save_opening(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.'); }
      check_admin_referer('patlis_save_opening');

      $raw   = isset($_POST[Patlis_Core::OPTION_OPENING]) ? wp_unslash($_POST[Patlis_Core::OPTION_OPENING]) : [];
      $clean = Patlis_Admin_Page_Opening::sanitize($raw);

      update_option(Patlis_Core::OPTION_OPENING, $clean);

      wp_safe_redirect(admin_url('admin.php?page=patlis-opening&patlis_saved=1'));
      exit;
    }

    public static function handle_save_homepage(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.'); }
      check_admin_referer('patlis_save_homepage');

      $raw   = isset($_POST['patlis_sections_order']) && is_array($_POST['patlis_sections_order'])
        ? array_map('sanitize_key', wp_unslash($_POST['patlis_sections_order']))
        : [];

      $allowed = ['welcome','dishes','rooms','offers','experience','services','events','gallery','reviews','cta'];
      $clean   = array_values(array_filter($raw, fn($s) => in_array($s, $allowed, true)));

      // Ensure all sections are present (append missing ones at the end)
      foreach ($allowed as $s) {
        if (!in_array($s, $clean, true)) {
          $clean[] = $s;
        }
      }

      $cta_bg_image_id = isset($_POST['patlis_cta_bg_image_id']) ? max(0, (int) $_POST['patlis_cta_bg_image_id']) : 0;

      // Preserve existing option values, only update what we manage
      $existing = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
      if (!is_array($existing)) $existing = [];

      update_option(Patlis_Core::OPTION_HOMEPAGE, array_merge($existing, [
        'sections_order'  => $clean,
        'cta_bg_image_id' => $cta_bg_image_id,
      ]));

      wp_safe_redirect(admin_url('admin.php?page=patlis-homepage&patlis_saved=1'));
      exit;
    }

    public static function handle_save_white_label(): void {
      if (!current_user_can('manage_options')) { wp_die('Not allowed.'); }
      check_admin_referer('patlis_save_white_label');

      $raw = isset($_POST[Patlis_Core::OPTION_WHITE_LABEL]) ? wp_unslash($_POST[Patlis_Core::OPTION_WHITE_LABEL]) : [];
      $clean = Patlis_Admin_Page_White_Label::sanitize($raw);

      update_option(Patlis_Core::OPTION_WHITE_LABEL, $clean);

      wp_safe_redirect(admin_url('admin.php?page=patlis-white-label&patlis_saved=1'));
      exit;
    }

}

Patlis_Admin_Settings::init();