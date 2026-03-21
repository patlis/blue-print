<?php
if (!defined('ABSPATH')) exit;

/**
 * Options key:
 * - patlis_cookies_integrations
 */
 function patlis_cookies_integrations_defaults(): array {
    return [
        'enable_banner' => 1,

        'gtm_enabled'   => 0,
        'gtm_id'        => '',

        'ga4_enabled'   => 0,
        'ga4_id'        => '',

        'pixel_enabled' => 0,
        'pixel_id'      => '',
    ];
}

function patlis_cookies_sanitize_integrations($input): array {
    $in = is_array($input) ? $input : [];
    $out = patlis_cookies_integrations_defaults();

    $out['enable_banner'] = !empty($in['enable_banner']) ? 1 : 0;

    $out['gtm_enabled'] = !empty($in['gtm_enabled']) ? 1 : 0;
    $out['gtm_id']      = isset($in['gtm_id']) ? sanitize_text_field($in['gtm_id']) : '';

    $out['ga4_enabled'] = !empty($in['ga4_enabled']) ? 1 : 0;
    $out['ga4_id']      = isset($in['ga4_id']) ? sanitize_text_field($in['ga4_id']) : '';

    $out['pixel_enabled'] = !empty($in['pixel_enabled']) ? 1 : 0;
    $out['pixel_id']      = isset($in['pixel_id']) ? sanitize_text_field($in['pixel_id']) : '';

    return $out;
}



function patlis_cookies_render_integrations_page() {
    if (!current_user_can('patlis_manage')) return;

    $opt = wp_parse_args(get_option('patlis_cookies_integrations', []), patlis_cookies_integrations_defaults());

    $enable_banner = !empty($opt['enable_banner']);
    $gtm_enabled   = !empty($opt['gtm_enabled']);
    $gtm_id        = $opt['gtm_id'] ?? '';
    $ga4_enabled   = !empty($opt['ga4_enabled']);
    $ga4_id        = $opt['ga4_id'] ?? '';
    $pixel_enabled = !empty($opt['pixel_enabled']);
    $pixel_id      = $opt['pixel_id'] ?? '';
    ?>
    <div class="wrap">
        <h1>Cookie Settings</h1>

        <?php if (!empty($_GET['patlis_saved'])): ?>
            <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="patlis_cookies_save_integrations">
            <?php wp_nonce_field('patlis_cookies_save_integrations'); ?>

            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">Enable Cookie banner</th>
                    <td>
                        <label>
                            <input type="checkbox" name="patlis_cookies_integrations[enable_banner]" value="1" <?php checked($enable_banner); ?>>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Google Tag Manager</th>
                    <td>
                        <label style="margin-right:14px;">
                            <input type="checkbox" name="patlis_cookies_integrations[gtm_enabled]" value="1" <?php checked($gtm_enabled); ?>> ON
                        </label>
                        <input type="text" class="regular-text" placeholder="GTM-XXXXXXX" name="patlis_cookies_integrations[gtm_id]" value="<?php echo esc_attr($gtm_id); ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Google Analytics 4</th>
                    <td>
                        <label style="margin-right:14px;">
                            <input type="checkbox" name="patlis_cookies_integrations[ga4_enabled]" value="1" <?php checked($ga4_enabled); ?>> ON
                        </label>
                        <input type="text" class="regular-text" placeholder="G-XXXXXXXXXX" name="patlis_cookies_integrations[ga4_id]" value="<?php echo esc_attr($ga4_id); ?>">
                    </td>
                </tr>

                <tr>
                    <th scope="row">Facebook Pixel</th>
                    <td>
                        <label style="margin-right:14px;">
                            <input type="checkbox" name="patlis_cookies_integrations[pixel_enabled]" value="1" <?php checked($pixel_enabled); ?>> ON
                        </label>
                        <input type="text" class="regular-text" placeholder="Pixel ID" name="patlis_cookies_integrations[pixel_id]" value="<?php echo esc_attr($pixel_id); ?>">
                    </td>
                </tr>

            </table>

            <?php submit_button('Save changes'); ?>
        </form>
    </div>
    <?php
}
