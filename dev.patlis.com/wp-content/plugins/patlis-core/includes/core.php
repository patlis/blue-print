<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Core {

  const OPTION_BASIC        = 'patlis_basic';
  const OPTION_SOCIAL       = 'patlis_social';
  const OPTION_OPENING      = 'patlis_opening';
  const OPTION_CENTER_POPUP = 'patlis_center_popup';
  const OPTION_NOTIFICATION_BAR = 'patlis_notification_bar';
  const OPTION_HOMEPAGE         = 'patlis_homepage';
  const OPTION_WHITE_LABEL      = 'patlis_white_label';
  const OPTION_AI_LABELS        = 'patlis_ai_labels';
  const OPTION_DESIGN_OPTIONS   = 'patlis_design_options';
  const HOME_SECTIONS           = ['slides', 'welcome', 'dishes', 'rooms', 'offers', 'experience', 'services', 'events', 'gallery', 'reviews', 'cta'];
  
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
    add_shortcode('patlis_template', [__CLASS__, 'shortcode_patlis_template']);
    add_filter('body_class', 'patlis_filter_body_classes', 20);

    add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_frontend_scripts']);

  }

  public static function enqueue_frontend_scripts(): void {
    $script_path = PATLIS_CORE_PATH . 'assets/js/tracking.min.js';
    $script_version = is_file($script_path) ? (string) filemtime($script_path) : PATLIS_CORE_VERSION;

    wp_enqueue_script(
        'patlis-tracking',
        PATLIS_CORE_URL . 'assets/js/tracking.min.js',
        [],
      $script_version,
        ['strategy' => 'defer', 'in_footer' => true]
    );
  }

  private static function get_option_value(string $option_name, string $key, $default = '') {
    $all = get_option($option_name, []);
    if (!is_array($all)) return $default;
    return array_key_exists($key, $all) ? $all[$key] : $default;
  }

  public static function get_basic(string $key, $default = '') {
    return self::get_option_value(self::OPTION_BASIC, $key, $default);
  }

  public static function get_basic_text(string $key, string $default = ''): string {
    $value = self::get_basic(sanitize_key($key), $default);
    if (is_array($value)) return patlis_core_get_multilang_value($value);
    return is_scalar($value) ? (string) $value : $default;
  }

  public static function get_center_popup(string $key, $default = '') {
    return self::get_option_value(self::OPTION_CENTER_POPUP, $key, $default);
  }
  
  public static function get_notification_bar(string $key, $default = '') {
    return self::get_option_value(self::OPTION_NOTIFICATION_BAR, $key, $default);
  }

  public static function get_homepage(string $key, $default = '') {
    return self::get_option_value(self::OPTION_HOMEPAGE, $key, $default);
  }

  public static function is_home_section_visible(string $section): bool {
    $section = sanitize_key($section);
    if (!in_array($section, self::HOME_SECTIONS, true)) return false;

    $visibility = self::get_homepage('sections_visibility', []);
    if (!is_array($visibility) || !array_key_exists($section, $visibility)) return true;
    return !empty($visibility[$section]);
  }

  public static function get_home_section_order(string $section): int {
    $section = sanitize_key($section);
    if (!in_array($section, self::HOME_SECTIONS, true)) return 0;

    $saved = self::get_homepage('sections_order', []);
    $order = is_array($saved)
      ? array_values(array_unique(array_filter($saved, static fn($slug) => in_array($slug, self::HOME_SECTIONS, true))))
      : [];
    foreach (self::HOME_SECTIONS as $slug) {
      if (!in_array($slug, $order, true)) $order[] = $slug;
    }

    $position = array_search($section, $order, true);
    return $position === false ? 0 : $position + 1;
  }

  public static function get_white_label(string $key, $default = '') {
    return self::get_option_value(self::OPTION_WHITE_LABEL, $key, $default);
  }

  public static function get_section_design_option(string $section, string $key, $default = '') {
    $options = get_option(self::OPTION_DESIGN_OPTIONS, []);
    if (!is_array($options) || !isset($options[$section]) || !is_array($options[$section])) return $default;
    return array_key_exists($key, $options[$section]) ? $options[$section][$key] : $default;
  }

  public static function get_global_design_text(string $key, string $default = ''): string {
    $value = self::get_section_design_option('global', sanitize_key($key), $default);
    if (is_array($value)) return patlis_core_get_multilang_value($value);
    return is_scalar($value) ? (string) $value : $default;
  }

  public static function get_design_registry(): array {
    $registry = require PATLIS_CORE_PATH . 'includes/design-registry.php';
    $registry = is_array($registry) ? $registry : [];

    return apply_filters('patlis/design_options/registry', $registry);
  }

  public static function get_design_pages_registry(): array {
    $registry = require PATLIS_CORE_PATH . 'includes/design-pages-registry.php';
    $registry = is_array($registry) ? $registry : [];

    return apply_filters('patlis/design_pages/registry', $registry);
  }

  public static function get_design_sets_registry(): array {
    $registry = require PATLIS_CORE_PATH . 'includes/design-sets-registry.php';
    $registry = is_array($registry) ? $registry : [];

    return apply_filters('patlis/design_sets/registry', $registry);
  }

  /**
   * Apply exact registry variant keys from sections/pages and an optional color_set.
   * Existing section/page field values are preserved; returns the variant count.
   */
  public static function apply_design_set(string $set_key) {
    $set_key = sanitize_key($set_key);
    $sets = self::get_design_sets_registry();
    if (!isset($sets[$set_key]) || !is_array($sets[$set_key])) {
      return new WP_Error('design_set_not_found', __('The selected design set was not found.', 'patlis-core'));
    }

    $set = $sets[$set_key];
    $applicable = [];

    foreach (['sections' => self::get_design_registry(), 'pages' => self::get_design_pages_registry()] as $group => $design_registry) {
      if (isset($set[$group]) && !is_array($set[$group])) {
        return new WP_Error('invalid_design_set', __('The design set contains an unknown section.', 'patlis-core'));
      }
      foreach (($set[$group] ?? []) as $section_key => $variant_key) {
        if (!is_string($variant_key)) {
          return new WP_Error('invalid_design_set', __('The design set contains an unknown variant.', 'patlis-core'));
        }
        $section_key = sanitize_key((string) $section_key);
        $variant_key = sanitize_key($variant_key);
        if (!isset($design_registry[$section_key]) || !is_array($design_registry[$section_key])) {
          return new WP_Error('invalid_design_set', __('The design set contains an unknown section.', 'patlis-core'));
        }

        $section = $design_registry[$section_key];
        $availability = sanitize_key((string) ($section['availability'] ?? ''));
        if ($availability !== '' && function_exists('allowed_section') && allowed_section($availability) !== 1) continue;

        $variants = isset($section['variants']) && is_array($section['variants']) ? $section['variants'] : [];
        if (!isset($variants[$variant_key]) || !is_array($variants[$variant_key])) {
          return new WP_Error('invalid_design_set', __('The design set contains an unknown variant.', 'patlis-core'));
        }

        $applicable[$section_key] = $variant_key;
      }
    }

    $colors = [];
    if (isset($set['color_set']) && $set['color_set'] !== '') {
      if (!is_string($set['color_set']) || !function_exists('patlis_get_color_palettes_registry') || !class_exists('Patlis_Bricks_Colors')) {
        return new WP_Error('invalid_color_set', __('The selected color set is unavailable or invalid.', 'patlis-core'));
      }
      $palettes = patlis_get_color_palettes_registry();
      $palette = $palettes[$set['color_set']] ?? null;
      if (!is_array($palette) || empty($palette['colors']) || !is_array($palette['colors'])) {
        return new WP_Error('invalid_color_set', __('The selected color set is unavailable or invalid.', 'patlis-core'));
      }
      $editable = Patlis_Bricks_Colors::get_editable_colors();
      if (is_wp_error($editable)) return $editable;
      foreach ($palette['colors'] as $variable => $value) {
        $name = sanitize_key(ltrim((string) $variable, '-'));
        if (!isset($editable['colors'][$name]) || !is_string($value) || patlis_sanitize_css_color($value) === '') {
          return new WP_Error('invalid_color_set', __('The selected color set is unavailable or invalid.', 'patlis-core'));
        }
        $colors[$name] = ['color' => $value];
      }
    }

    if ($applicable === [] && $colors === []) {
      return new WP_Error('empty_design_set', __('No sections from this design set are available.', 'patlis-core'));
    }

    $options = get_option(self::OPTION_DESIGN_OPTIONS, []);
    $options = is_array($options) ? $options : [];
    foreach ($applicable as $section_key => $variant_key) {
      if (!isset($options[$section_key]) || !is_array($options[$section_key])) {
        $options[$section_key] = [];
      }
      $options[$section_key]['variant'] = $variant_key;
    }

    if ($colors !== []) {
      $result = Patlis_Bricks_Colors::update_editable_colors($colors);
      if (is_wp_error($result)) return $result;
    }
    if (!update_option(self::OPTION_DESIGN_OPTIONS, $options) && get_option(self::OPTION_DESIGN_OPTIONS, []) !== $options) {
      return new WP_Error('design_set_save_failed', __('The design variants could not be saved. Colors may already have been applied.', 'patlis-core'));
    }
    do_action('patlis_design_set_applied', $set_key, $applicable);
    return count($applicable);
  }

  public static function get_design_section_by_slug(string $slug): ?array {
    foreach ([self::get_design_registry(), self::get_design_pages_registry()] as $registry) {
      foreach ($registry as $section_key => $section) {
        if (!is_array($section) || ($section['shortcode_slug'] ?? '') !== $slug) continue;

        $section['key'] = $section_key;
        return $section;
      }
    }

    return null;
  }

  public static function get_design_field(string $section, string $field, ?string $variant = null) {
    $registry = self::get_design_registry();
    if (!isset($registry[$section]) || !is_array($registry[$section])) return null;

    $section_definition = $registry[$section];
    $section_fields = isset($section_definition['fields']) && is_array($section_definition['fields'])
      ? $section_definition['fields']
      : [];
    if (isset($section_fields[$field]) && is_array($section_fields[$field])) {
      $definition = $section_fields[$field];
      $type = (string) ($definition['type'] ?? 'text');
      $value = self::get_section_design_option($section, (string) ($definition['option_key'] ?? $field), $definition['default'] ?? null);
      if (in_array($type, ['text', 'textarea'], true) && is_array($value)) {
        $value = patlis_core_get_multilang_value($value);
      }

      return [
        'type'  => $type,
        'value' => $value,
      ];
    }

    $default_variant = sanitize_key((string) ($section_definition['default_variant'] ?? 'default'));
    $explicit_variant = $variant !== null;
    $variant = sanitize_key($variant ?? (string) self::get_section_design_option($section, 'variant', $default_variant));
    $variants = isset($section_definition['variants']) && is_array($section_definition['variants'])
      ? $section_definition['variants']
      : [];

    if (!isset($variants[$variant]) || !is_array($variants[$variant])) {
      if ($explicit_variant) return null;
      $variant = $default_variant;
    }

    $fields = isset($variants[$variant]['fields']) && is_array($variants[$variant]['fields'])
      ? $variants[$variant]['fields']
      : [];
    if (!isset($fields[$field]) || !is_array($fields[$field])) return null;

    $definition = $fields[$field];
    $variant_options = self::get_section_design_option($section, $variant, []);
    if (!is_array($variant_options)) return null;

    $type = (string) ($definition['type'] ?? 'text');
    $value = $variant_options[$definition['option_key'] ?? $field] ?? null;
    if (in_array($type, ['text', 'textarea'], true) && is_array($value)) {
      $value = patlis_core_get_multilang_value($value);
    }

    return [
      'type'  => $type,
      'value' => $value,
    ];
  }

  public static function get_section_template_slug(string $slug): string {
    $slug = sanitize_title($slug);
    $section = self::get_design_section_by_slug($slug);
    if ($section === null) return $slug;

    $default_variant = sanitize_key((string) ($section['default_variant'] ?? 'default'));
    $variant = sanitize_key((string) self::get_section_design_option($section['key'], 'variant', $default_variant));
    $variants = isset($section['variants']) && is_array($section['variants']) ? $section['variants'] : [];

    if (!isset($variants[$variant])) {
      $variant = $default_variant;
    }

    return sanitize_title((string) ($variants[$variant]['template'] ?? $slug));
  }

  public static function get_default_section_template_slug(string $slug): string {
    $slug = sanitize_title($slug);
    $section = self::get_design_section_by_slug($slug);
    if ($section === null) return $slug;

    $default_variant = sanitize_key((string) ($section['default_variant'] ?? 'default'));
    return sanitize_title((string) ($section['variants'][$default_variant]['template'] ?? $slug));
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

  /**
   * Shortcode: [patlis_template slug="top-rooms"]
   */
  public static function shortcode_patlis_template($atts): string {
    $atts = shortcode_atts([
      'slug' => '',
    ], $atts, 'patlis_template');

    $requested_slug = is_string($atts['slug']) ? sanitize_title($atts['slug']) : '';
    $slug = self::get_section_template_slug($requested_slug);

    if ($slug === '' || !shortcode_exists('bricks_template')) return '';

    $template = get_page_by_path($slug, OBJECT, 'bricks_template');

    if (!$template instanceof WP_Post || $template->post_status !== 'publish') {
      $default_slug = self::get_default_section_template_slug($requested_slug);
      if ($default_slug !== $slug) {
        $template = get_page_by_path($default_slug, OBJECT, 'bricks_template');
      }
    }

    if (!$template instanceof WP_Post || $template->post_status !== 'publish') return '';

    return do_shortcode(sprintf('[bricks_template id="%d"]', $template->ID));
  }
}
