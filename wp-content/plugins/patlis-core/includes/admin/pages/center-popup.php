<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_Center_Popup {

  public static function boot(): void {
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
  }

  public static function enqueue_admin_assets($hook): void {
    if (empty($_GET['page']) || $_GET['page'] !== 'patlis-center-popup') return;

    wp_enqueue_media();
    wp_enqueue_script('jquery');
  }

  public static function sanitize($input): array {
    $in = is_array($input) ? $input : [];
    $out = [];

    $out['enabled']       = !empty($in['enabled']) ? 1 : 0;

    $show_from = isset($in['show_from']) ? sanitize_text_field($in['show_from']) : 'html';
    if (!in_array($show_from, ['html', 'image', 'video', 'code'], true)) $show_from = 'html';

    $out['show_from'] = $show_from;

    $out['title']         = self::sanitize_multilang_text_input($in['title'] ?? []);

    $delay = isset($in['delay_seconds']) ? (int)$in['delay_seconds'] : 0;
    if ($delay < 0) $delay = 0;
    $out['delay_seconds'] = $delay;

    $out['start_date']    = isset($in['start_date']) ? sanitize_text_field($in['start_date']) : '';
    $out['end_date']      = isset($in['end_date']) ? sanitize_text_field($in['end_date']) : '';

    $out['link_url']      = self::sanitize_multilang_url_input($in['link_url'] ?? []);

    $out['video']         = isset($in['video']) ? esc_url_raw($in['video']) : '';

    $out['image_id']      = isset($in['image_id']) ? (int)$in['image_id'] : 0;

    $out['code']          = self::sanitize_multilang_code_input($in['code'] ?? []);
    $out['html']          = self::sanitize_multilang_html_input($in['html'] ?? []);

    return $out;
  }

  protected static function sanitize_multilang_text_input($raw): array {
    $existing = self::title_all();
    $out = is_array($existing) ? $existing : [];

    if (is_string($raw)) {
      $default_lang = self::get_default_language();
      $out[$default_lang] = sanitize_text_field($raw);
      return $out;
    }

    if (!is_array($raw)) return $out;

    $editable_langs = array_keys(self::get_languages());

    foreach ($raw as $lang => $value) {
      $lang = is_string($lang) ? sanitize_key($lang) : '';
      if ($lang === '') continue;

      if (!empty($editable_langs) && !in_array($lang, $editable_langs, true)) {
        continue;
      }

      $out[$lang] = is_string($value) ? sanitize_text_field($value) : '';
    }

    return $out;
  }

  protected static function sanitize_multilang_code_input($raw): array {
    $existing = self::code_all();
    $out = is_array($existing) ? $existing : [];

    if (is_string($raw)) {
      $default_lang = self::get_default_language();
      $out[$default_lang] = (string)$raw;
      return $out;
    }

    if (!is_array($raw)) return $out;

    $editable_langs = array_keys(self::get_languages());

    foreach ($raw as $lang => $value) {
      $lang = is_string($lang) ? sanitize_key($lang) : '';
      if ($lang === '') continue;

      if (!empty($editable_langs) && !in_array($lang, $editable_langs, true)) {
        continue;
      }

      $out[$lang] = is_string($value) ? (string)$value : '';
    }

    return $out;
  }

  protected static function sanitize_multilang_html_input($raw): array {
    $existing = self::html_all();
    $out = is_array($existing) ? $existing : [];

    if (is_string($raw)) {
      $default_lang = self::get_default_language();
      $out[$default_lang] = wp_kses_post($raw);
      return $out;
    }

    if (!is_array($raw)) return $out;

    $editable_langs = array_keys(self::get_languages());

    foreach ($raw as $lang => $value) {
      $lang = is_string($lang) ? sanitize_key($lang) : '';
      if ($lang === '') continue;

      if (!empty($editable_langs) && !in_array($lang, $editable_langs, true)) {
        continue;
      }

      $out[$lang] = is_string($value) ? wp_kses_post($value) : '';
    }

    return $out;
  }

  protected static function sanitize_multilang_url_input($raw): array {
    $existing = self::link_all();
    $out = is_array($existing) ? $existing : [];

    if (is_string($raw)) {
      $default_lang = self::get_default_language();
      $out[$default_lang] = esc_url_raw($raw);
      return $out;
    }

    if (!is_array($raw)) return $out;

    $editable_langs = array_keys(self::get_languages());

    foreach ($raw as $lang => $value) {
      $lang = is_string($lang) ? sanitize_key($lang) : '';
      if ($lang === '') continue;

      if (!empty($editable_langs) && !in_array($lang, $editable_langs, true)) {
        continue;
      }

      $out[$lang] = is_string($value) ? esc_url_raw($value) : '';
    }

    return $out;
  }

  public static function title_all(): array {
    $opt = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
    if (!is_array($opt)) $opt = [];

    $raw = $opt['title'] ?? [];

    if (is_string($raw)) {
      $default_lang = self::get_default_language();
      return [$default_lang => $raw];
    }

    if (!is_array($raw)) return [];

    $out = [];

    foreach ($raw as $lang => $value) {
      $lang = is_string($lang) ? sanitize_key($lang) : '';
      if ($lang === '') continue;
      $out[$lang] = is_string($value) ? $value : '';
    }

    return $out;
  }

  public static function code_all(): array {
    $opt = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
    if (!is_array($opt)) $opt = [];

    $raw = $opt['code'] ?? [];

    if (is_string($raw)) {
      $default_lang = self::get_default_language();
      return [$default_lang => $raw];
    }

    if (!is_array($raw)) return [];

    $out = [];

    foreach ($raw as $lang => $value) {
      $lang = is_string($lang) ? sanitize_key($lang) : '';
      if ($lang === '') continue;
      $out[$lang] = is_string($value) ? $value : '';
    }

    return $out;
  }

  public static function html_all(): array {
    $opt = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
    if (!is_array($opt)) $opt = [];

    $raw = $opt['html'] ?? [];

    if (is_string($raw)) {
      $default_lang = self::get_default_language();
      return [$default_lang => $raw];
    }

    if (!is_array($raw)) return [];

    $out = [];

    foreach ($raw as $lang => $value) {
      $lang = is_string($lang) ? sanitize_key($lang) : '';
      if ($lang === '') continue;
      $out[$lang] = is_string($value) ? $value : '';
    }

    return $out;
  }

  public static function link_all(): array {
    $opt = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
    if (!is_array($opt)) $opt = [];

    $raw = $opt['link_url'] ?? [];

    if (is_string($raw)) {
      $default_lang = self::get_default_language();
      return [$default_lang => $raw];
    }

    if (!is_array($raw)) return [];

    $out = [];

    foreach ($raw as $lang => $value) {
      $lang = is_string($lang) ? sanitize_key($lang) : '';
      if ($lang === '') continue;
      $out[$lang] = is_string($value) ? $value : '';
    }

    return $out;
  }

  protected static function get_languages(): array {
    $slugs = [];

    if (function_exists('patlis_get_effective_language_slugs_for_current_user')) {
      $slugs = patlis_get_effective_language_slugs_for_current_user();
    } elseif (function_exists('pll_languages_list')) {
      $slugs = pll_languages_list(['fields' => 'slug']);
    }

    if (is_array($slugs) && !empty($slugs)) {
      $languages = [];

      foreach ($slugs as $slug) {
        if (!is_string($slug) || $slug === '') continue;
        $languages[$slug] = strtoupper($slug);
      }

      if (!empty($languages)) return $languages;
    }

    return ['default' => 'Default'];
  }

  protected static function get_default_language(): string {
    if (function_exists('pll_default_language')) {
      $lang = pll_default_language('slug');
      if (is_string($lang) && $lang !== '') return $lang;
    }

    $languages = self::get_languages();
    $first = array_key_first($languages);

    return is_string($first) ? $first : 'default';
  }

  public static function render(): void {
    if (!current_user_can('patlis_manage')) return;

    $opt = get_option(Patlis_Core::OPTION_CENTER_POPUP, []);
    if (!is_array($opt)) $opt = [];
    $languages = self::get_languages();
    $title_all = self::title_all();
    $code_all = self::code_all();
    $html_all = self::html_all();
    $link_all = self::link_all();

    $image_id = isset($opt['image_id']) ? (int)$opt['image_id'] : 0;
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';

    ?>
    <style>
  .patlis-center-popup-page .form-table th,
  .patlis-center-popup-page .form-table td {
    padding-top: 5px;
    padding-bottom: 0px;
  }

  .patlis-center-popup-page .form-table tr {
    line-height: 1.2;
  }

  .patlis-center-lang-block {
    margin-bottom: 10px;
  }

  .patlis-center-lang-label {
    font-weight: 600;
    margin-bottom: 4px;
  }

  .patlis-tabs-nav {
    margin: 0 0 12px;
  }

  .patlis-tab-panel {
    display: none;
    padding: 12px;
    border: 1px solid #ddd;
    background: #fff;
    margin-bottom: 10px;
  }

  .patlis-tab-panel.active {
    display: block;
  }
  </style>
  
  
    <div class="wrap patlis-center-popup-page">
        <h1><?php esc_html_e('Center Pop up', 'patlis-core'); ?></h1>
        
        <?php if (!empty($_GET['patlis_saved'])): ?>
          <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
        <?php endif; ?>


        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="patlis_save_center_popup">
          <?php wp_nonce_field('patlis_save_center_popup'); ?>

        <table class="form-table" role="presentation">

          <tr>
            <th scope="row"><?php esc_html_e('Enable', 'patlis-core'); ?></th>
            <td>
              <label>
                <input type="checkbox"
                  name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[enabled]"
                  value="1" <?php checked(!empty($opt['enabled'])); ?>>
              </label>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php esc_html_e('Show from', 'patlis-core'); ?></th>
            <td>
              <?php $show_from = $opt['show_from'] ?? 'html'; ?>
              <?php if (!in_array($show_from, ['html', 'image', 'video', 'code'], true)) $show_from = 'html'; ?>
              <select id="patlis_cp_show_from"
                name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[show_from]">
                <option value="html" <?php selected($show_from, 'html'); ?>>Text</option>
                <option value="code" <?php selected($show_from, 'code'); ?>>Html Code</option>
                <option value="image" <?php selected($show_from, 'image'); ?>>Image</option>
                <option value="video" <?php selected($show_from, 'video'); ?>>Video</option>
              </select>
            </td>
          </tr>

          <tr>
            <th scope="row"><?php esc_html_e('Content', 'patlis-core'); ?></th>
            <td>
              <?php $video_url = isset($opt['video']) ? (string)$opt['video'] : ''; ?>

              <div class="patlis-tabs-wrap" id="patlis-cp-tabs">
                <div class="patlis-tabs-nav nav-tab-wrapper">
                  <a href="#" class="nav-tab nav-tab-active" data-tab="basic">Basic Settings</a>
                  <a href="#" class="nav-tab" data-tab="image">Image</a>
                  <a href="#" class="nav-tab" data-tab="video">Video</a>
                  <a href="#" class="nav-tab" data-tab="html">Text</a>
                  <a href="#" class="nav-tab" data-tab="code">Html Code</a>
                </div>

                <div class="patlis-tabs-panels">
              <div class="patlis-tab-panel patlis-panel-basic active" data-panel="basic">
                <p>
                  <label for="patlis_cp_delay"><?php esc_html_e('Delay (Seconds)', 'patlis-core'); ?></label><br>
                  <input id="patlis_cp_delay" type="number" min="0" class="small-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[delay_seconds]"
                    value="<?php echo esc_attr((string)($opt['delay_seconds'] ?? 0)); ?>">
                </p>

                <p>
                  <label for="patlis_cp_start"><?php esc_html_e('Start date', 'patlis-core'); ?></label><br>
                  <input id="patlis_cp_start" type="date"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[start_date]"
                    value="<?php echo esc_attr($opt['start_date'] ?? ''); ?>">
                </p>

                <p>
                  <label for="patlis_cp_end"><?php esc_html_e('Date end', 'patlis-core'); ?></label><br>
                  <input id="patlis_cp_end" type="date"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[end_date]"
                    value="<?php echo esc_attr($opt['end_date'] ?? ''); ?>">
                </p>

                <div class="patlis-center-lang-block">
                  <h3 class="patlis-center-lang-label" ><?php esc_html_e('Title', 'patlis-core'); ?></h3>
                  <?php foreach ($languages as $lang_slug => $lang_label): ?>
                    <?php $value = $title_all[$lang_slug] ?? ''; ?>
                    <div class="patlis-center-lang-block">
                      <div class="patlis-center-lang-label"><?php echo esc_html($lang_label); ?></div>
                      <input id="patlis_cp_title" type="text" class="regular-text"
                        name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[title][<?php echo esc_attr($lang_slug); ?>]"
                        value="<?php echo esc_attr($value); ?>">
                    </div>
                  <?php endforeach; ?>
                </div>
                <hr>

                <div class="patlis-center-lang-block">
                  <h3 class="patlis-center-lang-label" ><?php esc_html_e('Link url', 'patlis-core'); ?></h3>
                  <?php foreach ($languages as $lang_slug => $lang_label): ?>
                    <?php $value = $link_all[$lang_slug] ?? ''; ?>
                    <div class="patlis-center-lang-block">
                      <div class="patlis-center-lang-label"><?php echo esc_html($lang_label); ?></div>
                      <input id="patlis_cp_link" type="text" class="regular-text"
                        name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[link_url][<?php echo esc_attr($lang_slug); ?>]"
                        value="<?php echo esc_attr($value); ?>">
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="patlis-tab-panel patlis-panel-image" data-panel="image">
                <input type="hidden"
                  id="patlis_cp_image_id"
                  name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[image_id]"
                  value="<?php echo esc_attr((string)$image_id); ?>">

                <div id="patlis_cp_image_preview" style="margin: 0 0 10px 0;">
                  <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width: 360px; height: auto;">
                  <?php endif; ?>
                </div>

                <button type="button" class="button" id="patlis_cp_pick_image"><?php esc_html_e('Select image', 'patlis-core'); ?></button>
                <button type="button" class="button" id="patlis_cp_remove_image"><?php esc_html_e('Remove', 'patlis-core'); ?></button>
              </div>

              <div class="patlis-tab-panel patlis-panel-video" data-panel="video">
                <input type="hidden"
                  id="patlis_cp_video_url"
                  name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[video]"
                  value="<?php echo esc_attr($video_url); ?>">

                <div id="patlis_cp_video_preview" style="margin: 0 0 10px 0;">
                  <?php if ($video_url): ?>
                    <video src="<?php echo esc_url($video_url); ?>" controls style="max-height: 150px; max-width: 360px; height: auto;"></video>
                    <div style="margin-top:6px; font-size:12px; opacity:.8;">
                      <?php echo esc_html($video_url); ?>
                    </div>
                  <?php endif; ?>
                </div>

                <button type="button" class="button" id="patlis_cp_pick_video"><?php esc_html_e('Select video', 'patlis-core'); ?></button>
                <button type="button" class="button" id="patlis_cp_remove_video"><?php esc_html_e('Remove', 'patlis-core'); ?></button>
              </div>

              <div class="patlis-tab-panel patlis-panel-html" data-panel="html">
                <?php foreach ($languages as $lang_slug => $lang_label): ?>
                  <?php $content = wp_unslash($html_all[$lang_slug] ?? ''); ?>
                  <div class="patlis-center-lang-block">
                    <div class="patlis-center-lang-label"><?php echo esc_html($lang_label); ?></div>
                    <?php
                    wp_editor($content, 'patlis_cp_html_editor_' . esc_attr($lang_slug), [
                      'textarea_name' => Patlis_Core::OPTION_CENTER_POPUP . '[html][' . $lang_slug . ']',
                      'textarea_rows' => 5,
                      'media_buttons' => true,
                      'teeny'         => false,
                    ]);
                    ?>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="patlis-tab-panel patlis-panel-code" data-panel="code">
                <?php foreach ($languages as $lang_slug => $lang_label): ?>
                  <?php $value = $code_all[$lang_slug] ?? ''; ?>
                  <div class="patlis-center-lang-block">
                    <div class="patlis-center-lang-label"><?php echo esc_html($lang_label); ?></div>
                    <textarea id="patlis_cp_code" rows="5" class="large-text code"
                      name="<?php echo esc_attr(Patlis_Core::OPTION_CENTER_POPUP); ?>[code][<?php echo esc_attr($lang_slug); ?>]"><?php echo esc_textarea(wp_unslash($value)); ?></textarea>
                  </div>
                <?php endforeach; ?>
              </div>
                </div>
              </div>

              <script>
              jQuery(function($){
                var tabsWrap = document.getElementById('patlis-cp-tabs');
                if (tabsWrap) {
                  var tabs = tabsWrap.querySelectorAll('.nav-tab');
                  var panels = tabsWrap.querySelectorAll('.patlis-tab-panel');

                  tabs.forEach(function(tab){
                    tab.addEventListener('click', function(e){
                      e.preventDefault();

                      var key = tab.getAttribute('data-tab') || 'basic';

                      tabs.forEach(function(t){
                        t.classList.remove('nav-tab-active');
                      });

                      panels.forEach(function(panel){
                        panel.classList.remove('active');
                      });

                      tab.classList.add('nav-tab-active');
                      var activePanel = tabsWrap.querySelector('.patlis-tab-panel[data-panel="' + key + '"]');
                      if (activePanel) {
                        activePanel.classList.add('active');
                      }
                    });
                  });
                }

                var videoFrame;
                $('#patlis_cp_pick_video').on('click', function(e){
                  e.preventDefault();

                  if (videoFrame) { videoFrame.open(); return; }

                  videoFrame = wp.media({
                    title: 'Select video',
                    button: { text: 'Use video' },
                    multiple: false,
                    library: { type: 'video' }
                  });

                  videoFrame.on('select', function(){
                    var attachment = videoFrame.state().get('selection').first().toJSON();
                    var url = attachment.url || '';

                    $('#patlis_cp_video_url').val(url);

                    if (url) {
                      $('#patlis_cp_video_preview').html(
                        '<video src="'+ url +'" controls style="max-width: 360px; height: auto;"></video>' +
                        '<div style="margin-top:6px; font-size:12px;">'+ url +'</div>'
                      );
                    } else {
                      $('#patlis_cp_video_preview').html('');
                    }
                  });

                  videoFrame.open();
                });

                $('#patlis_cp_remove_video').on('click', function(e){
                  e.preventDefault();
                  $('#patlis_cp_video_url').val('');
                  $('#patlis_cp_video_preview').html('');
                });

                var frame;
                $('#patlis_cp_pick_image').on('click', function(e){
                  e.preventDefault();

                  if (frame) { frame.open(); return; }

                  frame = wp.media({
                    title: 'Select image',
                    button: { text: 'Use image' },
                    multiple: false
                  });

                  frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#patlis_cp_image_id').val(attachment.id);
                    var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                    $('#patlis_cp_image_preview').html('<img src="'+ url +'" alt="" style="max-width: 240px; height: auto;">');
                  });

                  frame.open();
                });

                $('#patlis_cp_remove_image').on('click', function(e){
                  e.preventDefault();
                  $('#patlis_cp_image_id').val('');
                  $('#patlis_cp_image_preview').html('');
                });
              });
              </script>
            </td>
          </tr>

        </table>

        <?php submit_button('Save'); ?>
      </form>
      
      <p style="color:red">This popup is shown only after the cookie banner has been completed (consent flag stored in browser storage). If you change/replace your cookie banner, please let us know so we can adjust the popup condition accordingly.</p>
    </div>
    <?php
  }
}

Patlis_Admin_Page_Center_Popup::boot();

