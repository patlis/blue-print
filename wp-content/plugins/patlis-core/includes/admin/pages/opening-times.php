<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_Opening {

  public static function sanitize($input): array {
    $in  = is_array($input) ? $input : [];
    $out = [];

    $out['show_on_footer'] = !empty($in['show_on_footer']) ? 1 : 0;
    $out['text']           = self::sanitize_multilang_html_input($in['text'] ?? []);

    return $out;
  }

  protected static function sanitize_multilang_html_input($raw): array {
    $existing = self::text_all();
    $out      = is_array($existing) ? $existing : [];

    if (is_string($raw)) {
      $default_lang        = self::get_default_language();
      $out[$default_lang]  = wp_kses_post($raw);
      return $out;
    }

    if (!is_array($raw)) return $out;

    $editable_langs = array_keys(self::get_languages());

    foreach ($raw as $lang => $value) {
      $lang = is_string($lang) ? sanitize_key($lang) : '';
      if ($lang === '') continue;
      if (!empty($editable_langs) && !in_array($lang, $editable_langs, true)) continue;
      $out[$lang] = is_string($value) ? wp_kses_post($value) : '';
    }

    return $out;
  }

  public static function get_options(): array {
    $opt = get_option(Patlis_Core::OPTION_OPENING, []);
    return is_array($opt) ? $opt : [];
  }

  public static function text(): string {
    $all = self::text_all();
    if (!$all) return '';

    $current_lang = self::get_current_language();
    if (!empty($all[$current_lang])) return $all[$current_lang];

    $default_lang = self::get_default_language();
    if (!empty($all[$default_lang])) return $all[$default_lang];

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

    $opt       = self::get_options();
    $languages = self::get_languages();
    $text_all  = self::text_all();
    ?>

    <style>
      .patlis-opening-page .form-table th,
      .patlis-opening-page .form-table td {
        padding-top: 5px;
        padding-bottom: 0;
      }
      .patlis-opening-lang-block {
        margin-bottom: 16px;
      }
      .patlis-opening-lang-label {
        font-weight: 600;
        margin-bottom: 4px;
      }
    </style>

    <div class="wrap patlis-opening-page">
      <h1><?php esc_html_e('Opening times', 'patlis-core'); ?></h1>

      <?php if (!empty($_GET['patlis_saved'])): ?>
        <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
      <?php endif; ?>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="patlis_save_opening">
        <?php wp_nonce_field('patlis_save_opening'); ?>

        <table class="form-table">

          <tr>
            <th><?php esc_html_e('Show Opening times', 'patlis-core'); ?></th>
            <td>
              <input type="checkbox"
                name="<?php echo esc_attr(Patlis_Core::OPTION_OPENING); ?>[show_on_footer]"
                value="1" <?php checked(!empty($opt['show_on_footer'])); ?>>
            </td>
          </tr>

          <tr>
            <th><?php esc_html_e('Text', 'patlis-core'); ?></th>
            <td>
              <?php foreach ($languages as $lang_slug => $lang_label): ?>
                <?php $value = $text_all[$lang_slug] ?? ''; ?>
                <div class="patlis-opening-lang-block">
                  <div class="patlis-opening-lang-label"><?php echo esc_html($lang_label); ?></div>
                  <textarea
                    class="large-text"
                    rows="3"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_OPENING); ?>[text][<?php echo esc_attr($lang_slug); ?>]"><?php echo wp_kses_post($value); ?></textarea>
                </div>
              <?php endforeach; ?>
            </td>
          </tr>

        </table>

        <?php submit_button('Save'); ?>
      </form>
    </div>
    <?php
  }
}
