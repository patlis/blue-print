<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('patlis_get_design_color_registry')) {
  $design_colors_file = dirname(__DIR__, 2) . '/design-colors.php';
  if (is_file($design_colors_file)) require_once $design_colors_file;
}
if (!class_exists('Patlis_Bricks_Colors')) {
  $bricks_colors_file = dirname(__DIR__) . '/bricks-colors.php';
  if (is_file($bricks_colors_file)) require_once $bricks_colors_file;
}

final class Patlis_Admin_Page_Design_Options {

  public static function sanitize($input): array {
    $input = is_array($input) ? $input : [];
    $existing = get_option(Patlis_Core::OPTION_DESIGN_OPTIONS, []);
    $existing = is_array($existing) ? $existing : [];
    $output = $existing;

    $registry = array_merge(
      Patlis_Core::get_design_registry(),
      Patlis_Core::get_design_pages_registry()
    );
    foreach ($registry as $section_key => $section) {
      if (!is_array($section)) continue;

      $source = isset($input[$section_key]) && is_array($input[$section_key])
        ? $input[$section_key]
        : ($existing[$section_key] ?? []);
      $source = is_array($source) ? $source : [];
      $variants = isset($section['variants']) && is_array($section['variants']) ? $section['variants'] : [];
      $default_variant = sanitize_key((string) ($section['default_variant'] ?? 'default'));
      $variant = isset($source['variant']) ? sanitize_key((string) $source['variant']) : $default_variant;

      if (!isset($variants[$variant])) {
        $variant = $default_variant;
      }

      $output[$section_key] = ['variant' => $variant];

      $section_fields = isset($section['fields']) && is_array($section['fields'])
        ? $section['fields']
        : [];
      foreach ($section_fields as $field_key => $field) {
        if (!is_array($field)) continue;
        $option_key = sanitize_key((string) ($field['option_key'] ?? $field_key));
        $value = $source[$option_key] ?? ($field['default'] ?? '');
        $existing_value = isset($existing[$section_key]) && is_array($existing[$section_key])
          ? ($existing[$section_key][$option_key] ?? '')
          : '';
        $output[$section_key][$option_key] = self::sanitize_field(
          $value,
          (string) ($field['type'] ?? 'text'),
          $existing_value
        );
      }

      foreach ($variants as $variant_key => $variant_definition) {
        $fields = isset($variant_definition['fields']) && is_array($variant_definition['fields'])
          ? $variant_definition['fields']
          : [];
        if ($fields === []) continue;

        $variant_source = isset($source[$variant_key]) && is_array($source[$variant_key])
          ? $source[$variant_key]
          : [];

        foreach ($fields as $field_key => $field) {
          if (!is_array($field)) continue;
          $option_key = sanitize_key((string) ($field['option_key'] ?? $field_key));
          $value = $variant_source[$option_key] ?? '';
          $existing_variant = isset($existing[$section_key][$variant_key]) && is_array($existing[$section_key][$variant_key])
            ? $existing[$section_key][$variant_key]
            : [];
          $existing_value = $existing_variant[$option_key] ?? '';
          $output[$section_key][$variant_key][$option_key] = self::sanitize_field(
            $value,
            (string) ($field['type'] ?? 'text'),
            $existing_value
          );
        }
      }
    }

    return $output;
  }

  private static function sanitize_field($value, string $type, $existing_value = '') {
    if ($type === 'image') return max(0, (int) $value);
    if ($type === 'checkbox') return !empty($value) ? 1 : 0;
    if (in_array($type, ['number', 'range'], true)) return is_numeric($value) ? (float) $value : 0;
    if ($type === 'color') return sanitize_hex_color((string) $value) ?: '';
    if (in_array($type, ['text', 'textarea'], true)) {
      return self::sanitize_multilingual_field($value, $existing_value, $type);
    }
    return sanitize_text_field((string) $value);
  }

  private static function sanitize_multilingual_field($value, $existing_value, string $type): array {
    $default_language = self::get_default_language();
    $output = is_array($existing_value) ? $existing_value : [];

    if (is_string($existing_value) && $existing_value !== '') {
      $output[$default_language] = $existing_value;
    }

    if (is_string($value)) {
      $value = [$default_language => $value];
    }

    if (!is_array($value)) return $output;

    $editable_languages = array_keys(self::get_languages());
    foreach ($value as $language => $text) {
      $language = is_string($language) ? sanitize_key($language) : '';
      if ($language === '' || !in_array($language, $editable_languages, true)) continue;

      $output[$language] = $type === 'textarea'
        ? sanitize_textarea_field((string) $text)
        : sanitize_text_field((string) $text);
    }

    return $output;
  }

  private static function get_languages(): array {
    $slugs = [];
    if (function_exists('patlis_get_effective_language_slugs_for_current_user')) {
      $slugs = patlis_get_effective_language_slugs_for_current_user();
    } elseif (function_exists('pll_languages_list')) {
      $slugs = pll_languages_list(['fields' => 'slug']);
    }

    $languages = [];
    if (is_array($slugs)) {
      foreach ($slugs as $slug) {
        $slug = is_string($slug) ? sanitize_key($slug) : '';
        if ($slug !== '') $languages[$slug] = strtoupper($slug);
      }
    }

    return $languages ?: ['default' => __('Default', 'patlis-core')];
  }

  private static function get_default_language(): string {
    if (function_exists('patlis_get_default_language')) {
      $language = sanitize_key(patlis_get_default_language());
      if ($language !== '') return $language;
    } elseif (function_exists('pll_default_language')) {
      $language = pll_default_language('slug');
      if (is_string($language) && $language !== '') return sanitize_key($language);
    }

    return (string) array_key_first(self::get_languages());
  }

  private static function is_section_allowed(array $section): bool {
    $availability = sanitize_key((string) ($section['availability'] ?? ''));
    if ($availability === '' || !function_exists('allowed_section')) return true;
    return allowed_section($availability) === 1;
  }

  public static function render(): void {
    if (!current_user_can('patlis_manage')) return;

    $options = get_option(Patlis_Core::OPTION_DESIGN_OPTIONS, []);
    $options = is_array($options) ? $options : [];
    $design_types = [
      'global'  => __('Global', 'patlis-core'),
      'colors'  => __('Colors', 'patlis-core'),
      'section' => __('Sections', 'patlis-core'),
      'set'     => __('Design Sets', 'patlis-core'),
      'page'    => __('Pages', 'patlis-core'),
    ];
    $active_type = isset($_GET['design_type']) ? sanitize_key((string) $_GET['design_type']) : 'global';
    if (!isset($design_types[$active_type])) $active_type = 'global';
    $registry = $active_type === 'page'
      ? Patlis_Core::get_design_pages_registry()
      : Patlis_Core::get_design_registry();

    $visible_sections = array_filter($registry, static function ($section) use ($active_type): bool {
      if (!is_array($section)) return false;
      if ($active_type === 'section' && !self::is_section_allowed($section)) return false;
      return sanitize_key((string) ($section['type'] ?? 'section')) === $active_type;
    });
    $active_section = isset($_GET['section']) ? sanitize_key((string) $_GET['section']) : '';
    if (!isset($visible_sections[$active_section])) {
      $active_section = (string) array_key_first($visible_sections);
    }

    wp_enqueue_media();
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Design', 'patlis-core'); ?></h1>

      <?php if (!empty($_GET['patlis_saved'])): ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Saved.', 'patlis-core'); ?></p></div>
      <?php endif; ?>

      <?php if (!empty($_GET['patlis_error'])): ?>
        <div class="notice notice-error"><p>
          <?php if ($active_type === 'set'): ?>
            <?php esc_html_e('The design set could not be applied.', 'patlis-core'); ?>
          <?php elseif ($active_type === 'global'): ?>
            <?php esc_html_e('The Bricks global variables could not be updated.', 'patlis-core'); ?>
          <?php else: ?>
            <?php esc_html_e('The colors could not be saved. Check the values and try again.', 'patlis-core'); ?>
          <?php endif; ?>
        </p></div>
      <?php endif; ?>

      <nav class="nav-tab-wrapper patlis-design-tabs">
        <?php foreach ($design_types as $type_key => $type_label): ?>
          <?php $type_url = add_query_arg(['page' => 'patlis-design-options', 'design_type' => $type_key], admin_url('admin.php')); ?>
          <a href="<?php echo esc_url($type_url); ?>" class="nav-tab<?php echo $type_key === $active_type ? ' nav-tab-active' : ''; ?>">
            <?php echo esc_html($type_label); ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <?php if ($active_type === 'colors'): ?>
        <?php self::render_colors_tab(); ?>
    </div>
    <?php return; endif; ?>

      <?php if ($active_type === 'set'): ?>
        <?php self::render_design_sets_tab(); ?>
    </div>
    <?php return; endif; ?>

      <?php if ($active_type === 'global'): ?>
        <?php
        $homepage_labels = Patlis_Admin_Page_Homepage::labels();
        $sections_order = Patlis_Admin_Page_Homepage::get_sections_order();
        $global_variables = class_exists('Patlis_Bricks_Variables')
          ? Patlis_Bricks_Variables::get_editable_variables()
          : new WP_Error('variables_module_missing', __('The Bricks variables module is missing.', 'patlis-core'));
        $variable_definitions = class_exists('Patlis_Bricks_Variables')
          ? Patlis_Bricks_Variables::get_variable_definitions()
          : [];
        $font_options = class_exists('Patlis_Bricks_Variables')
          ? Patlis_Bricks_Variables::get_font_options()
          : [];
        $variable_labels = [
          'font-size'  => __('Base font size', 'patlis-core'),
          'site-width' => __('Site width', 'patlis-core'),
          'btn-radius' => __('Button radius', 'patlis-core'),
          'logo-height' => __('Logo height', 'patlis-core'),
          'logo-padding' => __('Logo padding', 'patlis-core'),
          'card-image-radius' => __('Card image radius', 'patlis-core'),
        ];
        wp_enqueue_script('jquery-ui-sortable');
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="patlis_save_design_global">
          <?php wp_nonce_field('patlis_save_design_global'); ?>

          <h2><?php esc_html_e('Global variables', 'patlis-core'); ?></h2>
          <?php if (is_wp_error($global_variables)): ?>
            <div class="notice notice-error inline"><p><?php echo esc_html($global_variables->get_error_message()); ?></p></div>
          <?php else: ?>
            <div class="patlis-global-variables">
              <?php foreach (['font-family' => __('Body font', 'patlis-core'), 'headers-font-family' => __('Headings font', 'patlis-core')] as $variable_name => $variable_label): ?>
                <?php $input_id = 'patlis-' . $variable_name; ?>
                <div class="patlis-font-variable">
                  <label for="<?php echo esc_attr($input_id); ?>"><strong><?php echo esc_html($variable_label); ?></strong></label>
                  <select id="<?php echo esc_attr($input_id); ?>" name="patlis_bricks_variables[<?php echo esc_attr($variable_name); ?>]">
                    <?php foreach ($font_options as $font): ?>
                      <option value="<?php echo esc_attr($font); ?>" <?php selected($global_variables[$variable_name], $font); ?>><?php echo esc_html($font); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php endforeach; ?>
              <?php foreach ($variable_labels as $variable_name => $variable_label): ?>
                <?php
                $definition = $variable_definitions[$variable_name];
                $input_id = 'patlis-' . $variable_name;
                ?>
                <div class="patlis-design-range-field">
                  <label for="<?php echo esc_attr($input_id); ?>"><strong><?php echo esc_html($variable_label); ?></strong></label>
                  <input id="<?php echo esc_attr($input_id); ?>" type="range" name="patlis_bricks_variables[<?php echo esc_attr($variable_name); ?>]" value="<?php echo esc_attr((string) $global_variables[$variable_name]); ?>" min="<?php echo esc_attr((string) $definition['min']); ?>" max="<?php echo esc_attr((string) $definition['max']); ?>" step="<?php echo esc_attr((string) $definition['step']); ?>">
                  <output for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html((string) $global_variables[$variable_name]); ?>px</output>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <h2><?php esc_html_e('Home page display order', 'patlis-core'); ?></h2>
          <p class="description"><?php esc_html_e('Drag to reorder.', 'patlis-core'); ?></p>
          <ul id="patlis-sections-sortable">
            <?php foreach ($sections_order as $index => $slug): ?>
              <li data-slug="<?php echo esc_attr($slug); ?>">
                <span class="patlis-drag-handle" aria-hidden="true">&#9776;</span>
                <span class="patlis-section-nr"><?php echo esc_html((string) ($index + 1)); ?></span>
                <span class="patlis-section-label"><?php echo esc_html((string) ($homepage_labels[$slug] ?? $slug)); ?></span>
                <input class="patlis-section-order-input" type="hidden" name="patlis_sections_order[]" value="<?php echo esc_attr($slug); ?>">
                <input type="hidden" name="patlis_sections_visibility[<?php echo esc_attr($slug); ?>]" value="0">
                <label class="patlis-section-visibility">
                  <input type="checkbox" name="patlis_sections_visibility[<?php echo esc_attr($slug); ?>]" value="1" <?php checked(Patlis_Core::is_home_section_visible($slug)); ?>>
                  <?php esc_html_e('Show', 'patlis-core'); ?>
                </label>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php submit_button(__('Save', 'patlis-core')); ?>
        </form>
        <style>
          #patlis-sections-sortable { list-style: none; max-width: 380px; margin: 16px 0; padding: 0; }
          #patlis-sections-sortable li { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; padding: 10px 14px; border: 1px solid #ccd0d4; border-radius: 4px; background: #fff; font-size: 14px; font-weight: 500; user-select: none; }
          #patlis-sections-sortable .patlis-drag-handle { color: #aaa; font-size: 18px; line-height: 1; cursor: grab; }
          #patlis-sections-sortable .patlis-drag-handle:active { cursor: grabbing; }
          #patlis-sections-sortable .patlis-section-nr { min-width: 22px; color: #999; font-size: 12px; }
          #patlis-sections-sortable .patlis-section-label { flex: 1; }
          #patlis-sections-sortable .patlis-section-visibility { display: flex; align-items: center; gap: 5px; font-weight: 400; }
          #patlis-sections-sortable li.ui-sortable-helper { box-shadow: 0 4px 12px rgba(0,0,0,.15); }
          #patlis-sections-sortable li.ui-sortable-placeholder { visibility: visible !important; border: 2px dashed #72aee6; background: #f0f6fc; }
          .patlis-global-variables { display: grid; gap: 14px; max-width: 520px; margin: 16px 0 28px; }
          .patlis-global-variables .patlis-font-variable { display: grid; grid-template-columns: 130px minmax(180px, 1fr) 70px; align-items: center; gap: 12px; }
          .patlis-global-variables .patlis-font-variable select { grid-column: 2 / -1; width: 100%; max-width: none; }
          .patlis-global-variables .patlis-design-range-field { display: grid; grid-template-columns: 130px minmax(180px, 1fr) 70px; align-items: center; gap: 12px; }
          .patlis-global-variables output { font-variant-numeric: tabular-nums; }
        </style>
        <script>
          jQuery(function($) {
            var $list = $('#patlis-sections-sortable');
            $('.patlis-global-variables input[type="range"]').on('input', function() {
              $(this).siblings('output').text(this.value + 'px');
            });
            $list.sortable({
              items: 'li',
              axis: 'y',
              handle: '.patlis-drag-handle',
              placeholder: 'ui-sortable-placeholder',
              update: function() {
                $list.find('li').each(function(index) {
                  $(this).find('.patlis-section-nr').text(index + 1);
                  $(this).find('.patlis-section-order-input').val($(this).data('slug'));
                });
              }
            });
          });
        </script>
    </div>
    <?php return; endif; ?>

      <?php if ($visible_sections === []): ?>
        <p><?php esc_html_e('No designs are available in this category yet.', 'patlis-core'); ?></p>
    </div>
    <?php return; endif; ?>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="patlis_save_design_options">
        <input type="hidden" name="patlis_design_active_section" id="patlis-design-active-section" value="<?php echo esc_attr($active_section); ?>">
        <input type="hidden" name="patlis_design_active_type" value="<?php echo esc_attr($active_type); ?>">
        <?php wp_nonce_field('patlis_save_design_options'); ?>

        <?php if (count($visible_sections) > 1 || $active_type === 'page'): ?>
          <div class="patlis-design-filter">
            <label for="patlis-design-section-filter"><?php echo esc_html($active_type === 'page' ? __('Page', 'patlis-core') : __('Section', 'patlis-core')); ?></label>
            <select id="patlis-design-section-filter">
              <?php foreach ($visible_sections as $filter_section_key => $filter_section): ?>
                <?php
                $section_url = add_query_arg([
                  'page'        => 'patlis-design-options',
                  'design_type' => $active_type,
                  'section'     => $filter_section_key,
                ], admin_url('admin.php'));
                ?>
                <option value="<?php echo esc_url($section_url); ?>" <?php selected($active_section, $filter_section_key); ?>>
                  <?php echo esc_html((string) ($filter_section['label'] ?? $filter_section_key)); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php foreach ([$active_section => $visible_sections[$active_section]] as $section_key => $section): ?>
          <?php
          $variants = isset($section['variants']) && is_array($section['variants']) ? $section['variants'] : [];
          if ($variants === []) continue;
          $section_options = isset($options[$section_key]) && is_array($options[$section_key]) ? $options[$section_key] : [];
          $default_variant = sanitize_key((string) ($section['default_variant'] ?? 'default'));
          $selected_variant = sanitize_key((string) ($section_options['variant'] ?? $default_variant));
          if (!isset($variants[$selected_variant])) $selected_variant = $default_variant;
          $section_fields = isset($section['fields']) && is_array($section['fields']) ? $section['fields'] : [];
          $section_description = trim((string) ($section['description'] ?? ''));
          ?>
          <section class="patlis-design-section" data-section="<?php echo esc_attr($section_key); ?>">
            <h2><?php echo esc_html((string) ($section['label'] ?? $section_key)); ?></h2>
            <?php if ($section_description !== ''): ?>
              <div class="patlis-design-description"><?php echo wp_kses_post(wpautop($section_description)); ?></div>
            <?php endif; ?>
            <div class="patlis-design-choices">
              <?php foreach ($variants as $variant_key => $variant): ?>
                <?php
                $preview = ltrim((string) ($variant['preview'] ?? ''), '/');
                $preview_url = $preview !== '' && is_file(PATLIS_CORE_PATH . $preview)
                  ? PATLIS_CORE_URL . $preview
                  : '';
                $fields = isset($variant['fields']) && is_array($variant['fields']) ? $variant['fields'] : [];
                $description = trim((string) ($variant['description'] ?? ''));
                ?>
                <div class="patlis-design-variant" data-variant="<?php echo esc_attr($variant_key); ?>">
                  <label class="patlis-design-choice">
                    <input type="radio" name="<?php echo esc_attr(Patlis_Core::OPTION_DESIGN_OPTIONS); ?>[<?php echo esc_attr($section_key); ?>][variant]" value="<?php echo esc_attr($variant_key); ?>" <?php checked($selected_variant, $variant_key); ?>>
                    <h3 style="margin: 0;"><?php echo esc_html((string) ($variant['label'] ?? $variant_key)); ?></h3>
                    <?php if ($preview_url !== ''): ?>
                      <img src="<?php echo esc_url($preview_url); ?>" alt="" class="patlis-design-preview">
                    <?php endif; ?>
                  </label>

                  <?php if ($fields !== [] || $description !== ''): ?>
                    <div class="patlis-design-fields" <?php echo $selected_variant === $variant_key ? '' : 'hidden'; ?>>
                      <?php if ($description !== ''): ?>
                        <div class="patlis-design-description"><?php echo wp_kses_post(wpautop($description)); ?></div>
                      <?php endif; ?>
                      <?php if ($fields !== []): ?>
                        <h3><?php echo esc_html(sprintf(__('%s options', 'patlis-core'), (string) ($variant['label'] ?? $variant_key))); ?></h3>
                        <?php foreach ($fields as $field_key => $field): ?>
                          <?php self::render_field($section_key, $variant_key, $field_key, $field, $section_options); ?>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <?php if ($section_fields !== []): ?>
              <div class="patlis-design-section-fields">
                <h3><?php echo esc_html(sprintf(__('%s content', 'patlis-core'), (string) ($section['label'] ?? $section_key))); ?></h3>
                <?php foreach ($section_fields as $field_key => $field): ?>
                  <?php self::render_field($section_key, '', $field_key, $field, $section_options); ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </section>
        <?php endforeach; ?>

        <?php submit_button(__('Save', 'patlis-core')); ?>
      </form>
    </div>

    <style>
      .patlis-design-choices { display: flex; flex-wrap: wrap; flex-direction: row; align-items: stretch; gap: 12px; margin: 16px 0; }
      .patlis-design-section { margin-bottom: 32px; }
      .patlis-design-section[hidden] { display: none; }
      .patlis-design-tabs { margin-bottom: 20px; }
      .patlis-design-filter { display: flex; align-items: center; gap: 12px; max-width: 1000px; margin: 20px 0; }
      .patlis-design-filter label { font-weight: 600; }
      .patlis-design-filter select { min-width: 280px; }
      .patlis-design-variant { max-width: 600px; flex-direction: column; display: flex; }
      .patlis-design-choice { position: relative; display: grid; grid-template-columns: auto 1fr; align-items: center; gap: 10px; 
      box-sizing: border-box; width: 100%; padding: 12px; border: 1px solid #c3c4c7; border-radius: 4px; 
      background: #fff; cursor: pointer; flex: 1;align-content: start;}
      .patlis-design-choice input { margin: 0; }
      .patlis-design-preview { grid-column: 1 / -1; display: block; width: 100%;  object-fit: cover; border: 1px solid #dcdcde; }
      .patlis-design-fields { margin-top: 8px; padding: 16px; border-left: 4px solid #2271b1; background: #fff; }
      .patlis-design-section-fields { max-width: 600px; margin-top: 16px; padding: 16px; border-left: 4px solid #2271b1; background: #fff; }
      .patlis-design-section-fields > h3:first-child { margin-top: 0; }
      .patlis-design-fields[hidden] { display: none; }
      .patlis-design-description > :first-child { margin-top: 0; }
      .patlis-design-description > :last-child { margin-bottom: 0; }
      .patlis-design-description + h3 { margin-top: 20px; }
      .patlis-design-field-description { margin: -6px 0 12px; color: #646970; }
      .patlis-design-field-checkbox + .patlis-design-field-description { margin-top: 6px; }
      .patlis-design-image { display: block; width: auto; max-width: 100%; height: auto; max-height: 240px; margin-bottom: 12px; }
      .patlis-design-language-field { margin-bottom: 12px; }
      .patlis-design-language-field label { display: block; margin-bottom: 4px; }
      .patlis-design-range-field { display: flex; align-items: center; gap: 12px; }
      .patlis-design-range-field input { width: min(320px, 100%); }
      .patlis-design-range-field output { min-width: 3ch; font-variant-numeric: tabular-nums; }
    </style>

    <script>
      jQuery(function($) {
        $('#patlis-design-section-filter').on('change', function() {
          window.location.href = this.value;
        });

        $('.patlis-design-section input[type="radio"]').on('change', function(event) {
          var $section = $(this).closest('.patlis-design-section');
          $section.find('.patlis-design-fields').prop('hidden', true);
          $(this).closest('.patlis-design-variant').find('.patlis-design-fields').prop('hidden', false);
        });

        $('.patlis-design-image-select').on('click', function() {
          var $button = $(this);
          var $field = $button.closest('.patlis-design-image-field');
          var frame = wp.media({
            title: <?php echo wp_json_encode(__('Select background image', 'patlis-core')); ?>,
            button: { text: <?php echo wp_json_encode(__('Use this image', 'patlis-core')); ?> },
            library: { type: 'image' },
            multiple: false
          });

          frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
            $field.find('input[type="hidden"]').val(attachment.id);
            $field.find('.patlis-design-image-preview').html($('<img>', { src: url, class: 'patlis-design-image', alt: '' }));
            $field.find('.patlis-design-image-remove').prop('hidden', false);
          });

          frame.open();
        });

        $('.patlis-design-image-remove').on('click', function() {
          var $field = $(this).closest('.patlis-design-image-field');
          $field.find('input[type="hidden"]').val('0');
          $field.find('.patlis-design-image-preview').empty();
          $(this).prop('hidden', true);
        });

        $('.patlis-design-range-field input[type="range"]').on('input', function() {
          $(this).siblings('output').text(this.value);
        });
      });
    </script>
    <?php
  }

  private static function render_colors_tab(): void {
    if (!class_exists('Patlis_Bricks_Colors') || !function_exists('patlis_get_design_color_registry')) {
      echo '<div class="notice notice-error inline"><p>' . esc_html__('The Colors module is missing from this Patlis Core installation. Reinstall or update the complete plugin package.', 'patlis-core') . '</p></div>';
      return;
    }

    $data = Patlis_Bricks_Colors::get_editable_colors();
    if (is_wp_error($data)) {
      echo '<div class="notice notice-error inline"><p>' . esc_html($data->get_error_message()) . '</p></div>';
      return;
    }

    $colors = isset($data['colors']) && is_array($data['colors']) ? $data['colors'] : [];
    $missing = isset($data['missing']) && is_array($data['missing']) ? $data['missing'] : [];
    $palette_registry = function_exists('patlis_get_color_palettes_registry')
      ? patlis_get_color_palettes_registry()
      : [];
    $palette_data = [];
    foreach ($palette_registry as $palette_key => $palette) {
      if (!is_array($palette)) continue;

      $palette_colors = [];
      foreach (($palette['colors'] ?? []) as $variable => $value) {
        $name = sanitize_key(ltrim((string) $variable, '-'));
        $color_value = patlis_sanitize_css_color($value);
        if ($name !== '' && isset($colors[$name]) && $color_value !== '') {
          $palette_colors[$name] = $color_value;
        }
      }
      if ($palette_colors === []) continue;

      $palette_data[sanitize_key((string) $palette_key)] = [
        'name'   => (string) ($palette['name'] ?? $palette_key),
        'tags'   => isset($palette['tags']) && is_array($palette['tags']) ? array_values($palette['tags']) : [],
        'colors' => $palette_colors,
      ];
    }
    ?>
    <?php if ($missing !== []): ?>
      <div class="notice notice-warning inline"><p>
        <?php echo esc_html(sprintf(__('Missing Bricks variables: %s', 'patlis-core'), implode(', ', $missing))); ?>
      </p></div>
    <?php endif; ?>

    <?php if ($colors === []): ?>
      <p><?php esc_html_e('No editable Patlis colors were found.', 'patlis-core'); ?></p>
      <?php return; ?>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="patlis_save_design_colors">
      <?php wp_nonce_field('patlis_save_design_colors'); ?>

      <div class="patlis-colors-workspace">
        <aside class="patlis-palette-panel">
          <h2><?php esc_html_e('Color palettes', 'patlis-core'); ?></h2>
          <p class="description"><?php esc_html_e('Choose a palette to load its colors into the editor.', 'patlis-core'); ?></p>
          <div class="patlis-palette-list">
            <?php foreach ($palette_data as $palette_key => $palette): ?>
              <button type="button" class="patlis-palette" data-palette="<?php echo esc_attr($palette_key); ?>">
                <span class="patlis-palette-swatches" aria-hidden="true">
                  <?php foreach (['head', 'body', 'body-zebra', 'body-link', 'footer'] as $swatch_key): ?>
                    <?php if (isset($palette['colors'][$swatch_key])): ?>
                      <span style="background-color:<?php echo esc_attr($palette['colors'][$swatch_key]); ?>"></span>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </span>
                <strong><?php echo esc_html($palette['name']); ?></strong>
                <?php if ($palette['tags'] !== []): ?>
                  <span class="patlis-palette-tags">
                    <?php foreach ($palette['tags'] as $tag): ?>
                      <span><?php echo esc_html((string) $tag); ?></span>
                    <?php endforeach; ?>
                  </span>
                <?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
        </aside>

        <div class="patlis-colors-main">
          <section class="patlis-color-editor">
            <div class="patlis-color-editor-header">
              <div>
                <h2><?php esc_html_e('Customize selected palette', 'patlis-core'); ?></h2>
                <p class="description"><?php esc_html_e('Fine-tune the colors for this palette. Your changes will be reflected in the preview above.', 'patlis-core'); ?></p>
              </div>
              <button type="submit" class="button" form="patlis-reset-colors-form"><?php esc_html_e('Restore default colors', 'patlis-core'); ?></button>
            </div>

            <?php
            $color_groups = patlis_get_design_color_groups();
            $color_columns = [
              array_slice($color_groups, 0, 2, true),
              array_slice($color_groups, 2, 1, true),
              array_slice($color_groups, 3, 1, true),
            ];
            ?>
            <div class="patlis-color-columns">
              <?php foreach ($color_columns as $column_groups): ?>
                <div class="patlis-color-column">
                  <?php foreach ($column_groups as $group_label => $group_colors): ?>
                    <table class="widefat striped patlis-colors-table">
                      <thead>
                        <tr class="patlis-colors-group">
                          <th colspan="2" scope="colgroup"><strong><?php echo esc_html($group_label); ?></strong></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($group_colors as $name): ?>
                          <?php if (!isset($colors[$name])) continue; ?>
                          <?php $color = $colors[$name]; ?>
                          <tr>
                            <th scope="row"><?php echo esc_html((string) $color['label']); ?></th>
                            <td><?php self::render_color_control($name, (string) $color['color']); ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="patlis-color-actions">
              <?php submit_button(__('Save colors', 'patlis-core'), 'primary', 'submit', false); ?>
            </div>
          </section>

          <aside class="patlis-color-preview" aria-label="<?php esc_attr_e('Color preview', 'patlis-core'); ?>">
            <div class="patlis-preview-notification"><?php esc_html_e('Notification text', 'patlis-core'); ?></div>
            <header class="patlis-preview-header">
              <strong class="logo">Company Logo</strong>
              <nav aria-label="<?php esc_attr_e('Preview navigation', 'patlis-core'); ?>">
                <a href="#" ><?php esc_html_e('About', 'patlis-core'); ?></a>
                <a href="#"><?php esc_html_e('Gallery', 'patlis-core'); ?></a>
                <a href="#"><?php esc_html_e('Services', 'patlis-core'); ?></a>
                <a href="#"><?php esc_html_e('Contact', 'patlis-core'); ?></a>
              </nav>
            </header>
            <main class="patlis-preview-main">
              <section class="patlis-preview-hero">
                <div class="patlis-preview-hero-content" style="width: 50%;">
                  <h2 class="patlis-preview-palette-name"><?php esc_html_e('Page Title', 'patlis-core'); ?></h2>
                  <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since 1966</p>
                  <a href="#" class="primary"><?php esc_html_e('Primary button', 'patlis-core'); ?></a>
                  <a href="#" class="primary-outline"><?php esc_html_e('Outline button', 'patlis-core'); ?></a>
                  <a href="#" class="primary-link"><?php esc_html_e('Text link', 'patlis-core'); ?></a>
                </div>
                <div class="patlis-preview-hero-image" style="width: 50%;">
                  <img src="<?php echo esc_url(PATLIS_CORE_URL . 'assets/design-previews/preview-image.webp'); ?>" style="max-width: 100%;" >
                </div>
              </section>
              <section class="patlis-preview-feature" >
                <h3><?php esc_html_e('Alternate background', 'patlis-core'); ?></h3>
                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since 1966</p>
              </section>
              <section class="patlis-preview-hero">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since 1966</section>
            </main>
            <footer class="patlis-preview-footer">
              <strong>Company Name</strong>
              <div class="patlis-footer-links">
                <a href="#" ><?php esc_html_e('Home', 'patlis-core'); ?></a>
                <a href="#"><?php esc_html_e('Legal', 'patlis-core'); ?></a>
                <a href="#"><?php esc_html_e('Privacy', 'patlis-core'); ?></a>
                <a href="#"><?php esc_html_e('Terms', 'patlis-core'); ?></a>
              </div>
            </footer>
          </aside>
        </div>
      </div>
    </form>

    <form id="patlis-reset-colors-form" class="patlis-reset-colors-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="patlis_reset_design_colors">
      <?php wp_nonce_field('patlis_reset_design_colors'); ?>
    </form>

    <style>
      .patlis-colors-workspace { display: grid; grid-template-columns: minmax(220px, 280px) minmax(0, 1fr); align-items: start; gap: 16px; margin-top: 24px; }
      .patlis-colors-main { display: flex; min-width: 0; flex-direction: column; gap: 16px; }
      .patlis-colors-workspace h2 { margin-top: 0; }
      .patlis-palette-panel, .patlis-color-editor, .patlis-color-preview { box-sizing: border-box; border: 1px solid #c3c4c7; border-radius: 4px; background: #fff; }
      .patlis-palette-panel, .patlis-color-editor { padding: 16px; }
      .patlis-palette-list { display: grid; gap: 10px; max-height: 680px; margin-top: 16px; padding-right: 6px; overflow-y: auto; }
      .patlis-palette { width: 100%; padding: 10px; border: 1px solid #dcdcde; border-radius: 4px; background: #fff; color: #1d2327; text-align: left; cursor: pointer; }
      .patlis-palette:hover { border-color: #8c8f94; }
      .patlis-palette:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: 2px solid transparent; }
      .patlis-palette.is-selected { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }
      .patlis-palette-swatches { display: grid; grid-template-columns: repeat(5, 1fr); height: 34px; margin-bottom: 8px; overflow: hidden; border: 1px solid #dcdcde; border-radius: 3px; }
      .patlis-palette-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 7px; }
      .patlis-palette-tags span { padding: 2px 6px; border-radius: 3px; background: #f0f0f1; color: #50575e; font-size: 11px; }
      .patlis-color-editor-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
      .patlis-color-editor-header h2 { margin-bottom: 2px; }
      .patlis-color-editor-header .button { flex: 0 0 auto; }
      .patlis-color-columns { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); align-items: start; gap: 12px; margin-top: 16px; }
      .patlis-color-column { display: grid; gap: 12px; min-width: 0; }
      .patlis-colors-table { margin: 0; table-layout: fixed; }
      .patlis-colors-table th:first-child { width: 38%; }
      .patlis-colors-table th, .patlis-colors-table td { vertical-align: middle; }
      .patlis-colors-table .patlis-colors-group th { padding: 10px; background: #dcdcde; font-size: 14px; }
      .patlis-color-control { display: flex; align-items: center; gap: 8px; min-width: 0; }
      .patlis-color-control input[type="color"] { width: 40px; min-width: 40px; height: 32px; padding: 2px; cursor: pointer; }
      .patlis-color-control input[type="text"] { width: 100%; min-width: 0; }
      .patlis-color-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 20px; }
      .patlis-reset-colors-form { display: none; }
      .patlis-color-preview { order: -1; overflow: hidden; color: var(--body-text); background: var(--body); max-width: 800px; margin: 0 auto; }
      .patlis-preview-notification { padding: 8px 16px; color: var(--notification-text); background: var(--notification); text-align: center; }
      .patlis-preview-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 18px 20px; color: var(--head-text); background: var(--head); }
      .patlis-preview-header .logo { font-size: 18px; font-weight: 700; }
      .patlis-preview-header nav { display: flex; flex-wrap: wrap; gap: 14px; color: var(--head-link); }
      .patlis-preview-header nav a:first-child { color: var(--head-active); }
      .patlis-preview-header a { color: var(--head-link); text-decoration: none; font-weight: 600; }
      .patlis-preview-header a:hover { color: var(--head-hover); }
      .patlis-preview-main { background: var(--body); }
      .patlis-preview-hero { padding: 25px;  display: flex; flex-direction: row; justify-content: center; }
      .patlis-preview-hero h2 { margin: 4px 0 14px; color: var(--title); font-size: 34px; line-height: 1.1; }
      .patlis-preview-feature h3 { margin: 4px 0 14px; color: var(--title);}

      .patlis-preview-hero p { max-width: 470px; font-size: 14px; line-height: 1.6; }
      .patlis-preview-hero .primary, .patlis-preview-hero .primary-outline, .patlis-preview-hero .primary-link{text-decoration: none; font-weight: 600;}
      .patlis-preview-hero .primary {border-radius: 3px; color: var(--body); background: var(--body-link); padding: 10px 14px; border: 1px solid var(--body-link); }
      .patlis-preview-hero .primary:hover {background: var(--body-hover); color: var(--body);  border: 1px solid var(--body-hover); }

      .patlis-preview-hero .primary-outline {border-radius: 3px; color: var(--body-link); background: var(--body); padding: 10px 14px; border: 1px solid var(--body-link); }
      .patlis-preview-hero .primary-outline:hover {background: var(--body-hover); color: var(--body);  border: 1px solid var(--body-hover); }

      .patlis-preview-hero .primary-link {color: var(--body-link); }
      .patlis-preview-hero .primary-link:hover {color: var(--body-hover); }

      .patlis-preview-feature {margin: 0 16px; padding: 16px 16px; background: var(--body-zebra); border: 1px solid var(--border);box-shadow: 0 2px 4px var(--shadow); }
      .patlis-preview-feature strong { color: var(--title); }
      .patlis-preview-footer { display: flex; justify-content: space-between; gap: 16px; padding: 24px 28px; color: var(--footer-text); background: var(--footer); }
      .patlis-preview-footer a { color: var(--footer-link); text-decoration: none; margin-right: 8px; }
      .patlis-preview-footer a:hover { color: var(--footer-hover); }
      @media (max-width: 1180px) {
        .patlis-color-columns { grid-template-columns: 1fr; }
      }
      @media (max-width: 782px) {
        .patlis-colors-workspace { display: block; }
        .patlis-palette-panel { margin-bottom: 16px; }
        .patlis-palette-list { max-height: 420px; }
        .patlis-color-editor-header { align-items: stretch; flex-direction: column; }
        .patlis-color-editor-header .button { align-self: flex-start; }
        .patlis-colors-table { display: block; overflow-x: auto; }
      }
    </style>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var palettes = <?php echo wp_json_encode($palette_data); ?>;
        var preview = document.querySelector('.patlis-color-preview');
        var previewPaletteName = document.querySelector('.patlis-preview-palette-name');

        function setPreviewColor(name, color) {
          if (preview && name && color) preview.style.setProperty('--' + name, color);
        }

        function updatePreviewFromEditor() {
          document.querySelectorAll('.patlis-color-control input[type="text"]').forEach(function(input) {
            var match = input.name.match(/\[([^\]]+)\]\[color\]$/);
            if (match) setPreviewColor(match[1], input.value.trim());
          });
        }

        document.querySelectorAll('.patlis-palette').forEach(function(button) {
          button.addEventListener('click', function() {
            var palette = palettes[button.dataset.palette];
            if (!palette || !palette.colors) return;

            Object.keys(palette.colors).forEach(function(name) {
              var input = document.querySelector('[name="patlis_bricks_colors[' + name + '][color]"]');
              if (!input) return;
              input.value = palette.colors[name];
              input.dispatchEvent(new Event('input', { bubbles: true }));
            });

            document.querySelectorAll('.patlis-palette').forEach(function(item) {
              item.classList.toggle('is-selected', item === button);
            });
            if (previewPaletteName && palette.name) previewPaletteName.textContent = palette.name;
            updatePreviewFromEditor();
          });
        });

        var resetForm = document.querySelector('.patlis-reset-colors-form');
        if (resetForm) {
          resetForm.addEventListener('submit', function(event) {
            if (!window.confirm(<?php echo wp_json_encode(__('Restore all editable colors to their defaults?', 'patlis-core')); ?>)) {
              event.preventDefault();
            }
          });
        }

        document.querySelectorAll('.patlis-color-control').forEach(function(control) {
          var picker = control.querySelector('input[type="color"]');
          var value = control.querySelector('input[type="text"]');

          function normalizeHex(color) {
            var shortHex = color.match(/^#([0-9a-f]{3})$/i);
            if (shortHex) return '#' + shortHex[1].split('').map(function(character) { return character + character; }).join('');
            return /^#[0-9a-f]{6}$/i.test(color) ? color : '';
          }

          var initialHex = normalizeHex(value.value.trim());
          if (initialHex) picker.value = initialHex;

          picker.addEventListener('input', function() {
            value.value = picker.value;
            value.dispatchEvent(new Event('input', { bubbles: true }));
          });
          value.addEventListener('input', function() {
            var hex = normalizeHex(value.value.trim());
            if (hex) picker.value = hex;
            var match = value.name.match(/\[([^\]]+)\]\[color\]$/);
            if (match) setPreviewColor(match[1], value.value.trim());
          });
        });

        updatePreviewFromEditor();
      });
    </script>
    <?php
  }

  private static function render_design_sets_tab(): void {
    $sets = Patlis_Core::get_design_sets_registry();
    if ($sets === []) {
      echo '<p>' . esc_html__('No design sets are available.', 'patlis-core') . '</p>';
      return;
    }
    ?>
    <h2><?php esc_html_e('Design Sets', 'patlis-core'); ?></h2>
    <p class="description"><?php esc_html_e('Apply a coordinated design across multiple sections. Existing content and field values are preserved.', 'patlis-core'); ?></p>

    <div class="patlis-design-sets">
      <?php foreach ($sets as $set_key => $set): ?>
        <?php
        if (!is_array($set)) continue;
        $preview = ltrim((string) ($set['preview'] ?? ''), '/');
        $preview_url = $preview !== '' && is_file(PATLIS_CORE_PATH . $preview)
          ? PATLIS_CORE_URL . $preview
          : '';
        ?>
        <article class="patlis-design-set">
          <div class="patlis-design-set-body">
            <h3><?php echo esc_html((string) ($set['label'] ?? $set_key)); ?></h3>
            <?php if (!empty($set['description'])): ?>
              <p class="patlis-design-set-description"><?php echo esc_html((string) $set['description']); ?></p>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="patlis-apply-design-set-form">
              <input type="hidden" name="action" value="patlis_apply_design_set">
              <input type="hidden" name="patlis_design_set" value="<?php echo esc_attr($set_key); ?>">
              <?php wp_nonce_field('patlis_apply_design_set'); ?>
              <?php submit_button(__('Apply design set', 'patlis-core'), 'primary', 'submit', false); ?>
            </form>
          </div>
          <?php if ($preview_url !== ''): ?>
            <img src="<?php echo esc_url($preview_url); ?>" alt="" class="patlis-design-set-preview">
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

    <style>
      .patlis-design-sets { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 360px)); gap: 16px; margin-top: 20px; }
      .patlis-design-set { overflow: hidden; border: 1px solid #c3c4c7; border-radius: 4px; background: #fff; }
      .patlis-design-set-preview { display: block; width: 100%; aspect-ratio: 16 / 10; object-fit: cover; border-top: 1px solid #dcdcde; }
      .patlis-design-set-body { padding: 16px; }
      .patlis-design-set-body h3 { margin: 0 0 8px; }
      .patlis-design-set-description { margin: 0 0 16px; color: #50575e; }
    </style>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.patlis-apply-design-set-form').forEach(function(form) {
          form.addEventListener('submit', function(event) {
            if (!window.confirm(<?php echo wp_json_encode(__('Apply this design set? Existing content and field values will be preserved.', 'patlis-core')); ?>)) {
              event.preventDefault();
            }
          });
        });
      });
    </script>
    <?php
  }

  private static function render_color_control(string $name, string $value): void {
    $input_name = 'patlis_bricks_colors[' . $name . '][color]';
    $label = sprintf(__('%s color', 'patlis-core'), $name);
    echo '<div class="patlis-color-control">';
    echo '<input type="color" value="#000000" aria-label="' . esc_attr($label) . '">';
    echo '<input type="text" class="regular-text code" name="' . esc_attr($input_name) . '" value="' . esc_attr($value) . '" aria-label="' . esc_attr($label) . '" required>';
    echo '</div>';
  }

  private static function render_field(string $section_key, string $variant_key, string $field_key, array $field, array $section_options): void {
    $type = (string) ($field['type'] ?? 'text');
    $option_key = sanitize_key((string) ($field['option_key'] ?? $field_key));
    $variant_options = $variant_key === ''
      ? $section_options
      : (isset($section_options[$variant_key]) && is_array($section_options[$variant_key]) ? $section_options[$variant_key] : []);
    $value = array_key_exists($option_key, $variant_options)
      ? $variant_options[$option_key]
      : ($field['default'] ?? '');
    $name = Patlis_Core::OPTION_DESIGN_OPTIONS . '[' . $section_key . ']';
    if ($variant_key !== '') $name .= '[' . $variant_key . ']';
    $name .= '[' . $option_key . ']';
    $label = (string) ($field['label'] ?? $field_key);
    $description = trim((string) ($field['description'] ?? ''));

    echo '<div class="patlis-design-field">';
    if ($type !== 'checkbox') echo '<p><strong>' . esc_html($label) . '</strong></p>';
    if ($type !== 'checkbox' && $description !== '') {
      echo '<div class="description patlis-design-field-description">' . wp_kses_post(wpautop($description)) . '</div>';
    }

    if ($type === 'image') {
      $image_id = max(0, (int) $value);
      echo '<div class="patlis-design-image-field">';
      echo '<div class="patlis-design-image-preview">';
      if ($image_id > 0) echo wp_get_attachment_image($image_id, 'medium', false, ['class' => 'patlis-design-image']);
      echo '</div>';
      echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($image_id) . '">';
      echo '<p><button type="button" class="button patlis-design-image-select">' . esc_html__('Select image', 'patlis-core') . '</button> ';
      echo '<button type="button" class="button patlis-design-image-remove"' . ($image_id > 0 ? '' : ' hidden') . '>' . esc_html__('Remove', 'patlis-core') . '</button></p>';
      echo '</div></div>';
      return;
    }

    if ($type === 'checkbox') {
      echo '<input type="hidden" name="' . esc_attr($name) . '" value="0">';
      echo '<label class="patlis-design-field-checkbox"><input type="checkbox" name="' . esc_attr($name) . '" value="1"' . checked(!empty($value), true, false) . '> ' . esc_html($label) . '</label>';
      if ($description !== '') {
        echo '<div class="description patlis-design-field-description">' . wp_kses_post(wpautop($description)) . '</div>';
      }
      echo '</div>';
      return;
    }

    if ($type === 'range') {
      $min = isset($field['min']) && is_numeric($field['min']) ? (string) $field['min'] : '0';
      $max = isset($field['max']) && is_numeric($field['max']) ? (string) $field['max'] : '100';
      $step = isset($field['step']) && is_numeric($field['step']) ? (string) $field['step'] : '1';
      echo '<div class="patlis-design-range-field">';
      echo '<input type="range" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '">';
      echo '<output>' . esc_html((string) $value) . '</output>';
      echo '</div></div>';
      return;
    }

    if (in_array($type, ['text', 'textarea'], true)) {
      $values = is_array($value) ? $value : [];
      if (is_string($value) && $value !== '') $values[self::get_default_language()] = $value;

      foreach (self::get_languages() as $language => $language_label) {
        $translated_value = $values[$language] ?? '';
        $translated_name = $name . '[' . $language . ']';
        echo '<div class="patlis-design-language-field">';
        echo '<label><strong>' . esc_html($language_label) . '</strong></label>';
        if ($type === 'textarea') {
          echo '<textarea class="large-text" rows="4" name="' . esc_attr($translated_name) . '">' . esc_textarea((string) $translated_value) . '</textarea>';
        } else {
          echo '<input class="regular-text" type="text" name="' . esc_attr($translated_name) . '" value="' . esc_attr((string) $translated_value) . '">';
        }
        echo '</div>';
      }
    } else {
      $input_type = in_array($type, ['number', 'color'], true) ? $type : 'text';
      $number_attributes = '';
      if ($type === 'number') {
        $step = $field['step'] ?? 'any';
        if ($step === 'any' || is_numeric($step)) {
          $number_attributes .= ' step="' . esc_attr((string) $step) . '"';
        }
        foreach (['min', 'max'] as $attribute) {
          if (isset($field[$attribute]) && is_numeric($field[$attribute])) {
            $number_attributes .= ' ' . $attribute . '="' . esc_attr((string) $field[$attribute]) . '"';
          }
        }
      }
      echo '<input class="regular-text" type="' . esc_attr($input_type) . '" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '"' . $number_attributes . '>';
    }

    echo '</div>';
  }
}