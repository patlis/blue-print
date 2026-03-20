<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Core {

  const OPTION_BASIC        = 'patlis_basic';
  const OPTION_SOCIAL       = 'patlis_social';
  const OPTION_OPENING      = 'patlis_opening';
  const OPTION_CENTER_POPUP = 'patlis_center_popup';
  const OPTION_NOTIFICATION_BAR = 'patlis_notification_bar';
  
    // One capability for all Patlis admin pages
  const CAP_MANAGE = 'patlis_manage';

  public static function init(): void {
      
    // Always load settings registration for ALL admin requests (including options.php)
    if (is_admin()) {
      // This file must register ALL register_setting(...) calls via admin_init
      require_once PATLIS_CORE_PATH . 'includes/admin/settings.php';
    }
    
    add_action('admin_menu', ['Patlis_Admin_Menu', 'register']);
    add_shortcode('patlis', [__CLASS__, 'shortcode_patlis']);
    add_filter('body_class', 'patlis_filter_body_classes', 20);

  }

  private static function get_option_value(string $option_name, string $key, $default = '') {
    $all = get_option($option_name, []);
    if (!is_array($all)) return $default;
    return array_key_exists($key, $all) ? $all[$key] : $default;
  }

  public static function get_basic(string $key, $default = '') {
    return self::get_option_value(self::OPTION_BASIC, $key, $default);
  }

  public static function get_center_popup(string $key, $default = '') {
    return self::get_option_value(self::OPTION_CENTER_POPUP, $key, $default);
  }
  
  public static function get_notification_bar(string $key, $default = '') {
    return self::get_option_value(self::OPTION_NOTIFICATION_BAR, $key, $default);
  }


  /**
   * Shortcode: [patlis key="basic.company_name" default=""]
   * Shortcode: [patlis key="center_popup.title" default=""]
   */
  public static function shortcode_patlis($atts): string {
    $atts = shortcode_atts([
      'key' => '',
      'default' => '',
    ], $atts);

    $key = is_string($atts['key']) ? trim($atts['key']) : '';
    $default = is_string($atts['default']) ? $atts['default'] : '';

    if ($key === '') return '';

    if (str_starts_with($key, 'basic.')) {
      $field = substr($key, 6);
      $val = self::get_basic($field, $default);
      return is_scalar($val) ? esc_html((string)$val) : '';
    }

    if (str_starts_with($key, 'center_popup.')) {
      $field = substr($key, 13);
      $val = self::get_center_popup($field, $default);
      return is_scalar($val) ? esc_html((string)$val) : '';
    }

    return '';
  }
}
