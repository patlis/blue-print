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
