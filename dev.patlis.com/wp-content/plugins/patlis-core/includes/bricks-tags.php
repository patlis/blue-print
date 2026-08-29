<?php
if (!defined('ABSPATH')) exit;

/**
 * Bricks Dynamic Tags for Patlis
 * - Groups: Patlis – Basic, Patlis – Social, Patlis – Center Popup, Patlis – Notification bar
 * - Renders {patlis_*} inside Text/Heading/etc
 */

function patlis_get_cta_bg_image_ai_status(): string {
  $options = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
  $attachment_id = isset($options['cta_bg_image_id']) ? (int) $options['cta_bg_image_id'] : 0;

  if ($attachment_id <= 0 || !function_exists('patlis_core_get_attachment_ai_status')) {
    return 'none';
  }

  return patlis_core_get_attachment_ai_status($attachment_id);
}

function patlis_get_center_image_ai_status(): string {
  $options = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
  $attachment_id = isset($options['image_id']) ? (int) $options['image_id'] : 0;

  if ($attachment_id <= 0 || !function_exists('patlis_core_get_attachment_ai_status')) {
    return 'none';
  }

  return patlis_core_get_attachment_ai_status($attachment_id);
}

/* --------------------------------------------------------------------------
 * 1) Show tags in Bricks UI (Dynamic Data list)
 * -------------------------------------------------------------------------- */
add_filter('bricks/dynamic_tags_list', function($tags) {

  $group_basic  = 'Patlis – Basic';
  $group_social = 'Patlis – Social';
  $group_center = 'Patlis – Center Popup';
  $group_bar    = 'Patlis – Notification bar';
  $group_events = 'Patlis – Events';
  $group_services = 'Patlis – Services';
  $group_gallery = 'Patlis – Gallery';

  // BASIC
  $tags[] = ['name' => '{patlis_company_name}',      'label' => 'Company name', 'patlis-core',            'group' => $group_basic];
  $tags[] = ['name' => '{patlis_logo_image_url}',    'label' => 'Logo image URL', 'patlis-core',          'group' => $group_basic];
  $tags[] = ['name' => '{patlis_cta_bg_image_url}',  'label' => 'CTA background image URL', 'patlis-core', 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_cta_bg_image_url_ai_status}', 'label' => 'CTA background image AI status', 'patlis-core', 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_home_video_url}',    'label' => 'Home welcome video URL', 'patlis-core',   'group' => $group_basic];
  $tags[] = ['name' => '{patlis_icon_tag:leaf-solid}', 'label' => 'Icon URL by name (SVG)', 'patlis-core', 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_address}',           'label' => 'Address', 'patlis-core',                 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_city}',              'label' => 'City', 'patlis-core',                    'group' => $group_basic];
  $tags[] = ['name' => '{patlis_zip}',               'label' => 'Zip', 'patlis-core',                     'group' => $group_basic];
  $tags[] = ['name' => '{patlis_email}',             'label' => 'E-mail',                                              'group' => $group_basic];
  $tags[] = ['name' => '{patlis_phone}',             'label' => 'Phone', 'patlis-core',                   'group' => $group_basic];
  $tags[] = ['name' => '{patlis_phone2}',            'label' => 'Phone-2', 'patlis-core',                 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_mobile}',            'label' => 'Mobile', 'patlis-core',                  'group' => $group_basic];
  $tags[] = ['name' => '{patlis_whatsapp}',          'label' => 'WhatsApp',                                            'group' => $group_basic];
  $tags[] = ['name' => '{patlis_cordinates}',        'label' => 'Cordinates',                                          'group' => $group_basic];
  $tags[] = ['name' => '{patlis_show_contact_form}',        'label' => 'Show contact form (1/0)', 'patlis-core',  'group' => $group_basic];
  $tags[] = ['name' => '{patlis_opening_show_on_footer}', 'label' => 'Opening: Show on footer (1/0)', 'patlis-core', 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_opening_text}',          'label' => 'Opening: Text (HTML)', 'patlis-core',          'group' => $group_basic];
  $tags[] = ['name' => '{patlis_contact_form_recipient_email}', 'label' => 'Contact form: Recipient email', 'patlis-core', 'group' => $group_basic];
  $tags[] = ['name' => '{patlis_contact_form_email_subject}',   'label' => 'Contact form: Email subject', 'patlis-core',   'group' => $group_basic];
  $tags[] = ['name' => '{patlis_reviews_featured_count}',       'label' => 'Reviews: Featured count', 'patlis-core',       'group' => $group_basic];

  // HOME PAGE sections order
  $hp_sections = ['welcome','dishes','rooms','offers','experience','services','events','gallery','reviews','cta'];
  $hp_labels   = ['Welcome','Dishes','Rooms','Offers','Experience','Services','Events','Gallery','Reviews','CTA'];
  foreach ($hp_sections as $i => $s) {
    $tags[] = ['name' => '{patlis_section_order_' . $s . '}', 'label' => 'Home section order: ' . $hp_labels[$i], 'group' => $group_basic];
  }

  // SOCIAL
  $tags[] = ['name' => '{patlis_facebook}',        'label' => 'Facebook URL',        'group' => $group_social];
  $tags[] = ['name' => '{patlis_instagram}',       'label' => 'Instagram URL',       'group' => $group_social];
  $tags[] = ['name' => '{patlis_youtube}',         'label' => 'YouTube URL',         'group' => $group_social];
  $tags[] = ['name' => '{patlis_tiktok}',          'label' => 'TikTok URL',          'group' => $group_social];
  $tags[] = ['name' => '{patlis_google_business}', 'label' => 'Google Business URL', 'group' => $group_social];
  $tags[] = ['name' => '{patlis_tripadvisor}',     'label' => 'Tripadvisor URL',     'group' => $group_social];
  $tags[] = ['name' => '{patlis_x_com}',           'label' => 'X (Twitter) URL',     'group' => $group_social];

  // CENTER POP UP
  $tags[] = ['name' => '{patlis_center_enabled}',       'label' => 'Enabled (1/0)', 'patlis-core',                    'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_show_from}',     'label' => 'Display source (html/image/video/code)', 'patlis-core', 'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_title}',         'label' => 'Title', 'patlis-core',                            'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_delay_seconds}', 'label' => 'Delay (seconds)', 'patlis-core',                  'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_start_date}',    'label' => 'Start date', 'patlis-core',                       'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_end_date}',      'label' => 'End date', 'patlis-core',                         'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_link_url}',      'label' => 'Link URL', 'patlis-core',                         'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_video}',         'label' => 'Video URL', 'patlis-core',                        'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_image_id}',      'label' => 'Image ID', 'patlis-core',                         'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_image_id_ai_status}', 'label' => 'Image AI status', 'patlis-core',                 'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_image_url}',     'label' => 'Image URL', 'patlis-core',                        'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_code}',          'label' => 'Code', 'patlis-core',                             'group' => $group_center];
  $tags[] = ['name' => '{patlis_center_html}',          'label' => 'Html', 'patlis-core',                             'group' => $group_center];

  // NOTIFICATION BAR
  $tags[] = ['name' => '{patlis_bar_enabled}',    'label' => 'Enabled (1/0)', 'patlis-core',  'group' => $group_bar];
  $tags[] = ['name' => '{patlis_bar_text}',       'label' => 'Text', 'patlis-core',          'group' => $group_bar];
  $tags[] = ['name' => '{patlis_bar_start_date}', 'label' => 'Start date', 'patlis-core',    'group' => $group_bar];
  $tags[] = ['name' => '{patlis_bar_end_date}',   'label' => 'End date', 'patlis-core',      'group' => $group_bar];

  $tags[] = ['name' => '{patlis_events_gallery_json}', 'label' => 'Events: Gallery JSON (ids + urls + meta)', 'group' => $group_events];
  $tags[] = ['name' => '{patlis_services_gallery_json}', 'label' => 'Services: Gallery JSON (ids + urls + meta)', 'patlis-core', 'group' => $group_services];
  $tags[] = ['name' => '{patlis_gallery_json}', 'label' => 'Gallery: Images JSON (ids + urls + meta)', 'patlis-core', 'group' => $group_gallery];
  $tags[] = ['name' => '{patlis_gallery_all_images_json:gallery}', 'label' => 'Gallery: All images JSON (except Home)', 'patlis-core', 'group' => $group_gallery];
  $tags[] = ['name' => '{patlis_gallery_all_images_json:home}', 'label' => 'Gallery: Home images JSON', 'patlis-core', 'group' => $group_gallery];
  $tags[] = ['name' => '{patlis_home_gallery_json}', 'label' => 'Gallery: Home gallery JSON', 'patlis-core', 'group' => $group_gallery];

  return $tags;
});


/* --------------------------------------------------------------------------
 * 2) Render tags inside content (Text, Heading, etc)
 * -------------------------------------------------------------------------- */
add_filter('bricks/dynamic_data/render_content', function($content, $post, $context = 'text') {
  return patlis_render_dynamic_tags_in_content($content, $post);
}, 20, 3);

add_filter('bricks/frontend/render_data', function($content, $post) {
  return patlis_render_dynamic_tags_in_content($content, $post);
}, 20, 2);

function patlis_icon_tag_build_svg_html(string $icon_name): string {
  $icon_name = sanitize_file_name($icon_name);
  if ($icon_name === '') {
    return '';
  }

  $file = PATLIS_CORE_PATH . 'assets/svg/' . $icon_name . '.svg';
  if (!file_exists($file)) {
    return '';
  }

  $svg_content = file_get_contents($file);
  if (!$svg_content) {
    return '';
  }

  $svg_content = preg_replace('/<\?xml.*?\?>/is', '', $svg_content);
  $svg_content = preg_replace('/<!DOCTYPE.*?>/is', '', $svg_content);
  $svg_content = preg_replace('/<!--(.*?)-->/s', '', $svg_content);
  $svg_content = trim((string) $svg_content);

  if (!preg_match('/<svg\b[^>]*>.*?<\/svg>/is', $svg_content)) {
    return '';
  }

  return $svg_content;
}


function patlis_render_dynamic_tags_in_content($content, $post = null) {
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
    'patlis_contact_form_recipient_email' => 'contact_form_recipient_email',
    'patlis_contact_form_email_subject'   => 'contact_form_email_subject',
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

  return preg_replace_callback('/{(patlis_[a-z0-9_]+(?::[a-z0-9_-]+)?)}/i', function($m) use ($basic_map, $social_map, $center_map, $bar_map, $opening_map, $post) {

    $tag = $m[1];
    $tag_parts = explode(':', $tag, 2);
    $tag_base = $tag_parts[0];
    $tag_arg = isset($tag_parts[1]) ? sanitize_key($tag_parts[1]) : '';

    if (!class_exists('Patlis_Core')) {
      return $m[0];
    }

    if ($tag === 'patlis_events_gallery_json') {
      if (!function_exists('patlis_core_get_events_gallery_items')) {
        return '';
      }

      $post_obj = null;

      if ($post instanceof WP_Post) {
        $post_obj = $post;
      } elseif (is_numeric($post)) {
        $post_obj = get_post((int) $post);
      } else {
        $post_obj = get_post();
      }

      if (!($post_obj instanceof WP_Post) || get_post_type($post_obj) !== 'events') {
        return '';
      }

      return wp_json_encode(patlis_core_get_events_gallery_items((int) $post_obj->ID));
    }

    if ($tag === 'patlis_services_gallery_json') {
      if (!function_exists('patlis_core_get_services_gallery_items')) {
        return '';
      }

      $post_obj = null;

      if ($post instanceof WP_Post) {
        $post_obj = $post;
      } elseif (is_numeric($post)) {
        $post_obj = get_post((int) $post);
      } else {
        $post_obj = get_post();
      }

      if (!($post_obj instanceof WP_Post) || get_post_type($post_obj) !== 'services') {
        return '';
      }

      return wp_json_encode(patlis_core_get_services_gallery_items((int) $post_obj->ID));
    }

    if ($tag === 'patlis_gallery_json') {
      if (!function_exists('patlis_gallery_get_items')) {
        return '';
      }

      $post_obj = null;

      if ($post instanceof WP_Post) {
        $post_obj = $post;
      } elseif (is_numeric($post)) {
        $post_obj = get_post((int) $post);
      } else {
        $post_obj = get_post();
      }

      if (!($post_obj instanceof WP_Post) || get_post_type($post_obj) !== 'patlis_gallery') {
        return '';
      }

      return wp_json_encode(patlis_gallery_get_items((int) $post_obj->ID));
    }

    if ($tag_base === 'patlis_gallery_all_images_json') {
      if (!function_exists('patlis_gallery_get_all_images_items')) {
        return '';
      }

      $scope = $tag_arg !== '' ? $tag_arg : 'gallery';
      return wp_json_encode(patlis_gallery_get_all_images_items($scope));
    }

    if ($tag === 'patlis_home_gallery_json') {
      if (!function_exists('patlis_gallery_get_home_items')) {
        return '';
      }

      return wp_json_encode(patlis_gallery_get_home_items());
    }

    if ($tag_base === 'patlis_icon_tag') {
      if ($tag_arg === '') {
        return '';
      }
      return patlis_icon_tag_build_svg_html($tag_arg);
    }

    // BASIC
    if ($tag === 'patlis_logo_image_url') {
      $id = (int) Patlis_Core::get_basic('logo_image_id', 0);
      $url = $id > 0 ? wp_get_attachment_image_url($id, 'full') : '';
      return is_string($url) ? $url : '';
    }

    if ($tag === 'patlis_cta_bg_image_url') {
      $opt = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
      $id  = isset($opt['cta_bg_image_id']) ? (int)$opt['cta_bg_image_id'] : 0;
      $url = $id > 0 ? wp_get_attachment_image_url($id, 'full') : '';
      return is_string($url) ? $url : '';
    }

    if ($tag === 'patlis_cta_bg_image_url_ai_status') {
      return patlis_get_cta_bg_image_ai_status();
    }

    if ($tag === 'patlis_center_image_id_ai_status') {
      return patlis_get_center_image_ai_status();
    }

    if ($tag === 'patlis_home_video_url') {
      if (function_exists('patlis_get_welcome_video_url')) {
        return (string) patlis_get_welcome_video_url();
      }

      $opt = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
      $url = isset($opt['welcome_video_url']) ? (string) $opt['welcome_video_url'] : '';
      return esc_url_raw($url);
    }

    if ($tag === 'patlis_reviews_featured_count') {
      $q = new WP_Query([
        'post_type'      => 'reviews',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [[
          'key'     => 'review_featured',
          'value'   => '1',
          'compare' => '=',
        ]],
      ]);
      return (string) count($q->posts);
    }

    // HOME PAGE section order tags
    if (strpos($tag, 'patlis_section_order_') === 0) {
      $slug = substr($tag, strlen('patlis_section_order_'));
      $allowed = ['welcome','dishes','rooms','offers','experience','services','events','gallery','reviews','cta'];
      if (!in_array($slug, $allowed, true)) return '';
      $opt   = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
      $order = isset($opt['sections_order']) && is_array($opt['sections_order']) ? $opt['sections_order'] : $allowed;
      $pos   = array_search($slug, $order, true);
      return $pos !== false ? (string)($pos + 1) : (string)(array_search($slug, $allowed, true) + 1);
    }

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

      if ($tag === 'patlis_bar_enabled') {
        return function_exists('patlis_notification_bar_should_show') && patlis_notification_bar_should_show() ? '1' : '0';
      }
    
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

      if ($tag === 'patlis_center_code' || $tag === 'patlis_center_html') {
        $field = $tag === 'patlis_center_code' ? 'code' : 'html';
        $raw = $all[$field] ?? '';

        if (is_string($raw)) {
          return $raw;
        }

        if (is_array($raw)) {
          $current_lang = '';
          $default_lang = '';

          if (function_exists('pll_current_language')) {
            $lang = pll_current_language('slug');
            if (is_string($lang)) {
              $current_lang = $lang;
            }
          }

          if (function_exists('pll_default_language')) {
            $lang = pll_default_language('slug');
            if (is_string($lang)) {
              $default_lang = $lang;
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

      if ($tag === 'patlis_center_link_url') {
        $raw = $all['link_url'] ?? '';

        if (is_string($raw)) {
          return $raw;
        }

        if (is_array($raw)) {
          $current_lang = '';
          $default_lang = '';

          if (function_exists('pll_current_language')) {
            $lang = pll_current_language('slug');
            if (is_string($lang)) {
              $current_lang = $lang;
            }
          }

          if (function_exists('pll_default_language')) {
            $lang = pll_default_language('slug');
            if (is_string($lang)) {
              $default_lang = $lang;
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

      if ($tag === 'patlis_center_title') {
        $raw = $all['title'] ?? '';

        if (is_string($raw)) {
          return $raw;
        }

        if (is_array($raw)) {
          $current_lang = '';
          $default_lang = '';

          if (function_exists('pll_current_language')) {
            $lang = pll_current_language('slug');
            if (is_string($lang)) {
              $current_lang = $lang;
            }
          }

          if (function_exists('pll_default_language')) {
            $lang = pll_default_language('slug');
            if (is_string($lang)) {
              $default_lang = $lang;
            }
          }

          if ($current_lang !== '' && !empty($raw[$current_lang]) && is_string($raw[$current_lang])) {
            return $raw[$current_lang];
          }

          if ($default_lang !== '' && !empty($raw[$default_lang]) && is_string($raw[$default_lang])) {
            return $raw[$default_lang];
          }

          if ($default_lang !== '' && array_key_exists($default_lang, $raw) && is_scalar($raw[$default_lang])) {
            return (string) $raw[$default_lang];
          }
        }

        return '';
      }

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
  $parts = explode(':', $clean, 2);
  $base  = $parts[0];
  $arg   = isset($parts[1]) ? sanitize_key($parts[1]) : '';

  // Only handle these tags here
  if (
    $base !== 'patlis_logo_image_url' &&
    $base !== 'patlis_cta_bg_image_url' &&
    $base !== 'patlis_cta_bg_image_url_ai_status' &&
    $base !== 'patlis_home_video_url' &&
    $base !== 'patlis_icon_tag' &&
    $base !== 'patlis_events_gallery_json' &&
    $base !== 'patlis_services_gallery_json' &&
    $base !== 'patlis_gallery_json' &&
    $base !== 'patlis_gallery_all_images_json' &&
    $base !== 'patlis_home_gallery_json' &&
    $base !== 'patlis_center_image_id' &&
    $base !== 'patlis_center_image_id_ai_status' &&
    $base !== 'patlis_center_image_url' &&
    $base !== 'patlis_center_title' &&
    $base !== 'patlis_center_start_date' &&
    $base !== 'patlis_center_end_date' &&
    $base !== 'patlis_bar_enabled' &&
    $base !== 'patlis_bar_start_date' &&
    $base !== 'patlis_bar_end_date' &&
    $base !== 'patlis_section_order_welcome' &&
    $base !== 'patlis_section_order_dishes' &&
    $base !== 'patlis_section_order_rooms' &&
    $base !== 'patlis_section_order_offers' &&
    $base !== 'patlis_section_order_experience' &&
    $base !== 'patlis_section_order_services' &&
    $base !== 'patlis_section_order_events' &&
    $base !== 'patlis_section_order_gallery' &&
    $base !== 'patlis_section_order_reviews' &&
    $base !== 'patlis_section_order_cta'
  ) {
    return $tag;
  }

  if (!class_exists('Patlis_Core')) return $tag;

  // HOME PAGE section order tags
  $_so_allowed = ['welcome','dishes','rooms','offers','experience','services','events','gallery','reviews','cta'];
  $_so_tags    = [
    'patlis_section_order_welcome', 'patlis_section_order_dishes',  'patlis_section_order_rooms',
    'patlis_section_order_offers',  'patlis_section_order_experience', 'patlis_section_order_services',
    'patlis_section_order_events',  'patlis_section_order_gallery', 'patlis_section_order_reviews',
    'patlis_section_order_cta',
  ];
  if (in_array($base, $_so_tags, true)) {
    $slug  = substr($base, strlen('patlis_section_order_'));
    $hopt  = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
    $order = isset($hopt['sections_order']) && is_array($hopt['sections_order']) ? $hopt['sections_order'] : $_so_allowed;
    $pos   = array_search($slug, $order, true);
    return $pos !== false ? (string)($pos + 1) : (string)(array_search($slug, $_so_allowed, true) + 1);
  }

  // GALLERY
  if ($base === 'patlis_home_gallery_json') {
    if (!function_exists('patlis_gallery_get_home_items')) return '';
    return wp_json_encode(patlis_gallery_get_home_items());
  }

  if ($base === 'patlis_gallery_all_images_json') {
    if (!function_exists('patlis_gallery_get_all_images_items')) return '';
    $scope = $arg !== '' ? $arg : 'gallery';
    return wp_json_encode(patlis_gallery_get_all_images_items($scope));
  }

  if ($base === 'patlis_gallery_json') {
    if (!function_exists('patlis_gallery_get_items')) return '';
    $post_obj = ($post instanceof WP_Post) ? $post : (is_numeric($post) ? get_post((int)$post) : get_post());
    if (!($post_obj instanceof WP_Post) || get_post_type($post_obj) !== 'patlis_gallery') return '';
    return wp_json_encode(patlis_gallery_get_items((int)$post_obj->ID));
  }

  if ($base === 'patlis_events_gallery_json') {
    if (!function_exists('patlis_core_get_events_gallery_items')) return '';
    $post_obj = ($post instanceof WP_Post) ? $post : (is_numeric($post) ? get_post((int)$post) : get_post());
    if (!($post_obj instanceof WP_Post) || get_post_type($post_obj) !== 'events') return '';
    return wp_json_encode(patlis_core_get_events_gallery_items((int)$post_obj->ID));
  }

  if ($base === 'patlis_services_gallery_json') {
    if (!function_exists('patlis_core_get_services_gallery_items')) return '';
    $post_obj = ($post instanceof WP_Post) ? $post : (is_numeric($post) ? get_post((int)$post) : get_post());
    if (!($post_obj instanceof WP_Post) || get_post_type($post_obj) !== 'services') return '';
    return wp_json_encode(patlis_core_get_services_gallery_items((int)$post_obj->ID));
  }

  // BASIC: Logo image URL
  if ($base === 'patlis_logo_image_url') {
    $logoId  = (int) Patlis_Core::get_basic('logo_image_id', 0);
    $logoUrl = $logoId > 0 ? wp_get_attachment_image_url($logoId, 'full') : '';
    if ($context === 'image') return $logoUrl ? [$logoUrl] : [];
    return $logoUrl ?: '';
  }

  // BASIC: CTA background image URL
  if ($base === 'patlis_cta_bg_image_url') {
    $hopt    = get_option(Patlis_Core::OPTION_HOMEPAGE, []);
    $ctaBgId = isset($hopt['cta_bg_image_id']) ? (int)$hopt['cta_bg_image_id'] : 0;
    $ctaBgUrl = $ctaBgId > 0 ? wp_get_attachment_image_url($ctaBgId, 'full') : '';
    if ($context === 'image') return $ctaBgUrl ? [$ctaBgUrl] : [];
    return $ctaBgUrl ?: '';
  }

  if ($base === 'patlis_cta_bg_image_url_ai_status') {
    return patlis_get_cta_bg_image_ai_status();
  }

  // BASIC: Home welcome video URL
  if ($base === 'patlis_home_video_url') {
    $url = function_exists('patlis_get_welcome_video_url') ? (string) patlis_get_welcome_video_url() : '';
    return $url;
  }

  // BASIC: Icon URL from icon name
  if ($base === 'patlis_icon_tag') {
    $icon_name = sanitize_file_name($arg);
    if ($icon_name === '') {
      return '';
    }

    $file = PATLIS_CORE_PATH . 'assets/svg/' . $icon_name . '.svg';
    if (!file_exists($file)) {
      return '';
    }

    if ($context === 'image') {
      return [esc_url(PATLIS_CORE_URL . 'assets/svg/' . $icon_name . '.svg')];
    }

    return patlis_icon_tag_build_svg_html($icon_name);
  }

  // NOTIFICATION BAR
  if ($base === 'patlis_bar_enabled') {
    return function_exists('patlis_notification_bar_should_show') && patlis_notification_bar_should_show() ? '1' : '0';
  }

  if ($base === 'patlis_bar_start_date' || $base === 'patlis_bar_end_date') {
    $bar = get_option(Patlis_Core::OPTION_NOTIFICATION_BAR, []);
    if (!is_array($bar)) $bar = [];
    if ($base === 'patlis_bar_start_date') {
      $start = isset($bar['start_date']) ? trim((string)$bar['start_date']) : '';
      return $start === '' ? '1900-01-01' : $start;
    }
    $end = isset($bar['end_date']) ? trim((string)$bar['end_date']) : '';
    return $end === '' ? '2100-01-01' : $end;
  }

  // CENTER POP UP
  $opt = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
  if (!is_array($opt)) $opt = [];

  if ($base === 'patlis_center_title') {
    $raw = $opt['title'] ?? '';
    if (is_string($raw)) return $raw;
    if (is_array($raw)) {
      $cl = function_exists('pll_current_language') ? (string)(pll_current_language('slug') ?? '') : '';
      $dl = function_exists('pll_default_language')  ? (string)(pll_default_language('slug') ?? '')  : '';
      if ($cl !== '' && !empty($raw[$cl]) && is_string($raw[$cl])) return $raw[$cl];
      if ($dl !== '' && !empty($raw[$dl]) && is_string($raw[$dl])) return $raw[$dl];
      if ($dl !== '' && array_key_exists($dl, $raw) && is_scalar($raw[$dl])) return (string)$raw[$dl];
    }
    return '';
  }

  if ($base === 'patlis_center_start_date') {
    $start = isset($opt['start_date']) ? trim((string)$opt['start_date']) : '';
    return $start === '' ? '1900-01-01' : $start;
  }

  if ($base === 'patlis_center_end_date') {
    $end = isset($opt['end_date']) ? trim((string)$opt['end_date']) : '';
    return $end === '' ? '2100-01-01' : $end;
  }

  $id = isset($opt['image_id']) ? (int)$opt['image_id'] : 0;

  if ($base === 'patlis_center_image_id') {
    if ($context === 'image') return $id > 0 ? [$id] : [];
    return $id;
  }

  if ($base === 'patlis_center_image_id_ai_status') {
    return patlis_get_center_image_ai_status();
  }

  // patlis_center_image_url (derived)
  $url = $id > 0 ? wp_get_attachment_image_url($id, 'full') : '';
  if ($context === 'image') return $url ? [$url] : [];
  return $url ?: '';

}, 20, 3);
