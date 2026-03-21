<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_Basic {

  public static function sanitize($input): array {
    $in = is_array($input) ? $input : [];

    $out = [];
    $out['company_name'] = isset($in['company_name']) ? sanitize_text_field($in['company_name']) : '';
    $out['address']      = isset($in['address']) ? sanitize_text_field($in['address']) : '';
    $out['city']         = isset($in['city']) ? sanitize_text_field($in['city']) : '';
    $out['zip']          = isset($in['zip']) ? sanitize_text_field($in['zip']) : '';
    $out['email']        = isset($in['email']) ? sanitize_email($in['email']) : '';

    $out['phone']        = isset($in['phone']) ? sanitize_text_field($in['phone']) : '';
    $out['phone2']       = isset($in['phone2']) ? sanitize_text_field($in['phone2']) : '';
    $out['mobile']       = isset($in['mobile']) ? sanitize_text_field($in['mobile']) : '';
    $out['whatsapp']     = isset($in['whatsapp']) ? sanitize_text_field($in['whatsapp']) : '';
    $out['cordinates']          = isset($in['cordinates']) ? sanitize_text_field($in['cordinates']) : '';

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
          <a href="#" class="nav-tab nav-tab-active" data-tab="basic"><?php esc_html_e('Basic', 'patlis-core'); ?></a>
          <a href="#" class="nav-tab" data-tab="contact"><?php esc_html_e('Contact form', 'patlis-core'); ?></a>
          <a href="#" class="nav-tab" data-tab="currency"><?php esc_html_e('Currency', 'patlis-core'); ?></a>
        </nav>

        <div class="patlis-basic-tabs-panels">
          <div class="patlis-basic-tab-panel is-active" data-panel="basic">
            <table class="form-table" role="presentation">

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
                <th scope="row"><label for="patlis_cordinates">Cordinates</label></th>
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
                <th scope="row"><?php esc_html_e('Display the contact form', 'patlis-core'); ?></th>
                <td>
                  <label>
                    <input type="checkbox"
                      name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[show_contact_form]"
                      value="1" <?php checked(!empty($opt['show_contact_form'])); ?>>
                  </label>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_contact_form_recipient_email"><?php esc_html_e('Recipients email', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_contact_form_recipient_email" type="email" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[contact_form_recipient_email]"
                    value="<?php echo esc_attr($opt['contact_form_recipient_email'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_contact_form_email_subject"><?php esc_html_e('Email subject', 'patlis-core'); ?></label></th>
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
                <th scope="row"><label for="patlis_currency_symbol"><?php esc_html_e('Currency symbol', 'patlis-core'); ?></label></th>
                <td>
                  <input id="patlis_currency_symbol" type="text" class="regular-text"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[currency_symbol]"
                    value="<?php echo esc_attr($opt['currency_symbol'] ?? ''); ?>">
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_decimal_divider"><?php esc_html_e('Decimal divider', 'patlis-core'); ?></label></th>
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
                <th scope="row"><label for="patlis_currency_position"><?php esc_html_e('Currency position', 'patlis-core'); ?></label></th>
                <td>
                  <select id="patlis_currency_position"
                    name="<?php echo esc_attr(Patlis_Core::OPTION_BASIC); ?>[currency_position]">
                    <option value="after" <?php selected($pos, 'after'); ?>><?php esc_html_e('After the amount', 'patlis-core'); ?></option>
                    <option value="before" <?php selected($pos, 'before'); ?>><?php esc_html_e('Before the amount', 'patlis-core'); ?></option>
                  </select>
                </td>
              </tr>

              <tr>
                <th scope="row"><label for="patlis_decimals"><?php esc_html_e('Decimals', 'patlis-core'); ?></label></th>
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
        </div>

        <script>
          document.addEventListener('DOMContentLoaded', function () {
            var tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab[data-tab]');
            var panels = document.querySelectorAll('.patlis-basic-tab-panel[data-panel]');

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
          });
        </script>

        <?php submit_button('Save'); ?>
      </form>
    </div>
    <?php
  }
}