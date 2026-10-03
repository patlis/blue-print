<?php
if (!defined('ABSPATH')) exit;

function patlis_get_design_color_registry(): array {
    return [
  'notification'      => ['label' => __('Background', 'patlis-core'), 'color' => '#c29f67'],
  'notification-text' => ['label' => __('Text', 'patlis-core'), 'color' => '#fff'],

  'head'              => ['label' => __('Background', 'patlis-core'), 'color' => '#eee6da'],
  'head-text'         => ['label' => __('Text', 'patlis-core'), 'color' => '#111111'],
  'head-link'         => ['label' => __('Link', 'patlis-core'), 'color' => '#171d23'],
  'head-hover'        => ['label' => __('Link hover', 'patlis-core'), 'color' => '#c29f67'],
  'head-active'       => ['label' => __('Active link', 'patlis-core'), 'color' => '#c29f67'],

  'body'              => ['label' => __('Background', 'patlis-core'), 'color' => '#f6f1ea'],
  'body-zebra'        => ['label' => __('Alternate background', 'patlis-core'), 'color' => '#efe7dc'],
  'title'             => ['label' => __('Titles', 'patlis-core'), 'color' => '#111111'],
  'body-text'         => ['label' => __('Text', 'patlis-core'), 'color' => '#242424'],
  'body-link'         => ['label' => __('Link', 'patlis-core'), 'color' => '#c29f67'],
  'body-hover'        => ['label' => __('Link hover', 'patlis-core'), 'color' => '#000'],
  'border'            => ['label' => __('Border', 'patlis-core'), 'color' => '#e3e3e3'],
  'shadow'            => ['label' => __('Shadow', 'patlis-core'), 'color' => 'hsla(0, 0%, 0%, 0.3)'],

  'footer'            => ['label' => __('Background', 'patlis-core'), 'color' => '#e8ddce'],
  'footer-text'       => ['label' => __('Text', 'patlis-core'), 'color' => '#000'],
  'footer-link'       => ['label' => __('Link', 'patlis-core'), 'color' => '#9b7440'],
  'footer-hover'      => ['label' => __('Link hover', 'patlis-core'), 'color' => '#000'],
  'footer-active'     => ['label' => __('Active link', 'patlis-core'), 'color' => '#000'],
    ];
}

function patlis_get_color_palettes_registry(): array {
  $file = __DIR__ . '/colors-registry.php';
  $registry = is_file($file) ? require $file : [];
  $registry = is_array($registry) ? $registry : [];

  return apply_filters('patlis/color_palettes/registry', $registry);
}

  function patlis_get_design_color_groups(): array {
    return [
      __('Notification', 'patlis-core') => [
        'notification',
        'notification-text',
      ],
      __('Header', 'patlis-core') => [
        'head',
        'head-text',
        'head-link',
        'head-hover',
        'head-active',
      ],
      __('Main', 'patlis-core') => [
        'body',
        'body-zebra',
        'title',
        'body-text',
        'body-link',
        'body-hover',
        'border',
        'shadow',
      ],
      __('Footer', 'patlis-core') => [
        'footer',
        'footer-text',
        'footer-link',
        'footer-hover',
        'footer-active',
      ],
    ];
  }

function patlis_sanitize_css_color($value): string {
  $value = trim(sanitize_text_field((string) $value));
  if ($value === '' || strlen($value) > 200) return '';

  if (preg_match('/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)) return $value;
  if (preg_match('/^var\(--[a-zA-Z0-9_-]+\)$/', $value)) return $value;
  if (preg_match('/^(?:rgb|rgba|hsl|hsla|hwb|lab|lch|oklab|oklch|color)\([a-zA-Z0-9\s.,%\/+\-]+\)$/', $value)) return $value;
  if (preg_match('/^(?:transparent|currentColor)$/i', $value)) return $value;

  return '';
}

function patlis_output_design_color_variables(): void {
  $palettes = get_option('bricks_color_palette', []);
  if (!is_array($palettes)) return;

  $registry = patlis_get_design_color_registry();
  $colors = [];

  foreach ($palettes as $palette) {
    if (!is_array($palette) || ($palette['id'] ?? '') !== 'xgdckv') continue;
    if (!isset($palette['colors']) || !is_array($palette['colors'])) return;

    foreach ($palette['colors'] as $color) {
      if (!is_array($color) || isset($color['parent']) || isset($color['type'])) continue;
      if (!preg_match('/^var\(--([a-zA-Z0-9_-]+)\)$/', trim((string) ($color['raw'] ?? '')), $matches)) continue;
      $name = sanitize_key($matches[1]);
      if (!isset($registry[$name])) continue;

      $value = patlis_sanitize_css_color($color['light'] ?? '');
      if ($value !== '') $colors[$name] = $value;
    }
    break;
  }

  if ($colors === []) return;

  echo '<style id="patlis-bricks-colors">';
  echo ':root{';
  foreach ($colors as $name => $value) echo '--' . $name . ':' . $value . ';';
  echo '}';
  echo '</style>';
}
add_action('wp_head', 'patlis_output_design_color_variables', 99);