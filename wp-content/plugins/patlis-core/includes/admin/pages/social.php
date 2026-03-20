<?php
if (!defined('ABSPATH')) exit;

final class Patlis_Admin_Page_Social {

  public static function sanitize($input): array {
    $in = is_array($input) ? $input : [];

    $out = [];
    $out['facebook']         = isset($in['facebook']) ? esc_url_raw($in['facebook']) : '';
    $out['instagram']        = isset($in['instagram']) ? esc_url_raw($in['instagram']) : '';
    $out['youtube']          = isset($in['youtube']) ? esc_url_raw($in['youtube']) : '';
    $out['tiktok']           = isset($in['tiktok']) ? esc_url_raw($in['tiktok']) : '';
    $out['google_business']  = isset($in['google_business']) ? esc_url_raw($in['google_business']) : '';
    $out['tripadvisor']      = isset($in['tripadvisor']) ? esc_url_raw($in['tripadvisor']) : '';
    $out['x_com']            = isset($in['x_com']) ? esc_url_raw($in['x_com']) : '';

    return $out;
  }

  public static function render(): void {
    if (!current_user_can('patlis_manage')) return;

    $opt = get_option(Patlis_Core::OPTION_SOCIAL, []);
    if (!is_array($opt)) $opt = [];

    ?>
    <div class="wrap">
      <h1>Social Media URLs</h1>

      <?php if (!empty($_GET['patlis_saved'])): ?>
        <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
      <?php endif; ?>

      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="patlis_save_social">
        <?php wp_nonce_field('patlis_save_social'); ?>

        <table class="form-table" role="presentation">

          <tr>
            <th scope="row"><label for="patlis_facebook">Facebook</label></th>
            <td>
              <input id="patlis_facebook" type="url" class="regular-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_SOCIAL); ?>[facebook]"
                value="<?php echo esc_attr($opt['facebook'] ?? ''); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_instagram">Instagram</label></th>
            <td>
              <input id="patlis_instagram" type="url" class="regular-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_SOCIAL); ?>[instagram]"
                value="<?php echo esc_attr($opt['instagram'] ?? ''); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_youtube">YouTube</label></th>
            <td>
              <input id="patlis_youtube" type="url" class="regular-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_SOCIAL); ?>[youtube]"
                value="<?php echo esc_attr($opt['youtube'] ?? ''); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_tiktok">TikTok</label></th>
            <td>
              <input id="patlis_tiktok" type="url" class="regular-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_SOCIAL); ?>[tiktok]"
                value="<?php echo esc_attr($opt['tiktok'] ?? ''); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_google_business">Google Business Profile</label></th>
            <td>
              <input id="patlis_google_business" type="url" class="regular-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_SOCIAL); ?>[google_business]"
                value="<?php echo esc_attr($opt['google_business'] ?? ''); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_tripadvisor">Tripadvisor</label></th>
            <td>
              <input id="patlis_tripadvisor" type="url" class="regular-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_SOCIAL); ?>[tripadvisor]"
                value="<?php echo esc_attr($opt['tripadvisor'] ?? ''); ?>">
            </td>
          </tr>

          <tr>
            <th scope="row"><label for="patlis_x_com">X (Twitter)</label></th>
            <td>
              <input id="patlis_x_com" type="url" class="regular-text"
                name="<?php echo esc_attr(Patlis_Core::OPTION_SOCIAL); ?>[x_com]"
                value="<?php echo esc_attr($opt['x_com'] ?? ''); ?>">
            </td>
          </tr>

        </table>

        <?php submit_button('Save'); ?>
      </form>
    </div>
    <?php
  }
}
