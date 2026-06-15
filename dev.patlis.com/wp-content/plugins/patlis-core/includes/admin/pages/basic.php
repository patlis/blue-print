<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_Basic {

  public static function sanitize($input): array {
    $in = is_array($input) ? $input : [];

    $out = [];
    $out['logo_image_id']   = isset($in['logo_image_id']) ? max(0, (int)$in['logo_image_id']) : 0;
    $out['company_name'] = isset($in['company_name']) ? sanitize_text_field($in['company_name']) : '';
    $out['address']      = isset($in['address']) ? sanitize_text_field($in['address']) : '';
    $out['city']         = isset($in['city']) ? sanitize_text_field($in['city']) : '';
    $out['zip']          = isset($in['zip']) ? sanitize_text_field($in['zip']) : '';
    $out['email']        = isset($in['email']) ? sanitize_email($in['email']) : '';

    $out['phone']        = isset($in['phone']) ? sanitize_text_field($in['phone']) : '';
    $out['phone2']       = isset($in['phone2']) ? sanitize_text_field($in['phone2']) : '';
    $out['mobile']       = isset($in['mobile']) ? sanitize_text_field($in['mobile']) : '';
    $out['whatsapp']     = isset($in['whatsapp']) ? sanitize_text_field($in['whatsapp']) : '';
    $out['cordinates']   = isset($in['cordinates']) ? sanitize_text_field($in['cordinates']) : '';

    $out['timezone']     = isset($in['timezone']) ? sanitize_text_field($in['timezone']) : wp_timezone_string();

    $out['show_contact_form'] = !empty($in['show_contact_form']) ? 1 : 0;
    $out['contact_form_recipient_email'] = isset($in['contact_form_recipient_email']) ? sanitize_email($in['contact_form_recipient_email']) : '';
    $out['contact_form_email_subject']   = isset($in['contact_form_email_subject']) ? sanitize_text_field($in['contact_form_email_subject']) : '';

    /* Currency settings */
    $out['currency_symbol']   = isset($in['currency_symbol']) ? sanitize_text_field($in['currency_symbol']) : '';
    $out['decimal_divider']   = isset($in['decimal_divider']) ? sanitize_text_field($in['decimal_divider']) : '';
    $out['currency_position'] = isset($in['currency_position']) ? sanitize_text_field($in['currency_position']) : '';
    
    /* Number format settings */
    $out['decimals'] = isset($in['decimals']) ? (int) $in['decimals'] : 2;
    if ($out['decimals'] < 0) $out['decimals'] = 0;
    if ($out['decimals'] > 2) $out['decimals'] = 2;

    $allowed_appearance_modes = ['light_dark', 'light_only', 'dark_only'];
    $out['appearance_mode'] = isset($in['appearance_mode']) ? sanitize_key((string) $in['appearance_mode']) : 'light_dark';
    if (!in_array($out['appearance_mode'], $allowed_appearance_modes, true)) {
      $out['appearance_mode'] = 'light_dark';
    }

    $out['show_legal_notice']   = !empty($in['show_legal_notice'])   ? 1 : 0;
    $out['show_privacy_policy'] = !empty($in['show_privacy_policy']) ? 1 : 0;
    $out['show_terms_of_use']   = !empty($in['show_terms_of_use'])   ? 1 : 0;

    return $out;
  }

  public static function render(): void {
    if (!current_user_can('patlis_manage')) return;

    $opt = get_option(Patlis_Core::OPTION_BASIC, []);
    if (!is_array($opt)) $opt = [];

    $tz_selected = isset($opt['timezone']) && is_string($opt['timezone']) && $opt['timezone'] !== ''
      ? $opt['timezone']
      : wp_timezone_string();

    $divider  = $opt['decimal_divider'] ?? ',';
    $pos      = $opt['currency_position'] ?? 'after';
    $decimals = isset($opt['decimals']) ? (int) $opt['decimals'] : 2;
    $appearance_mode = isset($opt['appearance_mode']) ? (string) $opt['appearance_mode'] : 'light_dark';
    $logo_image_id = isset($opt['logo_image_id']) ? (int)$opt['logo_image_id'] : 0;
    $logo_preview = $logo_image_id > 0
      ? wp_get_attachment_image($logo_image_id, 'thumbnail', false, ['style' => 'max-width:120px;height:auto;border:1px solid #ddd;padding:2px;background:#fff;'])
      : '';

    wp_enqueue_media();

    // Resolve tab labels early; on some installs late gettext calls inside hidden tab panels return source strings.
    $labels = [
      'basic'                    => __('Basic', 'patlis-core'),
      'contact_form'             => __('Contact form', 'patlis-core'),
      'currency'                 => __('Currency', 'patlis-core'),
      'display_contact_form'     => __('Display the contact form', 'patlis-core'),
      'recipient_email'          => __('Recipient email', 'patlis-core'),
      'email_subject'            => __('Email subject', 'patlis-core'),
      'currency_symbol'          => __('Currency symbol', 'patlis-core'),
      'decimal_divider'          => __('Decimal divider', 'patlis-core'),
      'currency_position'        => __('Currency position', 'patlis-core'),
      'after_the_amount'         => __('After the amount', 'patlis-core'),
      'before_the_amount'        => __('Before the amount', 'patlis-core'),
      'decimals'                 => __('Decimals', 'patlis-core'),
      'appearance'               => __('Appearance', 'patlis-core'),
      'display_mode'             => __('Display mode', 'patlis-core'),
      'appearance_light_dark'    => __('Light & Dark mode', 'patlis-core'),
      'appearance_light_only'    => __('Light mode only', 'patlis-core'),
      'appearance_dark_only'     => __('Dark mode only', 'patlis-core'),
      'legal_links'              => __('Legal Links', 'patlis-core'),
      'show_legal_notice'        => __('Show Legal Notice page', 'patlis-core'),
      'show_privacy_policy'      => __('Show Privacy Policy page', 'patlis-core'),
      'show_terms_of_use'        => __('Show Terms of Use page', 'patlis-core'),
    ];

    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Basic settings', 'patlis-core'); ?></h1>

        <?php if (!empty($_GET['patlis_saved'])): ?>
            <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
        <?php endif; ?>


      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <input type="hidden" name="action" value="patlis_save_basic">
          <?php wp_nonce_field('patlis_save_basic'); ?>

        <style>
          .patlis-basic-tabs-panels {
            margin-top: 18px;
          }
          .patlis-basic-tab-panel {
            display: none;
          }
          .patlis-basic-tab-panel.is-active {
            display: block;
          }
        </style>

        <nav class="nav-tab-wrapper">
          <a href="#" class="nav-tab nav-tab-active" data-tab="basic"><?php echo esc_html($labels['basic']); ?></a>
          <a href="#" class="nav-tab" data-tab="contact"><?php echo esc_html($labels['contact_form']); ?></a>
          <a href="#" class="nav-tab" data-tab="currency"><?php echo esc_html($labels['currency']); ?></a>
          <a href="#" class="nav-tab" data-tab="appearance"><?php echo esc_html($labels['appearance']); ?></a>
          <a href="#" class="nav-tab" data-tab="legal"><?php echo esc_html($labels['legal_links']); ?></a>
        </nav>

        <div class="patlis-basic-tabs-panels">
          <div class="patlis-basic-tab-panel is-active" data-panel="basic">
            <table class="form-table" role="presentation">

              <tr>
                <th scope="row"><label><?php esc_html_e('Logo image', 'patlis-core'); ?></label></th>
                <td>
                  <div id="patlis_logo_preview"><?php echo $logo_preview; ?></div>
                  <input type="hidden"
                    id="patlis_logo_image_id"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[logo_image_id]"
                    value="<?php echo esc_attr($logo_image_id); ?>">
                  <p>
                    <button type="button" class="button" id="patlis_logo_select"><?php esc_html_e('Select image', 'patlis-core'); ?></button>
                    <button type="button" class="button" id="patlis_logo_remove" style="<?php echo $logo_image_id > 0 ? '' : 'display:none;'; ?>"><?php esc_html_e('Remove', 'patlis-core'); ?></button>
                  </p>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_company_name"><?php esc_html_e('Company name', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_company_name" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[company_name]"
                    value="<?php echo esc_attr($opt['company_name'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_address"><?php esc_html_e('Address', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_address" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[address]"
                    value="<?php echo esc_attr($opt['address'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_city"><?php esc_html_e('City', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_city" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[city]"
                    value="<?php echo esc_attr($opt['city'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_zip"><?php esc_html_e('Zip code', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_zip" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[zip]"
                    value="<?php echo esc_attr($opt['zip'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_email">E-mail</label></th>
                <td>
                  <input id="patlis_email" type="email" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[email]"
                    value="<?php echo esc_attr($opt['email'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_phone"><?php esc_html_e('Phone', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_phone" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[phone]"
                    value="<?php echo esc_attr($opt['phone'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_phone2"><?php esc_html_e('Phone-2', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_phone2" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[phone2]"
                    value="<?php echo esc_attr($opt['phone2'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_mobile"><?php esc_html_e('Mobile phone', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_mobile" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[mobile]"
                    value="<?php echo esc_attr($opt['mobile'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_whatsapp">WhatsApp</label></th>
                <td>
                  <input id="patlis_whatsapp" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[whatsapp]"
                    value="<?php echo esc_attr($opt['whatsapp'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_cordinates"><?php esc_html_e('Coordinates', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_cordinates" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[cordinates]"
                    value="<?php echo esc_attr($opt['cordinates'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_timezone"><?php esc_html_e('Time zone', 'patlis-core'); ?></label></th>
                <td>
                  <select id="patlis_timezone" name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[timezone]">
                    <?php echo wp_timezone_choice($tz_selected); ?>
                  </select>
                </td>
              </tr>
            </table>
          </div>

          <div class="patlis-basic-tab-panel" data-panel="contact">
            <table class="form-table" role="presentation">
              <tr>
                <th scope="row"><?php echo esc_html($labels['display_contact_form']); ?></th>
                <td>
                  <label>
                    <input type="checkbox"
                      name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[show_contact_form]"
                      value="1" <?php checked(!empty($opt['show_contact_form'])); ?>>
                  </label>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_contact_form_recipient_email"><?php echo esc_html($labels['recipient_email']); ?></label></th>
                <td>
                  <input id="patlis_contact_form_recipient_email" type="email" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[contact_form_recipient_email]"
                    value="<?php echo esc_attr($opt['contact_form_recipient_email'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_contact_form_email_subject"><?php echo esc_html($labels['email_subject']); ?></label></th>
                <td>
                  <input id="patlis_contact_form_email_subject" type="text" class="large-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[contact_form_email_subject]"
                    value="<?php echo esc_attr($opt['contact_form_email_subject'] ?? ''); ?>">
                </td>
              </tr>
            </table>
          </div>

          <div class="patlis-basic-tab-panel" data-panel="currency">
            <table class="form-table" role="presentation">
              <tr>
                <th scope="row"><label for="patlis_currency_symbol"><?php echo esc_html($labels['currency_symbol']); ?></label></th>
                <td>
                  <input id="patlis_currency_symbol" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[currency_symbol]"
                    value="<?php echo esc_attr($opt['currency_symbol'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_decimal_divider"><?php echo esc_html($labels['decimal_divider']); ?></label></th>
                <td>
                  <select id="patlis_decimal_divider"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[decimal_divider]">
                    <option value="," <?php selected($divider, ','); ?>>,</option>
                    <option value="." <?php selected($divider, '.'); ?>>.</option>
                    <option value="٫" <?php selected($divider, '٫'); ?>>٫</option>
                    <option value="'" <?php selected($divider, "'"); ?>>'</option>
                  </select>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_currency_position"><?php echo esc_html($labels['currency_position']); ?></label></th>
                <td>
                  <select id="patlis_currency_position"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[currency_position]">
                    <option value="after" <?php selected($pos, 'after'); ?>><?php echo esc_html($labels['after_the_amount']); ?></option>
                    <option value="before" <?php selected($pos, 'before'); ?>><?php echo esc_html($labels['before_the_amount']); ?></option>
                  </select>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_decimals"><?php echo esc_html($labels['decimals']); ?></label></th>
                <td>
                  <select id="patlis_decimals"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[decimals]">
                    <option value="0" <?php selected($decimals, 0); ?>>0</option>
                    <option value="1" <?php selected($decimals, 1); ?>>1</option>
                    <option value="2" <?php selected($decimals, 2); ?>>2</option>
                  </select>
                </td>
              </tr>
            </table>
          </div>

          <div class="patlis-basic-tab-panel" data-panel="appearance">
            <table class="form-table" role="presentation">
              <tr>
                <th scope="row"><label for="patlis_appearance_mode"><?php echo esc_html($labels['display_mode']); ?></label></th>
                <td>
                  <select id="patlis_appearance_mode"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[appearance_mode]">
                    <option value="light_dark" <?php selected($appearance_mode, 'light_dark'); ?>><?php echo esc_html($labels['appearance_light_dark']); ?></option>
                    <option value="light_only" <?php selected($appearance_mode, 'light_only'); ?>><?php echo esc_html($labels['appearance_light_only']); ?></option>
                    <option value="dark_only" <?php selected($appearance_mode, 'dark_only'); ?>><?php echo esc_html($labels['appearance_dark_only']); ?></option>
                  </select>
                </td>
              </tr>
            </table>
          </div>

          <div class="patlis-basic-tab-panel" data-panel="legal">
            <table class="form-table" role="presentation">
              <tr>
                <th scope="row"><?php echo esc_html($labels['show_legal_notice']); ?></th>
                <td>
                  <label>
                    <input type="checkbox"
                      name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[show_legal_notice]"
                      value="1" <?php checked(!array_key_exists('show_legal_notice', $opt) || !empty($opt['show_legal_notice'])); ?>
                  </label>
                </td>
              </tr>

              <tr>
                <th scope="row"><?php echo esc_html($labels['show_privacy_policy']); ?></th>
                <td>
                  <label>
                    <input type="checkbox"
                      name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[show_privacy_policy]"
                      value="1" <?php checked(!array_key_exists('show_privacy_policy', $opt) || !empty($opt['show_privacy_policy'])); ?>
                  </label>
                </td>
              </tr>

              <tr>
                <th scope="row"><?php echo esc_html($labels['show_terms_of_use']); ?></th>
                <td>
                  <label>
                    <input type="checkbox"
                      name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[show_terms_of_use]"
                      value="1" <?php checked(!empty($opt['show_terms_of_use'])); ?>>
                  </label>
                </td>
              </tr>
            </table>
          </div>
        </div>

        <script>
          document.addEventListener('DOMContentLoaded', function () {
            var tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab[data-tab]');
            var panels = document.querySelectorAll('.patlis-basic-tab-panel[data-panel]');
            var logoFrame   = null;
            var logoInput   = document.getElementById('patlis_logo_image_id');
            var logoPreview = document.getElementById('patlis_logo_preview');
            var logoSelect  = document.getElementById('patlis_logo_select');
            var logoRemove  = document.getElementById('patlis_logo_remove');

            function makeImageSetter(input, preview, removeBtn) {
              return function (attachment) {
                if (!input || !preview || !removeBtn) return;
                var imageId  = attachment && attachment.id ? attachment.id : 0;
                var imageUrl = '';
                if (attachment && attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url) {
                  imageUrl = attachment.sizes.thumbnail.url;
                } else if (attachment && attachment.url) {
                  imageUrl = attachment.url;
                }
                input.value      = imageId;
                preview.innerHTML = imageUrl ? '<img src="' + imageUrl + '" style="max-width:120px;height:auto;border:1px solid #ddd;padding:2px;background:#fff;" />' : '';
                removeBtn.style.display = imageId ? '' : 'none';
              };
            }

            var setLogoImage  = makeImageSetter(logoInput,  logoPreview,  logoRemove);

            tabs.forEach(function (tab) {
              tab.addEventListener('click', function (event) {
                event.preventDefault();

                var target = tab.getAttribute('data-tab');

                tabs.forEach(function (item) {
                  item.classList.remove('nav-tab-active');
                });

                panels.forEach(function (panel) {
                  panel.classList.toggle('is-active', panel.getAttribute('data-panel') === target);
                });

                tab.classList.add('nav-tab-active');
              });
            });

            if (logoSelect) {
              logoSelect.addEventListener('click', function (event) {
                event.preventDefault();

                if (logoFrame) {
                  logoFrame.open();
                  return;
                }

                logoFrame = wp.media({
                  title: '<?php echo esc_js(__('Select logo image', 'patlis-core')); ?>',
                  button: { text: '<?php echo esc_js(__('Use this image', 'patlis-core')); ?>' },
                  multiple: false
                });

                logoFrame.on('select', function () {
                  var attachment = logoFrame.state().get('selection').first().toJSON();
                  setLogoImage(attachment);
                });

                logoFrame.open();
              });
            }

            if (logoRemove) {
              logoRemove.addEventListener('click', function (event) {
                event.preventDefault();
                setLogoImage(null);
              });
            }

          });
        </script>

        <?php submit_button('Save'); ?>
      </form>
    </div>
    <?php
  }
}