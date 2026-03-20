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
    // Start from current merged settings (defaults + saved)
    $out = patlis_reservations_get_settings();
    if (!is_array($input)) return $out;

    $mode = isset($input['mode']) ? sanitize_text_field($input['mode']) : 'off';
    $allowed_modes = ['off', 'simple', 'embed'];
    $out['mode'] = in_array($mode, $allowed_modes, true) ? $mode : 'off';

    $out['min_hours'] = isset($input['min_hours']) ? max(0, (int)$input['min_hours']) : (int)$out['min_hours'];

    $uid = isset($input['notify_user_id']) ? (int)$input['notify_user_id'] : 0;
    if ($uid > 0 && get_user_by('id', $uid)) {
        $out['notify_user_id'] = $uid;
    } else {
        $out['notify_user_id'] = 0;
    }

    // Store raw string, sanitize on output (shortcode later)
    $out['embed_code'] = isset($input['embed_code']) ? (string)$input['embed_code'] : (string)$out['embed_code'];

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

    echo '<div class="wrap">';
    echo '<h1>Patlis Reservations</h1>';

    if (!empty($_GET['patlis_saved'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Saved.</p></div>';
    }

    echo '<p>Phase 1: μόνο Mode + λίγες βασικές ρυθμίσεις. Τα emails θα σταλούν από το Bricks (π.χ. Bricks Form → Email action).</p>';
    echo '<p>Μπορείς να πάρεις το email του επιλεγμένου χρήστη με shortcode: <code>[patlis_res_notify_email]</code></p>';

    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="patlis_reservations_save_settings">';
    wp_nonce_field('patlis_reservations_save_settings');

    echo '<table class="form-table" role="presentation">';

    echo '<tr><th scope="row">Mode</th><td>';
    patlis_reservations_field_mode();
    echo '</td></tr>';

    echo '<tr><th scope="row">Email recipient (WP user)</th><td>';
    patlis_reservations_field_notify_user();
    echo '</td></tr>';

    echo '<tr><th scope="row">Minimum time before reservation (hours)</th><td>';
    patlis_reservations_field_min_hours();
    echo '</td></tr>';

    echo '<tr><th scope="row">Min. time</th><td>';
    patlis_reservations_field_min_time();
    echo '</td></tr>';

    echo '<tr><th scope="row">Max. time</th><td>';
    patlis_reservations_field_max_time();
    echo '</td></tr>';

    echo '<tr><th scope="row">Code from the other company</th><td>';
    patlis_reservations_field_embed_code();
    echo '</td></tr>';

    echo '</table>';

    submit_button('Save settings');
    echo '</form>';

    echo '</div>';
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
    </select>
    <?php
}

function patlis_reservations_field_notify_user()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();

    // δείχνουμε μόνο admins/editors για να μη γίνει τεράστια λίστα
    $users = get_users([
        'orderby'  => 'display_name',
        'order'    => 'ASC',
        'role__in' => ['administrator', 'editor'],
        'fields'   => ['ID', 'display_name', 'user_email'],
    ]);
    ?>
    <div class="patlis-res-mode-field" data-mode="simple">
        <select name="<?php echo esc_attr($key); ?>[notify_user_id]" style="min-width: 320px;">
            <option value="0" <?php selected((int)$s['notify_user_id'], 0); ?>>— Select user —</option>
            <?php foreach ($users as $u): ?>
                <option value="<?php echo (int)$u->ID; ?>" <?php selected((int)$s['notify_user_id'], (int)$u->ID); ?>>
                    <?php echo esc_html($u->display_name . ' (' . $u->user_email . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <p class="description">
            Αυτό ΔΕΝ στέλνει email από μόνο του. Είναι για να “τραβάς” το email μέσα στο Bricks μέσω <code>[patlis_res_notify_email]</code>.
        </p>
    </div>
    <?php
}

function patlis_reservations_field_min_hours()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <div class="patlis-res-mode-field" data-mode="simple">
        <input type="number" min="0" style="width:120px;"
               name="<?php echo esc_attr($key); ?>[min_hours]"
               value="<?php echo (int)$s['min_hours']; ?>">
        <p class="description">Θα χρησιμοποιηθεί αργότερα για validation (π.χ. να μην κλείνει κάποιος σε λιγότερο από X ώρες).</p>
    </div>
    <?php
}

function patlis_reservations_field_embed_code()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <div class="patlis-res-mode-field" data-mode="embed">
        <textarea class="large-text code" rows="8"
                  name="<?php echo esc_attr($key); ?>[embed_code]"
                  placeholder='<iframe src="..." width="330" height="400" style="border:none;"></iframe>'><?php
            echo esc_textarea($s['embed_code']);
        ?></textarea>
        <p class="description">Εδώ μπαίνει iframe/script από τρίτη εταιρεία.</p>
    </div>
    <?php
}

function patlis_reservations_field_min_time()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <div class="patlis-res-mode-field" data-mode="simple">
        <input type="time"
               name="<?php echo esc_attr($key); ?>[min_time]"
               value="<?php echo esc_attr($s['min_time']); ?>"
               step="900">
        <p class="description">Π.χ. 09:00</p>
    </div>
    <?php
}

function patlis_reservations_field_max_time()
{
    $s = patlis_reservations_get_settings();
    $key = patlis_reservations_option_key();
    ?>
    <div class="patlis-res-mode-field" data-mode="simple">
        <input type="time"
               name="<?php echo esc_attr($key); ?>[max_time]"
               value="<?php echo esc_attr($s['max_time']); ?>"
               step="900">
        <p class="description">Π.χ. 20:00</p>
    </div>
    <?php
}

/**
 * Show/hide ανά mode (onchange)
 */
add_action('admin_footer', function () {
    if (!is_admin()) return;
    if (!isset($_GET['page']) || $_GET['page'] !== patlis_reservations_page_slug_safe()) return;
    ?>
    <script>
    (function(){
        function toggleByMode(){
            var sel = document.getElementById('patlis_res_mode');
            if(!sel) return;
            var mode = sel.value;

            document.querySelectorAll('.patlis-res-mode-field').forEach(function(el){
                var m = el.getAttribute('data-mode');
                el.style.display = (m === mode) ? 'block' : 'none';
            });
        }

        var sel = document.getElementById('patlis_res_mode');
        if(sel){
            sel.onchange = toggleByMode;
            toggleByMode();
        }
    })();
    </script>
    <?php
});

/**
 * Shortcode: [patlis_res_notify_email]
 * Returns plain email (for Bricks usage)
 */
add_shortcode('patlis_res_notify_email', function () {
    if (!function_exists('patlis_reservations_get_notify_email')) return '';
    return patlis_reservations_get_notify_email();
});