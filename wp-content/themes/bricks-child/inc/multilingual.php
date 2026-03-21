<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * STOP translate Bricks templates
 */
add_filter('pll_get_post_types', function ($post_types, $is_settings) {
    unset($post_types['bricks_template']);
    return $post_types;
}, 10, 2);



/**
 * Prevent Polylang from copying Bricks settings between translations
 */
add_filter('pll_copy_post_metas', function ($metas, $sync, $original_post_id) {
    $blocked = [
        '_bricks_template_settings',
        '_bricks_page_settings',
    ];

    return array_diff($metas, $blocked);
}, 10, 3);

/**
 * Option name for automatically collected translation keys
 */
function patlis_translation_option_name(): string
{
    return 'patlis_translation_keys';
}

/**
 * Default / predefined keys
 * Keep only stable, intentional keys here.
 */
function patlis_strings(): array
{
    return [
        'patlis_book_a_table',
        'patlis_contact',
        'patlis_reserve_now',
		'patlis_form_contact_success',
		'patlis_form_contact_error',
		'patlis_form_reservation_success',
		'patlis_form_reservation_error',
		'patlis_form_booking_success',
		'patlis_form_booking_error'
    ];
}

/**
 * Normalize translation key
 */
function patlis_normalize_translation_key(string $key): string
{
    $key = trim($key);
    $key = strtolower($key);
    $key = str_replace([' ', '-'], '_', $key);
    $key = preg_replace('/[^a-z0-9_]/', '', $key);
    $key = preg_replace('/_+/', '_', $key);
    $key = trim($key, '_');

    return $key;
}

/**
 * Check if translation key is valid
 */
function patlis_is_valid_translation_key(string $key): bool
{
    if ($key === '') {
        return false;
    }

    if (strlen($key) < 4 || strlen($key) > 100) {
        return false;
    }

    if (!preg_match('/^[a-z0-9_]+$/', $key)) {
        return false;
    }

    if (strpos($key, 'patlis_') !== 0) {
        return false;
    }

    return true;
}

/**
 * Decide if runtime key collection should run
 */
function patlis_should_collect_runtime_strings(): bool
{
    if (is_admin()) {
        return false;
    }

    if (wp_doing_ajax()) {
        return false;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }

    if (defined('DOING_CRON') && DOING_CRON) {
        return false;
    }

    /**
     * Optional extra safety:
     * Do not collect while logged-in.
     * This avoids accidental key collection while editing/testing as admin.
     */
    if (is_user_logged_in()) {
        return false;
    }

    return true;
}

/**
 * Get stored runtime keys
 */
function patlis_get_runtime_strings(): array
{
    $keys = get_option(patlis_translation_option_name(), []);

    if (!is_array($keys)) {
        return [];
    }

    $keys = array_filter(array_map('trim', $keys));
    $keys = array_map('patlis_normalize_translation_key', $keys);
    $keys = array_filter($keys, 'patlis_is_valid_translation_key');

    return array_values(array_unique($keys));
}

/**
 * Store one runtime key if missing
 */
function patlis_store_runtime_string(string $key): void
{
    if (!patlis_should_collect_runtime_strings()) {
        return;
    }

    $key = patlis_normalize_translation_key($key);

    if (!patlis_is_valid_translation_key($key)) {
        return;
    }

    $keys = patlis_get_runtime_strings();

    if (!in_array($key, $keys, true)) {
        $keys[] = $key;
        $keys = array_values(array_unique($keys));
        sort($keys);

        update_option(
            patlis_translation_option_name(),
            $keys,
            false
        );
    }
}

/**
 * Register all known keys in Polylang
 */
function patlis_register_all_strings(): void
{
    if (!function_exists('pll_register_string')) {
        return;
    }

    $keys = array_merge(
        patlis_strings(),
        patlis_get_runtime_strings()
    );

    $keys = array_map('patlis_normalize_translation_key', $keys);
    $keys = array_filter($keys, 'patlis_is_valid_translation_key');
    $keys = array_values(array_unique($keys));
    sort($keys);

    foreach ($keys as $key) {
        pll_register_string($key, $key, 'patlis');
    }
}
add_action('init', 'patlis_register_all_strings');

/**
 * Translation helper for Bricks
 * Usage: {echo:patlis_transl('patlis_footer_opening_hours')}
 */
if (!function_exists('patlis_transl')) {

    function patlis_transl(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            return '';
        }

        // Save runtime key
        patlis_store_runtime_string($key);

        if (!function_exists('pll__')) {
            return $key;
        }

        $translated = pll__($key);

        // If translation exists, return it
        if ($translated !== $key) {
            return $translated;
        }

        // Fallback to default language
        if (function_exists('pll_translate_string')) {

            $default_lang = patlis_get_default_language();

            if ($default_lang !== '') {

                $fallback = pll_translate_string($key, $default_lang);

                if (is_string($fallback) && $fallback !== '') {
                    return $fallback;
                }
            }
        }

        return $key;
    }

}

/**
 * Admin page: Patlis Translation Keys
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'tools.php',
        'Patlis Translation Keys',
        'Patlis Translation Keys',
        'manage_options',
        'patlis-translation-keys',
        'patlis_render_translation_keys_page'
    );
});

/**
 * Render translation keys cleanup page
 */
function patlis_render_translation_keys_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Not allowed.');
    }

    $option_name = patlis_translation_option_name();

    if (
        isset($_POST['patlis_translation_keys_nonce']) &&
        wp_verify_nonce($_POST['patlis_translation_keys_nonce'], 'patlis_translation_keys_action')
    ) {
        $keys = $_POST['keys'] ?? [];
        $keys = is_array($keys) ? $keys : [];

        $clean = [];

        foreach ($keys as $key) {
            $key = patlis_normalize_translation_key((string) $key);

            if (patlis_is_valid_translation_key($key)) {
                $clean[] = $key;
            }
        }

        $clean = array_values(array_unique($clean));
        sort($clean);

        update_option($option_name, $clean, false);

        echo '<div class="notice notice-success is-dismissible"><p>Translation keys updated.</p></div>';
    }

    $keys = get_option($option_name, []);
    $keys = is_array($keys) ? $keys : [];
    $keys = array_map('patlis_normalize_translation_key', $keys);
    $keys = array_filter($keys, 'patlis_is_valid_translation_key');
    $keys = array_values(array_unique($keys));
    sort($keys);
    ?>
    <div class="wrap">
        <h1>Patlis Translation Keys</h1>
        <p>Uncheck keys you no longer want to keep.</p>

        <form method="post">
            <?php wp_nonce_field('patlis_translation_keys_action', 'patlis_translation_keys_nonce'); ?>

            <table class="widefat striped" style="max-width: 900px;">
                <thead>
                    <tr>
                        <th style="width:60px;">Keep</th>
                        <th>Key</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($keys)) : ?>
                        <tr>
                            <td colspan="2">No stored runtime keys found.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($keys as $key) : ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="keys[]" value="<?php echo esc_attr($key); ?>" checked>
                                </td>
                                <td>
                                    <code><?php echo esc_html($key); ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p style="margin-top: 16px;">
                <button type="submit" class="button button-primary">Save keys</button>
            </p>
        </form>
    </div>
    <?php
}



/**
 * Get default Polylang language slug
 */
function patlis_get_default_language(): string
{
    if (function_exists('pll_default_language')) {
        $lang = pll_default_language();

        if (is_string($lang) && $lang !== '') {
            return $lang;
        }
    }

    return '';
}

/**
 * Get current Polylang language slug
 */
function patlis_get_current_language(): string
{
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();

        if (is_string($lang) && $lang !== '') {
            return $lang;
        }
    }

    return '';
}




/**
 * Build fallback post IDs:
 * current language translation if exists, otherwise default language post.
 */
function patlis_get_fallback_post_ids(array $args): array
{
    $current_lang = function_exists('pll_current_language') ? pll_current_language() : '';
    $default_lang = function_exists('pll_default_language') ? pll_default_language() : '';

    if (!$current_lang || !$default_lang || !function_exists('pll_get_post')) {
        return [];
    }

    $base_args = $args;

    // Δεν θέλουμε εσωτερικό pagination εδώ
    unset($base_args['paged'], $base_args['page'], $base_args['offset']);

    $base_args['posts_per_page']   = -1;
    $base_args['fields']           = 'ids';
    $base_args['suppress_filters'] = false;
    $base_args['no_found_rows']    = true;

    // Παίρνουμε ΜΟΝΟ τα posts της default γλώσσας σαν master list
    $default_posts = get_posts(array_merge($base_args, [
        'lang' => $default_lang,
    ]));

    $final_ids = [];

    foreach ($default_posts as $post_id) {
        $translated_id = pll_get_post($post_id, $current_lang);
        $final_ids[] = $translated_id ? $translated_id : $post_id;
    }

    return array_values(array_unique($final_ids));
}

/**
 * Build Bricks query with multilingual fallback
 *
 * Usage in Bricks Query editor:
 * return patlis_fallback_posts_query([
 *     'post_type' => 'services',
 *     'meta_key'  => 'service_order',
 *     'orderby'   => 'meta_value_num',
 *     'order'     => 'ASC',
 * ]);
 */
function patlis_fallback_posts_query(array $args): array
{
    if (!function_exists('pll_current_language') || !function_exists('pll_default_language')) {
        return $args;
    }

    $post_ids = patlis_get_fallback_post_ids($args);

    if (empty($post_ids)) {
        return [
            'post_type' => $args['post_type'] ?? 'post',
            'post__in'  => [0],
        ];
    }

    return array_merge($args, [
        'post__in'         => $post_ids,
        'orderby'          => 'post__in',
        'lang'             => '',
        'suppress_filters' => true,
    ]);
}

//** safe */
if (!function_exists('patlis_get_fallback_term_ids')) {
    /**
     * Build fallback term IDs:
     * current language translation if exists, otherwise default language term.
     */
    function patlis_get_fallback_term_ids(array $args): array
    {
        $current_lang = function_exists('pll_current_language') ? pll_current_language() : '';
        $default_lang = function_exists('pll_default_language') ? pll_default_language() : '';

        if ($current_lang === '' || $default_lang === '' || !function_exists('pll_get_term')) {
            return [];
        }

        $taxonomy = $args['taxonomy'] ?? '';

        if (is_array($taxonomy)) {
            $taxonomy = reset($taxonomy);
        }

        if (!is_string($taxonomy) || $taxonomy === '') {
            return [];
        }

        $term_args = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'fields'     => 'ids',
            'lang'       => $default_lang,
        ];

        if (isset($args['number'])) {
            $term_args['number'] = (int) $args['number'];
        }

        if (!empty($args['meta_key'])) {
            $term_args['meta_key'] = $args['meta_key'];
        }

        if (!empty($args['orderby'])) {
            $term_args['orderby'] = $args['orderby'];
        }

        if (!empty($args['order'])) {
            $term_args['order'] = $args['order'];
        }

        if (!empty($args['meta_query']) && is_array($args['meta_query'])) {
            $term_args['meta_query'] = $args['meta_query'];
        }

        if (!empty($args['childless'])) {
            $term_args['childless'] = true;
        }

        $default_terms = get_terms($term_args);

        if (is_wp_error($default_terms) || empty($default_terms) || !is_array($default_terms)) {
            return [];
        }

        $final_ids = [];

        foreach ($default_terms as $term_id) {
            $term_id = (int) $term_id;

            if ($term_id <= 0) {
                continue;
            }

            $translated_id = pll_get_term($term_id, $current_lang);
            $final_ids[] = $translated_id ? (int) $translated_id : $term_id;
        }

        return array_values(array_unique(array_filter($final_ids)));
    }
}

if (!function_exists('patlis_fallback_terms_query')) {
    /**
     * Build Bricks term query with multilingual fallback
     */
    function patlis_fallback_terms_query(array $args): array
    {
        if (!function_exists('pll_current_language') || !function_exists('pll_default_language')) {
            return $args;
        }

        $taxonomy = $args['taxonomy'] ?? '';

        if (is_array($taxonomy)) {
            $taxonomy = reset($taxonomy);
        }

        if (!is_string($taxonomy) || $taxonomy === '') {
            return $args;
        }

        $term_ids = patlis_get_fallback_term_ids($args);

        if (empty($term_ids)) {
            return [
                'taxonomy'   => $taxonomy,
                'include'    => [0],
                'hide_empty' => $args['hide_empty'] ?? false,
            ];
        }

        $query_args = [
            'taxonomy'   => $taxonomy,
            'include'    => $term_ids,
            'orderby'    => 'include',
            'hide_empty' => $args['hide_empty'] ?? false,
            'lang'       => '',
        ];

        if (isset($args['number'])) {
            $query_args['number'] = (int) $args['number'];
        }

        if (!empty($args['meta_key'])) {
            $query_args['meta_key'] = $args['meta_key'];
        }

        if (!empty($args['meta_query']) && is_array($args['meta_query'])) {
            $query_args['meta_query'] = $args['meta_query'];
        }

        if (!empty($args['childless'])) {
            $query_args['childless'] = true;
        }

        return $query_args;
    }
}

/**
 * Fallback content for translated posts:
 * If current language post_content is empty, return default language post_content.
 */
add_filter('the_content', function ($content) {
    if (is_admin()) {
        return $content;
    }

    if (!function_exists('pll_current_language') || !function_exists('pll_default_language') || !function_exists('pll_get_post')) {
        return $content;
    }

    if (!is_singular()) {
        return $content;
    }

    global $post;
    if (!($post instanceof WP_Post)) {
        return $content;
    }

    $is_effectively_empty = trim(wp_strip_all_tags((string) $content)) === '';
    if (!$is_effectively_empty) {
        return $content;
    }

    $current_lang = pll_current_language('slug');
    $default_lang = pll_default_language('slug');

    if (!is_string($current_lang) || !is_string($default_lang) || $current_lang === '' || $default_lang === '' || $current_lang === $default_lang) {
        return $content;
    }

    $default_post_id = (int) pll_get_post((int) $post->ID, $default_lang);
    if ($default_post_id <= 0 || $default_post_id === (int) $post->ID) {
        return $content;
    }

    $fallback_content = (string) get_post_field('post_content', $default_post_id);
    $fallback_content = trim($fallback_content);

    return $fallback_content !== '' ? $fallback_content : $content;
}, 1);
