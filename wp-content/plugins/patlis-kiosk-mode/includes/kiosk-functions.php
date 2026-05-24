<?php
if (!defined('ABSPATH')) exit;

/**
 * Manage kiosk cookie based on URL
 */
function patlis_kiosk_manage_cookie() {
    if (is_admin()) {
        return;
    }

    if (patlis_kiosk_is_bricks_builder_request()) {
        return;
    }

    // Set cookie automatically on kiosk landing pages
    if (patlis_kiosk_is_kiosk_landing_page()) {
        // Set cookie for 10 years (maximum reasonable duration)
        $expire = time() + (10 * 365 * 24 * 60 * 60);
        setcookie('patlis_kiosk', 'true', $expire, COOKIEPATH, COOKIE_DOMAIN);
        $_COOKIE['patlis_kiosk'] = 'true';
    }
}

/**
 * Check if kiosk mode is active
 */
function patlis_kiosk_is_active(): bool {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return false;
    }

    if (patlis_kiosk_is_bricks_builder_request()) {
        return false;
    }

    $has_cookie = isset($_COOKIE['patlis_kiosk']) && $_COOKIE['patlis_kiosk'] === 'true';

    return $has_cookie;
}

/**
 * Enqueue kiosk scripts and styles
 */
function patlis_kiosk_enqueue_scripts() {
    if (!patlis_kiosk_is_active()) {
        return;
    }

    if (patlis_kiosk_is_kiosk_landing_page()) {
        return;
    }

    // Enqueue JS
    wp_enqueue_script(
        'patlis-kiosk-script',
        PATLIS_KIOSK_ASSETS_URL . 'kiosk.js',
        [],
        PATLIS_KIOSK_VERSION,
        true
    );

    // Localize script with settings
    wp_localize_script('patlis-kiosk-script', 'PatlisKioskSettings', [
        'inactivityTimeout' => (int) get_option('patlis_kiosk_inactivity_timeout', 60),
        'redirectUrl'       => esc_url(home_url('/kiosk/')),
        'imageRedirectUrl'  => esc_url(patlis_kiosk_get_target_url()),
    ]);
}

/**
 * Output initialization script in footer
 */
function patlis_kiosk_init_script() {
    if (!patlis_kiosk_is_active()) {
        return;
    }

    if (patlis_kiosk_is_kiosk_landing_page()) {
        ?>
        <script>
        (function() {
            function getCookie(name) {
                var row = document.cookie.split('; ').find(function(item) {
                    return item.indexOf(name + '=') === 0;
                });
                return row ? row.substring(name.length + 1) : null;
            }

            function isFullAcceptCookie(raw) {
                if (!raw) {
                    return false;
                }

                try {
                    var v = JSON.parse(raw);
                    return !!(v && v.all === true && v.necessary === true && v.preferences === true && v.statistics === true && v.marketing === true);
                } catch (e) {
                    return false;
                }
            }

            var cookieRaw = getCookie('patlis-cookie');
            if (!isFullAcceptCookie(cookieRaw)) {
                var cookieValue = '{"all":true,"necessary":true,"preferences":true,"statistics":true,"marketing":true}';
                var date = new Date();
                date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
                document.cookie = 'patlis-cookie=' + cookieValue + '; path=/; expires=' + date.toUTCString() + '; SameSite=Lax';
                window.location.reload();
                return;
            }

            setTimeout(function() {
                window.location.reload();
            }, 15 * 60 * 1000); //SOS 1st number is for minutes
        })();
        </script>
        <?php
        return;
    }
    ?>
    <script>
    (function() {
        if (typeof PatlisKiosk !== 'undefined' && !window.PatlisKioskInitialized) {
            window.PatlisKioskInitialized = true;
            PatlisKiosk.init(PatlisKioskSettings);
        }
    })();
    </script>
    <?php
}

/**
 * Sanitize and validate settings
 */
function patlis_kiosk_sanitize_inactivity_timeout($value) {
    $value = (int) $value;
    return max(10, min($value, 600)); // Between 10 and 600 seconds
}

/**
 * Sanitize target page setting.
 * Stores the default-language page ID when Polylang is available.
 */
function patlis_kiosk_sanitize_target_page_id($value): int {
    $page_id = absint($value);

    if ($page_id <= 0) {
        return 0;
    }

    $post = get_post($page_id);
    if (!$post || $post->post_type !== 'page') {
        return 0;
    }

    if (function_exists('pll_default_language') && function_exists('pll_get_post')) {
        $default_lang = pll_default_language('slug');
        if (is_string($default_lang) && $default_lang !== '') {
            $default_page_id = (int) pll_get_post($page_id, $default_lang);
            if ($default_page_id > 0) {
                $page_id = $default_page_id;
            }
        }
    }

    return $page_id;
}

/**
 * Sanitize slides mode setting.
 */
function patlis_kiosk_sanitize_slide_mode($value): string {
    $mode = sanitize_key((string) $value);
    return in_array($mode, ['normal', 'single'], true) ? $mode : 'normal';
}

/**
 * Sanitize single slide ID setting.
 */
function patlis_kiosk_sanitize_single_slide_id($value): int {
    $slide_id = absint($value);

    if ($slide_id <= 0) {
        return 0;
    }

    $post = get_post($slide_id);
    if (!$post || $post->post_type !== 'kiosk_slide' || $post->post_status !== 'publish') {
        return 0;
    }

    return $slide_id;
}

/**
 * Sanitize single-mode datetime values (datetime-local).
 */
function patlis_kiosk_sanitize_single_mode_datetime($value): string {
    $raw = sanitize_text_field((string) $value);
    if ($raw === '') {
        return '';
    }

    $timezone = wp_timezone();
    $dt = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $raw, $timezone);

    if (!$dt instanceof DateTimeImmutable) {
        return '';
    }

    return $dt->format('Y-m-d\\TH:i');
}

/**
 * Decide if frontend should force single-slide mode.
 * Rules:
 * - If mode is "single", force single mode.
 * - If mode is "normal" and both start/end are empty, stay in normal mode.
 * - If mode is "normal" and at least one date exists, force single mode only while
 *   current WP time is within the date window.
 */
function patlis_kiosk_should_force_single_mode(): bool {
    $mode = (string) get_option('patlis_kiosk_slide_mode', 'normal');
    if ($mode === 'single') {
        return true;
    }

    $start_raw = (string) get_option('patlis_kiosk_single_mode_start', '');
    $end_raw = (string) get_option('patlis_kiosk_single_mode_end', '');

    if ($start_raw === '' && $end_raw === '') {
        return false;
    }

    $timezone = wp_timezone();
    $start_dt = $start_raw !== '' ? DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $start_raw, $timezone) : null;
    $end_dt = $end_raw !== '' ? DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $end_raw, $timezone) : null;

    if (($start_raw !== '' && !$start_dt instanceof DateTimeImmutable) || ($end_raw !== '' && !$end_dt instanceof DateTimeImmutable)) {
        return false;
    }

    if ($start_dt instanceof DateTimeImmutable && $end_dt instanceof DateTimeImmutable && $end_dt < $start_dt) {
        return false;
    }

    $now = current_datetime();

    if ($start_dt instanceof DateTimeImmutable && $now < $start_dt) {
        return false;
    }

    if ($end_dt instanceof DateTimeImmutable && $now > $end_dt) {
        return false;
    }

    return true;
}

/**
 * Frontend override: return only the configured single slide when single mode is active.
 */
function patlis_kiosk_apply_single_slide_override($query) {
    if (is_admin()) {
        return;
    }

    if (wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    if (!patlis_kiosk_should_force_single_mode()) {
        return;
    }

    $single_slide_id = (int) get_option('patlis_kiosk_single_slide_id', 0);
    if ($single_slide_id <= 0) {
        return;
    }

    // Never alter the main page query itself.
    if ($query->is_main_query() && ($query->is_page() || $query->is_singular())) {
        return;
    }

    $post_type = $query->get('post_type');

    // If query targets another post type explicitly, skip.
    if (is_string($post_type) && $post_type !== '' && $post_type !== 'kiosk_slide' && $post_type !== 'any') {
        return;
    }

    if (is_array($post_type) && !in_array('kiosk_slide', $post_type, true)) {
        return;
    }

    // Bricks/custom loops may omit post_type; enforce kiosk_slide in that case.
    if (empty($post_type) || $post_type === 'any') {
        $query->set('post_type', 'kiosk_slide');
    }

    $query->set('post__in', [$single_slide_id]);
    $query->set('orderby', 'post__in');
    $query->set('posts_per_page', 1);
}
add_action('pre_get_posts', 'patlis_kiosk_apply_single_slide_override');

/**
 * Resolve redirect target URL for a specific language.
 */
function patlis_kiosk_get_target_url_for_language(string $lang_slug = ''): string {
    $page_id = (int) get_option('patlis_kiosk_target_page_id', 0);

    if ($page_id > 0) {
        $resolved_page_id = $page_id;

        if ($lang_slug !== '' && function_exists('pll_get_post')) {
            $translated_page_id = (int) pll_get_post($page_id, $lang_slug);
            if ($translated_page_id > 0) {
                $resolved_page_id = $translated_page_id;
            }
        }

        $permalink = get_permalink($resolved_page_id);
        if (is_string($permalink) && $permalink !== '') {
            return $permalink;
        }
    }

    return home_url('/kiosk/');
}

/**
 * Resolve redirect target URL from settings for current language.
 */
function patlis_kiosk_get_target_url(): string {
    $current_lang = '';

    if (function_exists('pll_current_language')) {
        $lang = pll_current_language('slug');
        if (is_string($lang) && $lang !== '') {
            $current_lang = $lang;
        }
    }

    return patlis_kiosk_get_target_url_for_language($current_lang);
}

/**
 * Return target links for active languages.
 * Useful for query loops, language buttons, and custom switchers.
 */
function patlis_kiosk_get_target_links_by_language(): array {
    $links = [];

    if (!function_exists('pll_the_languages')) {
        return [[
            'lang_slug' => 'default',
            'lang_name' => 'Default',
            'flag_url'  => '',
            'url'       => patlis_kiosk_get_target_url_for_language(''),
        ]];
    }

    $languages = pll_the_languages([
        'raw' => 1,
        'hide_if_empty' => 0,
        'hide_if_no_translation' => 0,
        'echo' => 0,
    ]);

    if (!is_array($languages)) {
        return [];
    }

    $allowed_languages = function_exists('patlis_get_site_visible_language_slugs') ? patlis_get_site_visible_language_slugs() : [];

    foreach ($languages as $language) {
        if (!is_array($language)) {
            continue;
        }

        $slug = isset($language['slug']) && is_string($language['slug']) ? sanitize_key($language['slug']) : '';
        if ($slug === '') {
            continue;
        }

        if (!empty($allowed_languages) && !in_array($slug, $allowed_languages, true)) {
            continue;
        }

        $name = isset($language['name']) && is_string($language['name']) ? $language['name'] : strtoupper($slug);

        $flag_raw = '';
        if (isset($language['flag_url']) && is_string($language['flag_url'])) {
            $flag_raw = $language['flag_url'];
        } elseif (isset($language['flag']) && is_string($language['flag'])) {
            $flag_raw = $language['flag'];
        }

        $flag_url = '';
        if ($flag_raw !== '') {
            if (preg_match('/src=["\']([^"\']+)["\']/i', $flag_raw, $matches) && !empty($matches[1])) {
                $flag_url = (string) $matches[1];
            } elseif (filter_var($flag_raw, FILTER_VALIDATE_URL)) {
                $flag_url = $flag_raw;
            }
        }

        $links[] = [
            'lang_slug' => $slug,
            'lang_name' => $name,
            'flag_url'  => $flag_url,
            'url'       => patlis_kiosk_get_target_url_for_language($slug),
        ];
    }

    return $links;
}

/**
 * Whitelist kiosk helper function in Bricks Builder.
 */
add_filter('bricks/code/echo_function_names', function ($functions) {
    if (empty($functions) || !is_array($functions)) {
        $functions = [];
    }
    
    $functions[] = 'patlis_kiosk_get_target_links_by_language';
    
    return array_unique($functions);
});

/**
 * Add kiosk-mode class to body on kiosk pages
 */
function patlis_kiosk_add_body_class($classes) {
    if (is_admin()) {
        return $classes;
    }

    if (patlis_kiosk_is_kiosk_landing_page()) {
        $classes[] = 'kiosk-mode';
    }

    return $classes;
}

/**
 * Check whether current request path is kiosk landing page.
 */
function patlis_kiosk_is_kiosk_landing_page(): bool {
    if (!isset($_SERVER['REQUEST_URI'])) {
        return false;
    }

    $request_uri = strtolower(sanitize_text_field((string) $_SERVER['REQUEST_URI']));

    // Match /kiosk, /kiosk/, /en/kiosk, /en/kiosk/, etc.
    return (bool) preg_match('/\/kiosk(\/)?(\?|$)/i', $request_uri);
}

/**
 * Skip kiosk behavior inside Bricks builder/editor contexts.
 */
function patlis_kiosk_is_bricks_builder_request(): bool {
    if (function_exists('bricks_is_builder_main') && bricks_is_builder_main()) {
        return true;
    }

    $bricks_param = isset($_GET['bricks']) ? strtolower(sanitize_text_field((string) $_GET['bricks'])) : '';
    if ($bricks_param === 'run') {
        return true;
    }

    $builder_param = isset($_GET['builder']) ? strtolower(sanitize_text_field((string) $_GET['builder'])) : '';
    if ($builder_param === 'true' || $builder_param === '1') {
        return true;
    }

    return false;
}
