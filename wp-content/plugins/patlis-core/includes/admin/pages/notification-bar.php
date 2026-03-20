<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_Notification_Bar {

  public static function sanitize($input): array {
    $in = is_array($input) ? $input : [];
    $out = [];

    $out['enabled']    = !empty($in['enabled']) ? 1 : 0;
    $out['start_date'] = isset($in['start_date']) ? sanitize_text_field($in['start_date']) : '';
    $out['end_date']   = isset($in['end_date']) ? sanitize_text_field($in['end_date']) : '';

    $out['text'] = self::sanitize_multilang_text_input($in['text'] ?? []);

    return $out;
  }

  protected static function sanitize_multilang_text_input($raw): array {
    // κρατάμε υπάρχουσες γλώσσες (important!)
    $existing = self::text_all();
    $out = is_array($existing) ? $existing : [];

    if (is_string($raw)) {
      $default_lang = self::get_default_language();
      $out[$default_lang] = sanitize_text_field($raw);
      return $out;
    }

    if (!is_array($raw)) {
      return $out;
    }

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

  public static function get_options(): array {
    $opt = get_option(Patlis_Core::OPTION_NOTIFICATION_BAR, []);
    return is_array($opt) ? $opt : [];
  }

  public static function text(): string {
    $all = self::text_all();

    if (!$all) return '';

    $current_lang = self::get_current_language();

    if (!empty($all[$current_lang])) {
      return $all[$current_lang];
    }

    $default_lang = self::get_default_language();

    if (!empty($all[$default_lang])) {
      return $all[$default_lang];
    }

    foreach ($all as $value) {
      if (!empty($value)) return $value;
    }

    return '';
  }

  public static function text_all(): array {
    $opt = self::get_options();
    $raw = $opt['text'] ?? [];

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

  protected static function get_current_language(): string {
    if (function_exists('pll_current_language')) {
      $lang = pll_current_language('slug');
      if (is_string($lang) && $lang !== '') return $lang;
    }

    return self::get_default_language();
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

    $opt = self::get_options();
    $languages = self::get_languages();
    $text_all = self::text_all();
    ?>

    <style>
      .patlis-notification-bar-page .form-table td {
        padding-top: 5px;
        padding-bottom: 0px;
      }

      .patlis-notification-field-label {
        display: block;
        font-weight: 600;
        margin-bottom: 6px;
      }

      .patlis-notification-lang-block {
        margin-bottom: 16px;
      }

      .patlis-notification-lang-label {
        font-weight: 600;
        margin-bottom: 4px;
      }
    </style>

    <div class="wrap patlis-notification-bar-page">
      <h1><?php esc_html_e('Notification Bar', 'patlis-core'); ?></h1>

      <?php if (!empty($_GET['patlis_saved'])): ?>
        <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
      <?php endif; ?>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="patlis_save_notification_bar">
        <?php wp_nonce_field('patlis_save_notification_bar'); ?>

        <table class="form-table">

          <tr>
            <td>
              <label class="patlis-notification-field-label" for="patlis_nb_enabled"><?php esc_html_e('Enable', 'patlis-core'); ?></label>
              <input type="checkbox"
                id="patlis_nb_enabled"
                name="<?php echo esc_attr(Patlis_Core::OPTION_NOTIFICATION_BAR); ?>[enabled]"
                value="1" <?php checked(!empty($opt['enabled'])); ?>>
            </td>
          </tr>

          <tr>
            <td>
              <span style="font-weight: 600; font-size: 24px;"><?php esc_html_e('Text', 'patlis-core'); ?></span>
              <small> (Recommended: Maximum 50 characters)</small>
              <?php foreach ($languages as $lang_slug => $lang_label): ?>
                <?php
                $value = $text_all[$lang_slug] ?? '';
                ?>
                <div class="patlis-notification-lang-block">
                  <div class="patlis-notification-lang-label"><?php echo esc_html($lang_label); ?></div>
                    
                    <input type="text"
                      class="large-text"
                      name="<?php echo esc_attr(Patlis_Core::OPTION_NOTIFICATION_BAR); ?>[text][<?php echo esc_attr($lang_slug); ?>]"
                      value="<?php echo esc_attr($value); ?>">
                </div>
              <?php endforeach; ?>
            </td>
          </tr>

          <tr>
            <td>
              <label class="patlis-notification-field-label" for="patlis_nb_start_date"><?php esc_html_e('Start date', 'patlis-core'); ?></label>
              <input type="date"
                id="patlis_nb_start_date"
                name="<?php echo esc_attr(Patlis_Core::OPTION_NOTIFICATION_BAR); ?>[start_date]"
                value="<?php echo esc_attr($opt['start_date'] ?? ''); ?>">
            </td>
          </tr>

          <tr>
            <td>
              <label class="patlis-notification-field-label" for="patlis_nb_end_date"><?php esc_html_e('Date end', 'patlis-core'); ?></label>
              <input type="date"
                id="patlis_nb_end_date"
                name="<?php echo esc_attr(Patlis_Core::OPTION_NOTIFICATION_BAR); ?>[end_date]"
                value="<?php echo esc_attr($opt['end_date'] ?? ''); ?>">
            </td>
          </tr>

        </table>

        <?php submit_button('Save'); ?>
      </form>
    </div>
    <?php
  }
}