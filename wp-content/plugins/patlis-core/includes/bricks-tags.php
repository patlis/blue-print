<?php
if (!defined('ABSPATH')) exit;

/**
 * Bricks Dynamic Tags for Patlis
 * - Groups: Patlis – Basic, Patlis – Social, Patlis – Center Pop up, Patlis – Notification Bar
 * - Renders {patlis_*} inside Text/Heading/etc
 */

/* --------------------------------------------------------------------------
 * 1) Show tags in Bricks UI (Dynamic Data list)
 * -------------------------------------------------------------------------- */
add_filter('bricks/dynamic_tags_list', function($tags) {

  $group_basic  = 'Patlis – Basic';
  $group_social = 'Patlis – Social';
  $group_center = 'Patlis – Center Pop up';
  $group_bar    = 'Patlis – Notification Bar';

  // BASIC
  $tags[] = ['name' => '{patlis_company_name}',      'label' => esc_html__('Company name', 'patlis-core'),            'group' => $group_basic];
  $tags[] = ['name' => '{patlis_address}',           'label' => esc_html__('Address', 'patlis-core'),                 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_city}',              'label' => esc_html__('City', 'patlis-core'),                    'group' => $group_basic];
  $tags[] = ['name' => '{patlis_zip}',               'label' => esc_html__('Zip', 'patlis-core'),                     'group' => $group_basic];
  $tags[] = ['name' => '{patlis_email}',             'label' => 'E-mail',                                              'group' => $group_basic];
  $tags[] = ['name' => '{patlis_phone}',             'label' => esc_html__('Phone', 'patlis-core'),                   'group' => $group_basic];
  $tags[] = ['name' => '{patlis_phone2}',            'label' => esc_html__('Phone-2', 'patlis-core'),                 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_mobile}',            'label' => esc_html__('Mobile', 'patlis-core'),                  'group' => $group_basic];
  $tags[] = ['name' => '{patlis_whatsapp}',          'label' => 'WhatsApp',                                            'group' => $group_basic];
  $tags[] = ['name' => '{patlis_cordinates}',        'label' => 'Cordinates',                                          'group' => $group_basic];
  $tags[] = ['name' => '{patlis_show_contact_form}',        'label' => esc_html__('Show contact form (1/0)', 'patlis-core'),  'group' => $group_basic];
  $tags[] = ['name' => '{patlis_opening_show_on_footer}', 'label' => esc_html__('Opening: Show on footer (1/0)', 'patlis-core'), 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_opening_text}',          'label' => esc_html__('Opening: Text (HTML)', 'patlis-core'),          'group' => $group_basic];

  // SOCIAL
  $tags[] = ['name' => '{patlis_facebook}',        'label' => 'Facebook URL',        'group' => $group_social];
  $tags[] = ['name' => '{patlis_instagram}',       'label' => 'Instagram URL',       'group' => $group_social];
  $tags[] = ['name' => '{patlis_youtube}',         'label' => 'YouTube URL',         'group' => $group_social];
  $tags[] = ['name' => '{patlis_tiktok}',          'label' => 'TikTok URL',          'group' => $group_social];
  $tags[] = ['name' => '{patlis_google_business}', 'label' => 'Google Business URL', 'group' => $group_social];
  $tags[] = ['name' => '{patlis_tripadvisor}',     'label' => 'Tripadvisor URL',     'group' => $group_social];
  $tags[] = ['name' => '{patlis_x_com}',           'label' => 'X (Twitter) URL',     'group' => $group_social];

  // CENTER POP UP
  $tags[] = ['name' => '{patlis_center_enabled}',       'label' => esc_html__('Enabled (1/0)', 'patlis-core'),                    'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_show_from}',     'label' => esc_html__('Show from (html/image/video/code)', 'patlis-core'), 'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_title}',         'label' => esc_html__('Title', 'patlis-core'),                            'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_delay_seconds}', 'label' => esc_html__('Delay (seconds)', 'patlis-core'),                  'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_start_date}',    'label' => esc_html__('Start date', 'patlis-core'),                       'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_end_date}',      'label' => esc_html__('End date', 'patlis-core'),                         'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_link_url}',      'label' => esc_html__('Link URL', 'patlis-core'),                         'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_video}',         'label' => esc_html__('Video URL', 'patlis-core'),                        'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_image_id}',      'label' => esc_html__('Image ID', 'patlis-core'),                         'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_image_url}',     'label' => esc_html__('Image URL', 'patlis-core'),                        'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_code}',          'label' => esc_html__('Code', 'patlis-core'),                             'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_html}',          'label' => esc_html__('Html', 'patlis-core'),                             'group' => $group_center];

  // NOTIFICATION BAR
  $tags[] = ['name' => '{patlis_bar_enabled}',    'label' => esc_html__('Enabled (1/0)', 'patlis-core'),  'group' => $group_bar];
  $tags[] = ['name' => '{patlis_bar_text}',       'label' => esc_html__('Text', 'patlis-core'),          'group' => $group_bar];
  $tags[] = ['name' => '{patlis_bar_start_date}', 'label' => esc_html__('Start date', 'patlis-core'),    'group' => $group_bar];
  $tags[] = ['name' => '{patlis_bar_end_date}',   'label' => esc_html__('End date', 'patlis-core'),      'group' => $group_bar];

  return $tags;
});


/* --------------------------------------------------------------------------
 * 2) Render tags inside content (Text, Heading, etc)
 * -------------------------------------------------------------------------- */
add_filter('bricks/dynamic_data/render_content', function($content, $post, $context = 'text') {
  return patlis_render_dynamic_tags_in_content($content);
}, 20, 3);

add_filter('bricks/frontend/render_data', function($content, $post) {
  return patlis_render_dynamic_tags_in_content($content);
}, 20, 2);


function patlis_render_dynamic_tags_in_content($content) {
  if (!is_string($content) || strpos($content, '{patlis_') === false) {
    return $content;
  }

  // map tag -> field key (BASIC)
  $basic_map = [
    'patlis_company_name' => 'company_name',
    'patlis_address'      => 'address',
    'patlis_city'         => 'city',
    'patlis_zip'          => 'zip',
    'patlis_email'        => 'email',
    'patlis_phone'        => 'phone',
    'patlis_phone2'       => 'phone2',
    'patlis_mobile'       => 'mobile',
    'patlis_whatsapp'     => 'whatsapp',
    'patlis_cordinates'   => 'cordinates',
    'patlis_show_contact_form' => 'show_contact_form',
  ];

  // map tag -> field key (SOCIAL)
  $social_map = [
    'patlis_facebook'        => 'facebook',
    'patlis_instagram'       => 'instagram',
    'patlis_youtube'         => 'youtube',
    'patlis_tiktok'          => 'tiktok',
    'patlis_google_business' => 'google_business',
    'patlis_tripadvisor'     => 'tripadvisor',
    'patlis_x_com'           => 'x_com',
  ];

  // map tag -> field key (CENTER POP UP)
  $center_map = [
    'patlis_center_enabled'       => 'enabled',
    'patlis_center_show_from'     => 'show_from',
    'patlis_center_title'         => 'title',
    'patlis_center_delay_seconds' => 'delay_seconds',
    'patlis_center_start_date'    => 'start_date',
    'patlis_center_end_date'      => 'end_date',
    'patlis_center_show_button'   => 'show_button',
    'patlis_center_button_text'   => 'button_text',
    'patlis_center_link_url'      => 'link_url',
    'patlis_center_video'         => 'video',
    'patlis_center_image_id'      => 'image_id',
    'patlis_center_code'          => 'code',
    'patlis_center_html'          => 'html',
  ];

  // map tag -> field key (OPENING)
  $opening_map = [
    'patlis_opening_show_on_footer' => 'show_on_footer',
    'patlis_opening_text'           => 'text',
  ];

  // map tag -> field key (NOTIFICATION BAR)
  $bar_map = [
    'patlis_bar_enabled'    => 'enabled',
    'patlis_bar_text'       => 'text',
    'patlis_bar_start_date' => 'start_date',
    'patlis_bar_end_date'   => 'end_date',
  ];

  return preg_replace_callback('/{(patlis_[a-z0-9_]+)}/i', function($m) use ($basic_map, $social_map, $center_map, $bar_map, $opening_map) {

    $tag = $m[1];

    if (!class_exists('Patlis_Core')) {
      return $m[0];
    }

    // BASIC
    if (isset($basic_map[$tag])) {
      $val = Patlis_Core::get_basic($basic_map[$tag], '');
      return is_scalar($val) ? (string)$val : '';
    }

    // OPENING
    if (isset($opening_map[$tag])) {
      $all = get_option(Patlis_Core::OPTION_OPENING, []);
      if (!is_array($all)) $all = [];

      if ($tag === 'patlis_opening_text') {
        $raw = $all['text'] ?? '';
        if (is_string($raw)) return $raw;
        if (is_array($raw)) {
          $current_lang = function_exists('pll_current_language') ? (string)(pll_current_language('slug') ?? '') : '';
          $default_lang = function_exists('pll_default_language') ? (string)(pll_default_language('slug') ?? '') : '';
          if ($current_lang !== '' && !empty($raw[$current_lang])) return $raw[$current_lang];
          if ($default_lang !== '' && !empty($raw[$default_lang])) return $raw[$default_lang];
          foreach ($raw as $v) { if (is_string($v) && $v !== '') return $v; }
        }
        return '';
      }

      $val = array_key_exists($opening_map[$tag], $all) ? $all[$opening_map[$tag]] : '';
      return is_scalar($val) ? (string)$val : '';
    }

    // SOCIAL (fallback)
    if (isset($social_map[$tag])) {

      if (method_exists('Patlis_Core', 'get_social')) {
        $val = Patlis_Core::get_social($social_map[$tag], '');
        return is_scalar($val) ? (string)$val : '';
      }

      $all = get_option(Patlis_Core::OPTION_SOCIAL, []);
      if (!is_array($all)) $all = [];

      $key = $social_map[$tag];
      $val = array_key_exists($key, $all) ? $all[$key] : '';
      return is_scalar($val) ? (string)$val : '';
    }

    // NOTIFICATION BAR
    if (isset($bar_map[$tag])) {
    
      $all = get_option(Patlis_Core::OPTION_NOTIFICATION_BAR, []);
      if (!is_array($all)) $all = [];
    
      // multilingual text
      if ($tag === 'patlis_bar_text') {
        $raw = $all['text'] ?? '';
    
        // backward compatibility: old format
        if (is_string($raw)) {
          return $raw;
        }
    
        if (is_array($raw)) {
          $current_lang = '';
          $default_lang = '';
    
          if (function_exists('pll_current_language')) {
            $current_lang = pll_current_language('slug');
            if (!is_string($current_lang)) {
              $current_lang = '';
            }
          }
    
          if (function_exists('pll_default_language')) {
            $default_lang = pll_default_language('slug');
            if (!is_string($default_lang)) {
              $default_lang = '';
            }
          }
    
          if ($current_lang !== '' && !empty($raw[$current_lang]) && is_string($raw[$current_lang])) {
            return $raw[$current_lang];
          }
    
          if ($default_lang !== '' && !empty($raw[$default_lang]) && is_string($raw[$default_lang])) {
            return $raw[$default_lang];
          }
    
          foreach ($raw as $value) {
            if (is_string($value) && $value !== '') {
              return $value;
            }
          }
        }
    
        return '';
      }
    
      $key = $bar_map[$tag];
      $val = array_key_exists($key, $all) ? $all[$key] : '';
    
      // Defaults for empty dates
      if ($tag === 'patlis_bar_start_date' && trim((string)$val) === '') {
        return '1900-01-01';
      }
      if ($tag === 'patlis_bar_end_date' && trim((string)$val) === '') {
        return '2100-01-01';
      }
    
      return is_scalar($val) ? (string)$val : '';
    }

    // CENTER POP UP: special case image_url (derived from image_id)
    if ($tag === 'patlis_center_image_url') {
      $all = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
      if (!is_array($all)) $all = [];
      $id = isset($all['image_id']) ? (int)$all['image_id'] : 0;
      $url = $id > 0 ? wp_get_attachment_image_url($id, 'full') : '';
      return is_string($url) ? $url : '';
    }

    // CENTER POP UP
    if (isset($center_map[$tag])) {

      $all = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
      if (!is_array($all)) $all = [];

      $key = $center_map[$tag];
      $val = array_key_exists($key, $all) ? $all[$key] : '';

      // Defaults for empty dates (as requested)
      if ($tag === 'patlis_center_start_date' && trim((string)$val) === '') {
        return '1900-01-01';
      }
      if ($tag === 'patlis_center_end_date' && trim((string)$val) === '') {
        return '2100-01-01';
      }

      if ($tag === 'patlis_center_delay_seconds') {
        return is_numeric($val) ? (string)($val * 1000) : '0';
      }

      return is_scalar($val) ? (string)$val : '';
    }

    return $m[0];

  }, $content);
}


/* --------------------------------------------------------------------------
 * 3) Render tags for Bricks Dynamic Data fields (Image etc)
 * -------------------------------------------------------------------------- */
add_filter('bricks/dynamic_data/render_tag', function($tag, $post, $context = 'text') {

  if (!is_string($tag)) return $tag;

  $clean = str_replace(['{', '}'], '', $tag);

  // Only handle these tags here
  if (
    $clean !== 'patlis_center_image_id' &&
    $clean !== 'patlis_center_image_url' &&
    $clean !== 'patlis_center_start_date' &&
    $clean !== 'patlis_center_end_date' &&
    $clean !== 'patlis_bar_start_date' &&
    $clean !== 'patlis_bar_end_date'
  ) {
    return $tag;
  }

  if (!class_exists('Patlis_Core')) return $tag;

  // NOTIFICATION BAR defaults (dates)
  if ($clean === 'patlis_bar_start_date' || $clean === 'patlis_bar_end_date') {

    $bar = get_option(Patlis_Core::OPTION_NOTIFICATION_BAR, []);
    if (!is_array($bar)) $bar = [];

    if ($clean === 'patlis_bar_start_date') {
      $start = isset($bar['start_date']) ? trim((string)$bar['start_date']) : '';
      return $start === '' ? '1900-01-01' : $start;
    }

    $end = isset($bar['end_date']) ? trim((string)$bar['end_date']) : '';
    return $end === '' ? '2100-01-01' : $end;
  }

  // CENTER POP UP
  $opt = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
  if (!is_array($opt)) $opt = [];

  $id = isset($opt['image_id']) ? (int)$opt['image_id'] : 0;

  // Start date default
  if ($clean === 'patlis_center_start_date') {
    $start = isset($opt['start_date']) ? trim((string)$opt['start_date']) : '';
    return $start === '' ? '1900-01-01' : $start;
  }

  // End date default
  if ($clean === 'patlis_center_end_date') {
    $end = isset($opt['end_date']) ? trim((string)$opt['end_date']) : '';
    return $end === '' ? '2100-01-01' : $end;
  }

  // Image ID
  if ($clean === 'patlis_center_image_id') {
    if ($context === 'image') {
      return $id > 0 ? [$id] : [];
    }
    return $id;
  }

  // Image URL (derived)
  $url = $id > 0 ? wp_get_attachment_image_url($id, 'full') : '';
  if ($context === 'image') {
    return $url ? [$url] : [];
  }
  return $url ?: '';

}, 20, 3);
