<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Bricks_Colors {
  private const OPTION = 'bricks_color_palette';
  private const PALETTE_ID = 'xgdckv';

  public static function get_editable_colors() {
    $palettes = get_option(self::OPTION, []);
    if (!is_array($palettes)) {
      return new WP_Error('invalid_palette_data', __('The Bricks color palette data is invalid.', 'patlis-core'));
    }

    $palette = null;
    foreach ($palettes as $candidate) {
      if (is_array($candidate) && ($candidate['id'] ?? '') === self::PALETTE_ID) {
        $palette = $candidate;
        break;
      }
    }

    if (!is_array($palette) || !isset($palette['colors']) || !is_array($palette['colors'])) {
      return new WP_Error('palette_not_found', __('The Patlis Template colors palette was not found in Bricks.', 'patlis-core'));
    }

    $colors_by_name = [];
    foreach ($palette['colors'] as $color) {
      if (!is_array($color) || isset($color['parent']) || isset($color['type'])) continue;
      $name = self::get_variable_name((string) ($color['raw'] ?? ''));
      if ($name !== '') $colors_by_name[$name] = $color;
    }

    $colors = [];
    $missing = [];
    foreach (patlis_get_design_color_registry() as $name => $definition) {
      if (!isset($colors_by_name[$name])) {
        $missing[] = $name;
        continue;
      }

      $color = $colors_by_name[$name];
      $colors[$name] = [
        'label' => (string) ($definition['label'] ?? $name),
        'raw'   => (string) ($color['raw'] ?? ''),
        'color' => (string) ($color['light'] ?? ''),
      ];
    }

    return ['colors' => $colors, 'missing' => $missing];
  }

  public static function update_editable_colors($input) {
    if (!is_array($input)) {
      return new WP_Error('invalid_colors', __('No valid colors were submitted.', 'patlis-core'));
    }

    $palettes = get_option(self::OPTION, []);
    if (!is_array($palettes)) {
      return new WP_Error('invalid_palette_data', __('The Bricks color palette data is invalid.', 'patlis-core'));
    }

    $registry = patlis_get_design_color_registry();
    $palette_found = false;
    $invalid_variables = [];

    foreach ($palettes as &$palette) {
      if (!is_array($palette) || ($palette['id'] ?? '') !== self::PALETTE_ID) continue;
      if (!isset($palette['colors']) || !is_array($palette['colors'])) break;
      $palette_found = true;

      foreach ($palette['colors'] as &$color) {
        if (!is_array($color) || isset($color['parent']) || isset($color['type'])) continue;
        $name = self::get_variable_name((string) ($color['raw'] ?? ''));
        if ($name === '' || !isset($registry[$name]) || !isset($input[$name]) || !is_array($input[$name])) continue;

        if (!array_key_exists('color', $input[$name])) continue;

        $value = patlis_sanitize_css_color($input[$name]['color']);
        if ($value === '') {
          $invalid_variables[] = $name;
          continue;
        }
        $color['light'] = $value;
      }
      unset($color);
      break;
    }
    unset($palette);

    if (!$palette_found) {
      return new WP_Error('palette_not_found', __('The Patlis Template colors palette was not found in Bricks.', 'patlis-core'));
    }
    if ($invalid_variables !== []) {
      return new WP_Error('invalid_color', __('One or more color values are invalid.', 'patlis-core'));
    }

    update_option(self::OPTION, $palettes);
    do_action('patlis_bricks_colors_updated', self::PALETTE_ID);
    return true;
  }

  public static function restore_defaults() {
    $defaults = [];
    foreach (patlis_get_design_color_registry() as $name => $definition) {
      if (!is_array($definition)) continue;
      $defaults[$name] = [
        'color' => (string) ($definition['color'] ?? ''),
      ];
    }

    return self::update_editable_colors($defaults);
  }

  private static function get_variable_name(string $raw): string {
    if (!preg_match('/^var\(--([a-zA-Z0-9_-]+)\)$/', trim($raw), $matches)) return '';
    return sanitize_key($matches[1]);
  }
}