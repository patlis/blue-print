<?php
if (!defined('ABSPATH')) exit;

/**
 * Bricks Dynamic Tags for Patlis Reservations
 * - Group: Patlis – Reservations
 * - Renders {patlis_res_*} inside Text/Heading/etc
 */

// 1) Εμφάνιση tags στη λίστα Dynamic Data του Bricks
add_filter('bricks/dynamic_tags_list', function($tags) {

  $group = 'Patlis – Reservations';

  $tags[] = ['name' => '{patlis_res_mode}',         'label' => 'Reservation mode (off/simple/embed)', 'group' => $group];
  $tags[] = ['name' => '{patlis_res_min_hours}',    'label' => 'Minimum hours before reservation',    'group' => $group];
  $tags[] = ['name' => '{patlis_res_notify_email}', 'label' => 'Recipient email (selected WP user)',  'group' => $group];
  $tags[] = ['name' => '{patlis_res_min_time}', 'label' => 'Min. time (HH:MM)', 'group' => $group];
  $tags[] = ['name' => '{patlis_res_max_time}', 'label' => 'Max. time (HH:MM)', 'group' => $group];
  $tags[] = ['name' => '{patlis_res_embed_code}',   'label' => '3rd party Code', 'group' => $group];

  return $tags;
});


// 2) Render tags μέσα στο content (Text, Heading, κλπ)
add_filter('bricks/dynamic_data/render_content', function($content, $post, $context = 'text') {
  return patlis_reservations_render_dynamic_tags_in_content($content);
}, 20, 3);

add_filter('bricks/frontend/render_data', function($content, $post) {
  return patlis_reservations_render_dynamic_tags_in_content($content);
}, 20, 2);


function patlis_reservations_render_dynamic_tags_in_content($content) {

  if (!is_string($content) || strpos($content, '{patlis_res_') === false) {
    return $content;
  }

  return preg_replace_callback('/{(patlis_res_[a-z0-9_]+)}/i', function($m) {

    $tag = $m[1];

    // settings function (από settings.php)
    if (!function_exists('patlis_reservations_get_settings')) {
      return $m[0];
    }

    $s = patlis_reservations_get_settings();

    if ($tag === 'patlis_res_mode') {
      return isset($s['mode']) ? (string)$s['mode'] : '';
    }

    if ($tag === 'patlis_res_min_hours') {
      return isset($s['min_hours']) ? (string)((int)$s['min_hours']) : '0';
    }

    if ($tag === 'patlis_res_notify_email') {
      if (function_exists('patlis_reservations_get_notify_email')) {
        return (string) patlis_reservations_get_notify_email();
      }
      return '';
    }
    
    if ($tag === 'patlis_res_embed_code') {
      return isset($s['embed_code']) ? (string)$s['embed_code'] : '';
    }
    
    if ($tag === 'patlis_res_min_time') {
      return isset($s['min_time']) ? (string)$s['min_time'] : '';
    }
    
    if ($tag === 'patlis_res_max_time') {
      return isset($s['max_time']) ? (string)$s['max_time'] : '';
    }

    
    

    return $m[0];

  }, $content);
}
