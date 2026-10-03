<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Bricks_Variables {
  private const OPTION = 'bricks_global_variables';
  private const VARIABLES = [
    'btn-radius' => ['min' => 0, 'max' => 100, 'default' => 0, 'step' => 1],
    'font-size'  => ['min' => 15, 'max' => 20, 'default' => 16, 'step' => 1],
    'site-width' => ['min' => 1200, 'max' => 1920, 'default' => 1440, 'step' => 20],
    'logo-height' => ['min' => 20, 'max' => 200, 'default' => 50, 'step' => 1],
    'logo-padding' => ['min' => 0, 'max' => 50, 'default' => 10, 'step' => 1],
    'card-image-radius' => ['min' => 0, 'max' => 40, 'default' => 8, 'step' => 1],
  ];
  private const FONT_VARIABLES = ['font-family', 'headers-font-family'];
  private const FONTS = ['Roboto', 'Inter', 'Open Sans', 'Lato', 'Noto Serif', 'Serif'];

  public static function get_variable_definitions(): array {
    return self::VARIABLES;
  }

  public static function get_font_options(): array {
    return self::FONTS;
  }

  public static function get_editable_variables() {
    $data = get_option(self::OPTION, []);
    if (!is_array($data)) {
      return new WP_Error('invalid_variable_data', __('The Bricks global variables data is invalid.', 'patlis-core'));
    }

    $variables = isset($data['variables']) && is_array($data['variables'])
      ? $data['variables']
      : $data;

    $values = [];
    foreach ($variables as $variable) {
      if (!is_array($variable)) continue;
      $name = (string) ($variable['name'] ?? '');
      if (in_array($name, self::FONT_VARIABLES, true)) {
        $font = trim(trim((string) ($variable['value'] ?? '')), '"\'');
        $values[$name] = in_array($font, self::FONTS, true) ? $font : self::FONTS[0];
        continue;
      }
      if (!isset(self::VARIABLES[$name])) continue;

      if (!preg_match('/^(\d+(?:\.\d+)?)px$/', trim((string) ($variable['value'] ?? '')), $matches)) {
        return new WP_Error('invalid_variable_value', sprintf(__('The Bricks %s variable must use a pixel value.', 'patlis-core'), $name));
      }

      $definition = self::VARIABLES[$name];
      $values[$name] = max($definition['min'], min($definition['max'], (float) $matches[1]));
    }

    $editable_names = array_merge(array_keys(self::VARIABLES), self::FONT_VARIABLES);
    $missing = array_diff($editable_names, array_keys($values));
    if ($missing !== []) {
      return new WP_Error('variable_not_found', sprintf(__('The following Bricks variables were not found: %s.', 'patlis-core'), implode(', ', $missing)));
    }

    return $values;
  }

  public static function update_editable_variables($input) {
    if (!is_array($input)) {
      return new WP_Error('invalid_variable_values', __('No valid Bricks variable values were submitted.', 'patlis-core'));
    }

    $values = [];
    foreach (self::VARIABLES as $name => $definition) {
      if (!isset($input[$name]) || !is_numeric($input[$name])) {
        return new WP_Error('invalid_variable_value', sprintf(__('The %s value must be a number.', 'patlis-core'), $name));
      }
      $values[$name] = max($definition['min'], min($definition['max'], (float) $input[$name]));
    }
    foreach (self::FONT_VARIABLES as $name) {
      $font = isset($input[$name]) ? sanitize_text_field((string) $input[$name]) : '';
      if (!in_array($font, self::FONTS, true)) {
        return new WP_Error('invalid_variable_value', sprintf(__('The %s value must be a supported font.', 'patlis-core'), $name));
      }
      $values[$name] = $font;
    }

    $data = get_option(self::OPTION, []);
    if (!is_array($data)) {
      return new WP_Error('invalid_variable_data', __('The Bricks global variables data is invalid.', 'patlis-core'));
    }

    $variables =& $data;
    if (isset($data['variables']) && is_array($data['variables'])) {
      $variables =& $data['variables'];
    }

    $found = [];
    foreach ($variables as &$variable) {
      if (!is_array($variable)) continue;
      $name = (string) ($variable['name'] ?? '');
      if (!array_key_exists($name, $values)) continue;

      $variable['value'] = in_array($name, self::FONT_VARIABLES, true)
        ? '"' . $values[$name] . '"'
        : rtrim(rtrim(number_format($values[$name], 2, '.', ''), '0'), '.') . 'px';
      $found[$name] = true;
    }
    unset($variable);

    $editable_names = array_merge(array_keys(self::VARIABLES), self::FONT_VARIABLES);
    $missing = array_diff($editable_names, array_keys($found));
    if ($missing !== []) {
      return new WP_Error('variable_not_found', sprintf(__('The following Bricks variables were not found: %s.', 'patlis-core'), implode(', ', $missing)));
    }

    update_option(self::OPTION, $data);
    do_action('patlis_bricks_variables_updated', $values);
    return true;
  }
}