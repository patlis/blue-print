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
      add_action('admin_post_patlis_save_design_global',      [__CLASS__, 'handle_save_design_global']);
      add_action('admin_post_patlis_save_design_colors',      [__CLASS__, 'handle_save_design_colors']);
      add_action('admin_post_patlis_reset_design_colors',     [__CLASS__, 'handle_reset_design_colors']);
      add_action('admin_post_patlis_apply_design_set',        [__CLASS__, 'handle_apply_design_set']);
      add_action('admin_post_patlis_save_design_options',     [__CLASS__, 'handle_save_design_options']);
      add_action('admin_post_patlis_save_white_label',        [__CLASS__, 'handle_save_white_label']);
      add_action('admin_post_patlis_save_ai_labels',          [__CLASS__, 'handle_save_ai_labels']);
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

    public static function handle_save_design_global(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.'); }
      check_admin_referer('patlis_save_design_global');

      $variable_values = isset($_POST['patlis_bricks_variables']) && is_array($_POST['patlis_bricks_variables'])
        ? wp_unslash($_POST['patlis_bricks_variables'])
        : [];
      $variables_result = class_exists('Patlis_Bricks_Variables')
        ? Patlis_Bricks_Variables::update_editable_variables($variable_values)
        : new WP_Error('variables_module_missing', __('The Bricks variables module is missing.', 'patlis-core'));
      if (is_wp_error($variables_result)) {
        wp_safe_redirect(add_query_arg([
          'page'         => 'patlis-design-options',
          'design_type'  => 'global',
          'patlis_error' => $variables_result->get_error_code(),
        ], admin_url('admin.php')));
        exit;
      }

      if (isset($_POST['patlis_logo_image_id']) && is_scalar($_POST['patlis_logo_image_id'])) {
        $basic_options = get_option(Patlis_Core::OPTION_BASIC, []);
        $basic_options = is_array($basic_options) ? $basic_options : [];
        $basic_options['logo_image_id'] = max(0, (int) wp_unslash($_POST['patlis_logo_image_id']));
        update_option(Patlis_Core::OPTION_BASIC, $basic_options);
      }

      if (isset($_POST['patlis_match_header_colors']) && is_scalar($_POST['patlis_match_header_colors'])) {
        $basic_options = get_option(Patlis_Core::OPTION_BASIC, []);
        $basic_options = is_array($basic_options) ? $basic_options : [];
        $basic_options['match_header_colors'] = wp_unslash($_POST['patlis_match_header_colors']) === '1' ? 1 : 0;
        update_option(Patlis_Core::OPTION_BASIC, $basic_options);
      }

      $raw_order = isset($_POST['patlis_sections_order']) && is_array($_POST['patlis_sections_order'])
        ? array_map('sanitize_key', wp_unslash($_POST['patlis_sections_order']))
        : [];
      $allowed_sections = Patlis_Admin_Page_Homepage::DEFAULT_ORDER;
      $sections_order = array_values(array_unique(array_filter(
        $raw_order,
        static fn($section) => in_array($section, $allowed_sections, true)
      )));
      foreach ($allowed_sections as $section) {
        if (!in_array($section, $sections_order, true)) $sections_order[] = $section;
      }

      $homepage = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
      $homepage = is_array($homepage) ? $homepage : [];
      $raw_visibility = isset($_POST['patlis_sections_visibility']) && is_array($_POST['patlis_sections_visibility'])
        ? wp_unslash($_POST['patlis_sections_visibility'])
        : [];
      $existing_visibility = isset($homepage['sections_visibility']) && is_array($homepage['sections_visibility'])
        ? $homepage['sections_visibility']
        : [];
      $sections_visibility = [];
      foreach ($allowed_sections as $section) {
        $sections_visibility[$section] = array_key_exists($section, $raw_visibility)
          ? (!empty($raw_visibility[$section]) ? 1 : 0)
          : (!array_key_exists($section, $existing_visibility) || !empty($existing_visibility[$section]) ? 1 : 0);
      }

      $homepage['sections_order'] = $sections_order;
      $homepage['sections_visibility'] = $sections_visibility;
      update_option(Patlis_Core::OPTION_HOMEPAGE, $homepage);

      wp_safe_redirect(admin_url('admin.php?page=patlis-design-options&design_type=global&patlis_saved=1'));
      exit;
    }

    public static function handle_save_design_colors(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.'); }
      check_admin_referer('patlis_save_design_colors');

      if (!class_exists('Patlis_Bricks_Colors')) {
        wp_die(esc_html__('The Colors module is missing from this Patlis Core installation.', 'patlis-core'));
      }

      $raw = isset($_POST['patlis_bricks_colors'])
        ? wp_unslash($_POST['patlis_bricks_colors'])
        : [];
      $result = Patlis_Bricks_Colors::update_editable_colors($raw);

      $redirect_args = [
        'page'        => 'patlis-design-options',
        'design_type' => 'colors',
      ];
      if (is_wp_error($result)) {
        $redirect_args['patlis_error'] = $result->get_error_code();
      } else {
        $redirect_args['patlis_saved'] = 1;
      }

      wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
      exit;
    }

    public static function handle_reset_design_colors(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.'); }
      check_admin_referer('patlis_reset_design_colors');

      if (!class_exists('Patlis_Bricks_Colors')) {
        wp_die(esc_html__('The Colors module is missing from this Patlis Core installation.', 'patlis-core'));
      }

      $result = Patlis_Bricks_Colors::restore_defaults();
      $redirect_args = [
        'page'        => 'patlis-design-options',
        'design_type' => 'colors',
      ];
      if (is_wp_error($result)) {
        $redirect_args['patlis_error'] = $result->get_error_code();
      } else {
        $redirect_args['patlis_saved'] = 1;
      }

      wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
      exit;
    }

    public static function handle_apply_design_set(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.'); }
      check_admin_referer('patlis_apply_design_set');

      $set_key = isset($_POST['patlis_design_set'])
        ? sanitize_key(wp_unslash((string) $_POST['patlis_design_set']))
        : '';
      $result = Patlis_Core::apply_design_set($set_key);
      $redirect_args = [
        'page'        => 'patlis-design-options',
        'design_type' => 'set',
      ];
      if (is_wp_error($result)) {
        $redirect_args['patlis_error'] = $result->get_error_code();
      } else {
        $redirect_args['patlis_saved'] = 1;
      }

      wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
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

      $cta_bg_image_id   = isset($_POST['patlis_cta_bg_image_id']) ? max(0, (int) $_POST['patlis_cta_bg_image_id']) : 0;
      $welcome_video_url = isset($_POST['patlis_welcome_video_url']) ? esc_url_raw(wp_unslash((string) $_POST['patlis_welcome_video_url'])) : '';

      // Preserve existing option values, only update what we manage
      $existing = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
      if (!is_array($existing)) $existing = [];

      update_option(Patlis_Core::OPTION_HOMEPAGE, array_merge($existing, [
        'cta_bg_image_id'   => $cta_bg_image_id,
        'welcome_video_url' => $welcome_video_url,
      ]));

      wp_safe_redirect(admin_url('admin.php?page=patlis-homepage&patlis_saved=1'));
      exit;
    }

    public static function handle_save_design_options(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.'); }
      check_admin_referer('patlis_save_design_options');

      $raw = isset($_POST[Patlis_Core::OPTION_DESIGN_OPTIONS])
        ? wp_unslash($_POST[Patlis_Core::OPTION_DESIGN_OPTIONS])
        : [];
      $clean = Patlis_Admin_Page_Design_Options::sanitize($raw);

      update_option(Patlis_Core::OPTION_DESIGN_OPTIONS, $clean);

      $active_section = isset($_POST['patlis_design_active_section'])
        ? sanitize_key(wp_unslash((string) $_POST['patlis_design_active_section']))
        : '';
      $active_type = isset($_POST['patlis_design_active_type'])
        ? sanitize_key(wp_unslash((string) $_POST['patlis_design_active_type']))
        : 'global';
      if (!in_array($active_type, ['section', 'page', 'global'], true)) $active_type = 'global';
      $redirect_args = [
        'page'         => 'patlis-design-options',
        'design_type'  => $active_type,
        'patlis_saved' => 1,
      ];
      $active_registry = $active_type === 'page'
        ? Patlis_Core::get_design_pages_registry()
        : Patlis_Core::get_design_registry();
      if (isset($active_registry[$active_section])) {
        $redirect_args['section'] = $active_section;
      }

      wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
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

    public static function handle_save_ai_labels(): void {
      if (!current_user_can('patlis_manage')) { wp_die('Not allowed.'); }
      check_admin_referer('patlis_save_ai_labels');

      $raw = isset($_POST[Patlis_Core::OPTION_AI_LABELS]) ? wp_unslash($_POST[Patlis_Core::OPTION_AI_LABELS]) : [];
      $clean = Patlis_Admin_Page_AI_Labels::sanitize($raw);

      update_option(Patlis_Core::OPTION_AI_LABELS, $clean);

      wp_safe_redirect(admin_url('admin.php?page=patlis-ai-labels&patlis_saved=1'));
      exit;
    }

}

Patlis_Admin_Settings::init();