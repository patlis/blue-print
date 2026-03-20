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

    echo '<div class="wrap">';
    echo '<h1>Patlis Cookies – Integrations</h1>';
    if (!empty($_GET['patlis_saved'])) echo '<div class="notice notice-success is-dismissible"><p>Saved.</p></div>';

    echo '<form method="post" action="'. esc_url(admin_url('admin-post.php')) .'">';
    echo '<input type="hidden" name="action" value="patlis_cookies_save_integrations">';
    wp_nonce_field('patlis_cookies_save_integrations');

    echo '<table class="form-table" role="presentation">';

    // Enable banner
    echo '<tr>';
    echo '<th scope="row">Enable banner</th>';
    echo '<td><label>';
    echo '<input type="checkbox" name="patlis_cookies_integrations[enable_banner]" value="1" ' . checked($enable_banner, true, false) . ' />';
    echo '</label></td>';
    echo '</tr>';

    // GTM
    echo '<tr><th scope="row">GTM</th><td>';
    echo '<label style="margin-right:14px;">';
    echo '<input type="checkbox" name="patlis_cookies_integrations[gtm_enabled]" value="1" ' . checked($gtm_enabled, true, false) . ' /> ON';
    echo '</label>';
    echo '<input type="text" class="regular-text" placeholder="GTM-XXXXXXX" name="patlis_cookies_integrations[gtm_id]" value="' . esc_attr($gtm_id) . '" />';
    echo '</td></tr>';

    // GA4
    echo '<tr><th scope="row">GA4</th><td>';
    echo '<label style="margin-right:14px;">';
    echo '<input type="checkbox" name="patlis_cookies_integrations[ga4_enabled]" value="1" ' . checked($ga4_enabled, true, false) . ' /> ON';
    echo '</label>';
    echo '<input type="text" class="regular-text" placeholder="G-XXXXXXXXXX" name="patlis_cookies_integrations[ga4_id]" value="' . esc_attr($ga4_id) . '" />';
    echo '</td></tr>';

    // Pixel
    echo '<tr><th scope="row">Facebook Pixel</th><td>';
    echo '<label style="margin-right:14px;">';
    echo '<input type="checkbox" name="patlis_cookies_integrations[pixel_enabled]" value="1" ' . checked($pixel_enabled, true, false) . ' /> ON';
    echo '</label>';
    echo '<input type="text" class="regular-text" placeholder="Pixel ID" name="patlis_cookies_integrations[pixel_id]" value="' . esc_attr($pixel_id) . '" />';
    echo '</td></tr>';

    echo '</table>';

    submit_button('Save changes');
    echo '</form>';

    echo '</div>';
}
