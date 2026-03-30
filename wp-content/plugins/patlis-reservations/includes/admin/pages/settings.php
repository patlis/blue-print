<?php
if (!defined('ABSPATH')) exit;

/**
 * Patlis Reservations - Settings Page (Phase 1)
 * Admin UI only + saving + helper shortcode.
 *
 * IMPORTANT:
 * Shared functions (option_key/defaults/get_settings/get_notify_email) must live in includes/settings.php
 * and be loaded globally by the main plugin file.
 */

if (!function_exists('patlis_reservations_get_settings')) {
    // Hard stop: settings.php must be loaded before this file.
    return;
}

function patlis_reservations_page_slug_safe(): string
{
    // defined in includes/admin/menu.php
    if (function_exists('patlis_reservations_page_slug')) {
        return patlis_reservations_page_slug();
    }
    return 'patlis-reservations';
}

function patlis_reservations_sanitize_settings($input): array
{
    // Start from defaults
    $out = patlis_reservations_defaults();
    if (!is_array($input)) return $out;

    $mode = isset($input['mode']) ? sanitize_text_field($input['mode']) : 'off';
    $allowed_modes = ['off', 'simple', 'embed', 'redirect'];
    $out['mode'] = in_array($mode, $allowed_modes, true) ? $mode : 'off';

    $out['min_hours'] = isset($input['min_hours']) ? max(0, (int)$input['min_hours']) : (int)$out['min_hours'];

    $out['notify_email'] = isset($input['notify_email']) ? sanitize_email((string)$input['notify_email']) : (string)($out['notify_email'] ?? '');
    $out['email_subject'] = isset($input['email_subject']) ? sanitize_text_field((string)$input['email_subject']) : (string)($out['email_subject'] ?? '');

    // Store raw string, sanitize on output (shortcode later)
    $out['embed_code'] = isset($input['embed_code']) ? (string)$input['embed_code'] : (string)$out['embed_code'];
    $out['redirect_url'] = isset($input['redirect_url']) ? esc_url_raw((string)$input['redirect_url']) : (string)$out['redirect_url'];

    $minTime = isset($input['min_time']) ? trim((string)$input['min_time']) : (string)$out['min_time'];
    $maxTime = isset($input['max_time']) ? trim((string)$input['max_time']) : (string)$out['max_time'];

    $timeRe = '/^(?:[01]\d|2[0-3]):[0-5]\d$/';
    $out['min_time'] = preg_match($timeRe, $minTime) ? $minTime : (string)$out['min_time'];
    $out['max_time'] = preg_match($timeRe, $maxTime) ? $maxTime : (string)$out['max_time'];

    return $out;
}

function patlis_reservations_render_settings_page()
{
    if (!current_user_can('patlis_manage')) return;

        ?>
        <div class="wrap">
                <h1>Reservation Settings</h1>

                <?php if (!empty($_GET['patlis_saved'])): ?>
                        <div class="notice notice-success is-dismissible"><p>Saved.</p></div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="patlis_reservations_save_settings">
                        <?php wp_nonce_field('patlis_reservations_save_settings'); ?>

                        <style>
                            .patlis-res-tabs-panels {
                                margin-top: 18px;
                            }
                            .patlis-res-tab-panel {
                                display: none;
                            }
                            .patlis-res-tab-panel.is-active {
                                display: block;
                            }
                            .patlis-res-mode-outside {
                                margin: 16px 0 10px;
                                max-width: 460px;
                            }
                            .patlis-res-mode-outside label {
                                display: block;
                                margin-bottom: 6px;
                                font-weight: 600;
                            }
                        </style>

                        <div class="patlis-res-mode-outside">
                            <label for="patlis_res_mode" style="font-size:1.5rem">Mode</label>
                            <?php patlis_reservations_field_mode(); ?>
                        </div>

                        <nav class="nav-tab-wrapper">
                            <a href="#" class="nav-tab nav-tab-active" data-tab="simple">Simple system</a>
                            <a href="#" class="nav-tab" data-tab="embed">Embed</a>
                            <a href="#" class="nav-tab" data-tab="redirect">Redirect</a>
                        </nav>

                        <div class="patlis-res-tabs-panels">
                            <div class="patlis-res-tab-panel is-active" data-panel="simple">
                                <table class="form-table" role="presentation">
                                    <tr><th scope="row">Email recipient</th><td><?php patlis_reservations_field_notify_user(); ?></td></tr>
                                    <tr><th scope="row">Email subject</th><td><?php patlis_reservations_field_email_subject(); ?></td></tr>
                                    <tr><th scope="row">Minimum time before reservation (hours)</th><td><?php patlis_reservations_field_min_hours(); ?></td></tr>
                                    <tr><th scope="row">Min. time</th><td><?php patlis_reservations_field_min_time(); ?></td></tr>
                                    <tr><th scope="row">Max. time</th><td><?php patlis_reservations_field_max_time(); ?></td></tr>
                                </table>
                            </div>

                            <div class="patlis-res-tab-panel" data-panel="embed">
                                <table class="form-table" role="presentation">
                                    <tr><th scope="row">Code from the other company</th><td><?php patlis_reservations_field_embed_code(); ?></td></tr>
                                </table>
                            </div>

                            <div class="patlis-res-tab-panel" data-panel="redirect">
                                <table class="form-table" role="presentation">
                                    <tr><th scope="row">Redirect URL</th><td><?php patlis_reservations_field_redirect_url(); ?></td></tr>
                                </table>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var tabs = document.querySelectorAll('.nav-tab-wrapper .nav-tab[data-tab]');
                                var panels = document.querySelectorAll('.patlis-res-tab-panel[data-panel]');

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

                        <?php submit_button('Save settings'); ?>
                </form>
        </div>
        <?php
}

/** Fields */

function patlis_reservations_field_mode()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <select name="<?php echo esc_attr($key); ?>[mode]" id="patlis_res_mode">
        <option value="off" <?php selected($s['mode'], 'off'); ?>>Switched off</option>
        <option value="simple" <?php selected($s['mode'], 'simple'); ?>>With our simple system</option>
        <option value="embed" <?php selected($s['mode'], 'embed'); ?>>Code from the other company</option>
        <option value="redirect" <?php selected($s['mode'], 'redirect'); ?>>Redirect</option>
    </select>
    <?php
}

function patlis_reservations_field_notify_user()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();

    $value = !empty($s['notify_email']) && is_string($s['notify_email']) ? $s['notify_email'] : '';
    ?>
    <input type="email" class="regular-text"
           name="<?php echo esc_attr($key); ?>[notify_email]"
           value="<?php echo esc_attr($value); ?>"
           placeholder="name@example.com">
    <?php
}

function patlis_reservations_field_min_hours()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <input type="number" min="0" style="width:120px;"
           name="<?php echo esc_attr($key); ?>[min_hours]"
           value="<?php echo (int)$s['min_hours']; ?>">
    <?php
}

function patlis_reservations_field_email_subject()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <input type="text" class="regular-text"
           name="<?php echo esc_attr($key); ?>[email_subject]"
           value="<?php echo esc_attr((string)($s['email_subject'] ?? '')); ?>"
           placeholder="New reservation">
    <?php
}

function patlis_reservations_field_embed_code()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <textarea class="large-text code" rows="8"
              name="<?php echo esc_attr($key); ?>[embed_code]"
              placeholder='<iframe src="..." width="330" height="400" style="border:none;"></iframe>'><?php
        echo esc_textarea($s['embed_code']);
    ?></textarea>
    <p class="description">Paste hier the iframe/script from a third-party company.</p>
    <?php
}

function patlis_reservations_field_min_time()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <input type="time"
           name="<?php echo esc_attr($key); ?>[min_time]"
           value="<?php echo esc_attr($s['min_time']); ?>"
           step="900">
    <p class="description">E.g. 09:00</p>
    <?php
}

function patlis_reservations_field_max_time()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <input type="time"
           name="<?php echo esc_attr($key); ?>[max_time]"
           value="<?php echo esc_attr($s['max_time']); ?>"
           step="900">
    <p class="description">E.g. 20:00</p>
    <?php
}

function patlis_reservations_field_redirect_url()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <input type="url" class="regular-text"
           name="<?php echo esc_attr($key); ?>[redirect_url]"
           value="<?php echo esc_attr((string)($s['redirect_url'] ?? '')); ?>"
           placeholder="https://example.com/reservations">
    <p class="description">In Redirect mode, users will be directed to this URL</p>
    <?php
}

/**
 * Shortcode: [patlis_res_notify_email]
 * Returns plain email (for Bricks usage)
 */
add_shortcode('patlis_res_notify_email', function () {
    if (!function_exists('patlis_reservations_get_notify_email')) return '';
    return patlis_reservations_get_notify_email();
});