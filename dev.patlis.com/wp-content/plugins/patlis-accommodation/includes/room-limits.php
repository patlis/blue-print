<?php
if (!defined('ABSPATH')) exit;

function patlis_acc_room_limit(): int {
    return function_exists('patlis_version_get_room_limit') ? patlis_version_get_room_limit() : 0;
}

function patlis_acc_room_limit_language(int $post_id = 0, $requested = null) {
    if (!function_exists('pll_get_post_language')
        || (function_exists('pll_is_translated_post_type') && !pll_is_translated_post_type('patlis_room'))) {
        return '';
    }

    if ($requested !== null) {
        if (!is_string($requested) && !is_int($requested)) {
            return new WP_Error('patlis_room_language_invalid', 'Choose a valid room language.', ['status' => 400]);
        }

        if (is_numeric($requested)) {
            $term = get_term((int) $requested, 'language');
            if (is_wp_error($term) || !$term) {
                return new WP_Error('patlis_room_language_invalid', 'Choose a valid room language.', ['status' => 400]);
            }
            $requested = $term->slug;
        }

        $language = sanitize_key($requested);
        if (!in_array($language, pll_languages_list(['fields' => 'slug']), true)) {
            return new WP_Error('patlis_room_language_invalid', 'Choose a valid room language.', ['status' => 400]);
        }
        return $language;
    }

    if ($post_id > 0) {
        $language = pll_get_post_language($post_id, 'slug');
        if (is_string($language) && $language !== '') {
            return $language;
        }
    } elseif (function_exists('pll_current_language')) {
        $language = pll_current_language('slug');
        if (is_string($language) && $language !== '') {
            return $language;
        }
    }

    $language = pll_default_language('slug');
    if (!is_string($language) || $language === '') {
        return new WP_Error('patlis_room_language_missing', 'Configure a default language before saving a room.', ['status' => 400]);
    }
    return $language;
}

function patlis_acc_room_limit_requested_language(array $postarr) {
    if (isset($postarr['patlis_room_limit_language'])) {
        return $postarr['patlis_room_limit_language'];
    }
    if (isset($postarr['tax_input']['language'])) {
        $terms = (array) $postarr['tax_input']['language'];
        return count($terms) === 1 ? reset($terms) : $terms;
    }
    foreach (['post_lang_choice', 'inline_lang_choice'] as $key) {
        if (isset($_POST[$key]) && $_POST[$key] !== '-1') {
            return wp_unslash($_POST[$key]);
        }
    }
    if (isset($_GET['inline_lang_choice']) && $_GET['inline_lang_choice'] !== '-1') {
        return wp_unslash($_GET['inline_lang_choice']);
    }
    foreach (['new_lang', 'lang'] as $key) {
        if ($key === 'lang' && !empty($postarr['ID'])) {
            continue;
        }
        if (isset($_GET[$key]) && $_GET[$key] !== 'all' && $_GET[$key] !== '') {
            return wp_unslash($_GET[$key]);
        }
    }
    return null;
}

function patlis_acc_room_limit_lock(string $language) {
    global $wpdb;
    static $locks = [];

    $name = 'patlis_room_' . md5(DB_NAME . ':' . $wpdb->prefix . ':' . $language);
    if (isset($locks[$name])) {
        return true;
    }

    // Hold the language slot through both the post write and Polylang's term assignment.
    if ((string) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, %d)', $name, 5)) !== '1') {
        return new WP_Error('patlis_room_limit_busy', 'Could not verify the room limit. Please try again.', ['status' => 503]);
    }

    $locks[$name] = true;
    add_action('shutdown', static function () use ($name): void {
        global $wpdb;
        if ((string) $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $name)) !== '1') {
            error_log('Patlis Accommodation: could not release the room-limit lock.');
        }
    });
    return true;
}

function patlis_acc_room_limit_check(int $post_id, string $language, string $status, bool $allow_existing = true) {
    $limit = patlis_acc_room_limit();
    if ($limit === 0 || in_array($status, ['trash', 'auto-draft', 'inherit'], true)) {
        return true;
    }

    $post = $post_id > 0 ? get_post($post_id) : null;
    if ($allow_existing && $post && $post->post_type === 'patlis_room'
        && !in_array($post->post_status, ['trash', 'auto-draft', 'inherit'], true)) {
        $existing_language = patlis_acc_room_limit_language($post_id);
        if (is_wp_error($existing_language)) {
            return $existing_language;
        }
        if ($existing_language === $language) {
            return true;
        }
    }

    $locked = patlis_acc_room_limit_lock($language);
    if (is_wp_error($locked)) {
        return $locked;
    }

    $rooms = get_posts([
        'post_type'              => 'patlis_room',
        'post_status'            => array_values(array_diff(get_post_stati(), ['trash', 'auto-draft', 'inherit'])),
        'posts_per_page'         => -1,
        'post__not_in'           => $post_id > 0 ? [$post_id] : [],
        'fields'                => 'ids',
        'lang'                  => '',
        'suppress_filters'      => true,
        'cache_results'         => false,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    global $wpdb;
    if ($wpdb->last_error !== '') {
        return new WP_Error('patlis_room_limit_query_failed', 'Could not verify the room limit. Please try again.', ['status' => 503]);
    }

    $count = 0;
    foreach ($rooms as $room_id) {
        $room_language = patlis_acc_room_limit_language((int) $room_id);
        if (is_wp_error($room_language)) {
            return $room_language;
        }
        if ($room_language === $language && ++$count >= $limit) {
            return new WP_Error(
                'patlis_room_limit_reached',
                sprintf('This website version allows only %d saved room(s) per language. Edit the existing room or move it to Trash before creating another.', $limit),
                ['status' => 409]
            );
        }
    }
    return true;
}

function patlis_acc_room_limit_die(WP_Error $error): void {
    $data = $error->get_error_data();
    wp_die($error, 'Room limit', ['response' => $data['status'], 'back_link' => true]);
}

add_action('load-post-new.php', function (): void {
    if (patlis_acc_room_limit() === 0 || ($_GET['post_type'] ?? '') !== 'patlis_room') {
        return;
    }
    $language = patlis_acc_room_limit_language(0, patlis_acc_room_limit_requested_language([]));
    $result = is_wp_error($language) ? $language : patlis_acc_room_limit_check(0, $language, 'draft');
    if (is_wp_error($result)) {
        patlis_acc_room_limit_die($result);
    }
});

add_filter('rest_pre_insert_patlis_room', function ($prepared_post, WP_REST_Request $request) {
    if (patlis_acc_room_limit() === 0 || is_wp_error($prepared_post)) {
        return $prepared_post;
    }
    $post_id = (int) $request->get_param('id');
    $language = patlis_acc_room_limit_language($post_id, $request->get_param('lang'));
    if (is_wp_error($language)) {
        return $language;
    }
    $post = $post_id > 0 ? get_post($post_id) : null;
    $status = $prepared_post->post_status ?? ($post ? $post->post_status : 'draft');
    $result = patlis_acc_room_limit_check($post_id, $language, $status);
    if (is_wp_error($result)) {
        return $result;
    }
    $prepared_post->patlis_room_limit_language = $language;
    return $prepared_post;
}, 10, 2);

add_filter('wp_insert_post_data', function (array $data, array $postarr): array {
    if (patlis_acc_room_limit() === 0 || $data['post_type'] !== 'patlis_room'
        || in_array($data['post_status'], ['trash', 'auto-draft', 'inherit'], true)) {
        return $data;
    }
    $post_id = (int) ($postarr['ID'] ?? 0);
    $language = patlis_acc_room_limit_language($post_id, patlis_acc_room_limit_requested_language($postarr));
    $result = is_wp_error($language) ? $language : patlis_acc_room_limit_check($post_id, $language, $data['post_status']);
    if (is_wp_error($result)) {
        patlis_acc_room_limit_die($result);
    }
    return $data;
}, 99, 2);

add_filter('pre_untrash_post', function ($override, WP_Post $post) {
    if ($override !== null || patlis_acc_room_limit() === 0 || $post->post_type !== 'patlis_room') {
        return $override;
    }
    $language = patlis_acc_room_limit_language((int) $post->ID);
    $result = is_wp_error($language) ? $language : patlis_acc_room_limit_check((int) $post->ID, $language, 'draft');
    if (is_wp_error($result)) {
        patlis_acc_room_limit_die($result);
    }
    return $override;
}, 10, 2);

// Polylang can change a language before saving the post (including Quick/Bulk Edit).
add_action('add_term_relationship', function ($post_id, $term_taxonomy_id, $taxonomy): void {
    if (patlis_acc_room_limit() === 0 || $taxonomy !== 'language' || get_post_type($post_id) !== 'patlis_room') {
        return;
    }
    $post = get_post($post_id);
    if (in_array($post->post_status, ['trash', 'auto-draft', 'inherit'], true)) {
        return;
    }
    $term = get_term_by('term_taxonomy_id', $term_taxonomy_id, 'language');
    if (!$term) {
        patlis_acc_room_limit_die(new WP_Error('patlis_room_language_invalid', 'Choose a valid room language.', ['status' => 400]));
        return;
    }
    $language = patlis_acc_room_limit_language((int) $post_id, $term->slug);
    $result = is_wp_error($language) ? $language : patlis_acc_room_limit_check((int) $post_id, $language, $post->post_status, false);
    if (is_wp_error($result)) {
        patlis_acc_room_limit_die($result);
    }
}, 10, 3);
