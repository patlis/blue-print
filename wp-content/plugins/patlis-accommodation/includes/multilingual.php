<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('patlis_get_post_translation_ids')) {
    /**
     * Get all translation IDs for a post (including itself).
     */
    function patlis_get_post_translation_ids(int $post_id): array
    {
        if ($post_id <= 0) {
            return [];
        }

        $ids = [$post_id];

        if (function_exists('pll_languages_list') && function_exists('pll_get_post')) {
            $langs = pll_languages_list(['fields' => 'slug']);

            if (is_array($langs)) {
                foreach ($langs as $lang) {
                    if (!is_string($lang) || $lang === '') {
                        continue;
                    }

                    $translated = (int) pll_get_post($post_id, $lang);
                    if ($translated > 0) {
                        $ids[] = $translated;
                    }
                }
            }
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }
}

if (!function_exists('patlis_parse_linked_rooms_ids')) {
    /**
     * Parse ACF linked rooms meta that may be stored as array, serialized array, or comma-separated string.
     */
    function patlis_parse_linked_rooms_ids($raw): array
    {
        if ($raw === null || $raw === '' || $raw === false || $raw === []) {
            return [];
        }

        $ids = [];

        if (is_array($raw)) {
            $ids = $raw;
        } elseif (is_string($raw)) {
            $unserialized = maybe_unserialize($raw);

            if (is_array($unserialized)) {
                $ids = $unserialized;
            } else {
                $parts = preg_split('/[\s,]+/', trim($raw));
                $ids = is_array($parts) ? $parts : [];
            }
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }
}

if (!function_exists('patlis_rates_query_for_current_room')) {
    /**
     * Rates query for single room pages:
     * - keeps packages with empty package_linked_rooms
     * - keeps packages linked to current room (or any translation of that room)
     */
    function patlis_rates_query_for_current_room(array $args): array
    {
        $input_args = $args;

        $queried_id = (int) get_queried_object_id();
        if ($queried_id > 0) {
            $exclude_ids = patlis_get_post_translation_ids($queried_id);
            if (!empty($exclude_ids)) {
                $existing_not_in = [];
                if (!empty($args['post__not_in']) && is_array($args['post__not_in'])) {
                    $existing_not_in = array_map('intval', $args['post__not_in']);
                }

                $args['post__not_in'] = array_values(array_unique(array_filter(array_merge(
                    $existing_not_in,
                    $exclude_ids
                ), function ($id) {
                    return (int) $id > 0;
                })));
            }
        }

        if (function_exists('patlis_fallback_posts_query')) {
            $args = patlis_fallback_posts_query($args);
        }

        $room_id = $queried_id;

        if ($room_id <= 0 || get_post_type($room_id) !== 'patlis_room') {
            return $args;
        }

        $room_ids = patlis_get_post_translation_ids($room_id);
        if (empty($room_ids)) {
            return $args;
        }

        $candidate_ids = get_posts(array_merge($args, [
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ]));

        if (empty($candidate_ids) || !is_array($candidate_ids)) {
            $args['post__in'] = [0];
            return $args;
        }

        $keep_ids = [];

        foreach ($candidate_ids as $rate_id) {
            $rate_id = (int) $rate_id;
            if ($rate_id <= 0) {
                continue;
            }

            $linked_raw = get_post_meta($rate_id, 'package_linked_rooms', true);
            $linked_ids = patlis_parse_linked_rooms_ids($linked_raw);

            if (empty($linked_ids)) {
                $keep_ids[] = $rate_id;
                continue;
            }

            if (!empty(array_intersect($linked_ids, $room_ids))) {
                $keep_ids[] = $rate_id;
            }
        }

        $args['post__in'] = !empty($keep_ids) ? array_values(array_unique($keep_ids)) : [0];

        // Preserve caller ordering if provided.
        if (!empty($input_args['meta_key'])) {
            $args['meta_key'] = $input_args['meta_key'];
        }
        if (!empty($input_args['orderby'])) {
            $args['orderby'] = $input_args['orderby'];
        }
        if (!empty($input_args['order'])) {
            $args['order'] = $input_args['order'];
        }

        return $args;
    }
}

if (!function_exists('patlis_acc_get_current_language_slug')) {
    /**
     * Current frontend language slug (Polylang), empty string when unavailable.
     */
    function patlis_acc_get_current_language_slug(): string
    {
        if (!function_exists('pll_current_language')) {
            return '';
        }

        $lang = pll_current_language('slug');
        return is_string($lang) ? trim($lang) : '';
    }
}

if (!function_exists('patlis_acc_get_default_language_slug')) {
    /**
     * Default site language slug (Polylang), empty string when unavailable.
     */
    function patlis_acc_get_default_language_slug(): string
    {
        if (!function_exists('pll_default_language')) {
            return '';
        }

        $lang = pll_default_language('slug');
        return is_string($lang) ? trim($lang) : '';
    }
}

if (!function_exists('patlis_acc_translate_post_id')) {
    /**
     * Translate post to target language with safe fallback to source ID.
     */
    function patlis_acc_translate_post_id(int $post_id, string $target_lang): int
    {
        if ($post_id <= 0) {
            return 0;
        }

        $target_lang = trim($target_lang);
        if ($target_lang === '' || !function_exists('pll_get_post')) {
            return $post_id;
        }

        $translated = (int) pll_get_post($post_id, $target_lang);
        return $translated > 0 ? $translated : $post_id;
    }
}

if (!function_exists('patlis_acc_room_id_to_default_language_front')) {
    /**
     * Resolve room ID to default language ID when translations exist.
     */
    function patlis_acc_room_id_to_default_language_front(int $room_id): int
    {
        if ($room_id <= 0) {
            return 0;
        }

        $default_lang = patlis_acc_get_default_language_slug();
        if ($default_lang === '' || !function_exists('pll_get_post')) {
            return $room_id;
        }

        $default_room_id = (int) pll_get_post($room_id, $default_lang);
        return $default_room_id > 0 ? $default_room_id : $room_id;
    }
}

if (!function_exists('patlis_acc_parse_room_rate_room_ids')) {
    /**
     * Parse linked room IDs from room-rate meta.
     */
    function patlis_acc_parse_room_rate_room_ids($raw): array
    {
        if ($raw === null || $raw === '' || $raw === false || $raw === []) {
            return [];
        }

        $ids = [];

        if (is_array($raw)) {
            $ids = $raw;
        } elseif (is_string($raw)) {
            $unserialized = maybe_unserialize($raw);

            if (is_array($unserialized)) {
                $ids = $unserialized;
            } else {
                $parts = preg_split('/[\s,]+/', trim($raw));
                $ids = is_array($parts) ? $parts : [];
            }
        }

        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }
}

if (!function_exists('patlis_acc_is_empty_meta_value')) {
    /**
     * Determine whether a meta value should be treated as empty for fallback.
     */
    function patlis_acc_is_empty_meta_value($value): bool
    {
        if ($value === null || $value === false) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return count($value) === 0;
        }

        return false;
    }
}

if (!function_exists('patlis_acc_get_room_rate_meta_with_translation_fallback')) {
    /**
     * Read room-rate shared meta from the source rate, with fallback to sibling translations.
     */
    function patlis_acc_get_room_rate_meta_with_translation_fallback(int $rate_id, string $meta_key)
    {
        if ($rate_id <= 0 || $meta_key === '') {
            return '';
        }

        $value = get_post_meta($rate_id, $meta_key, true);
        if (!patlis_acc_is_empty_meta_value($value)) {
            return $value;
        }

        $translation_ids = function_exists('patlis_get_post_translation_ids')
            ? patlis_get_post_translation_ids($rate_id)
            : [$rate_id];

        foreach ($translation_ids as $translation_id) {
            $translation_id = (int) $translation_id;
            if ($translation_id <= 0 || $translation_id === $rate_id) {
                continue;
            }

            $candidate = get_post_meta($translation_id, $meta_key, true);
            if (!patlis_acc_is_empty_meta_value($candidate)) {
                return $candidate;
            }
        }

        return $value;
    }
}

if (!function_exists('patlis_acc_get_room_rates_payload')) {
    /**
     * Build room rate rows for the current room page (or a given room ID).
     * Output rows are multilingual-aware and sorted by period priority.
     */
    function patlis_acc_get_room_rates_payload(?int $room_id = null): array
    {
        $room_id = (int) ($room_id ?? 0);
        if ($room_id <= 0) {
            $room_id = (int) get_queried_object_id();
        }

        if ($room_id <= 0 || get_post_type($room_id) !== 'patlis_room') {
            return [];
        }

        $default_room_id = patlis_acc_room_id_to_default_language_front($room_id);
        if ($default_room_id <= 0) {
            return [];
        }

        $current_lang = patlis_acc_get_current_language_slug();
        $default_lang = patlis_acc_get_default_language_slug();

        $rate_query_args = [
            'post_type'      => 'patlis_room_rate',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'lang'           => '',
            'suppress_filters' => true,
        ];

        $candidate_rate_ids = get_posts($rate_query_args);
        if (empty($candidate_rate_ids) || !is_array($candidate_rate_ids)) {
            return [];
        }

        $rows = [];
        $processed_groups = [];

        foreach ($candidate_rate_ids as $candidate_rate_id) {
            $candidate_rate_id = (int) $candidate_rate_id;
            if ($candidate_rate_id <= 0) {
                continue;
            }

            $translation_ids = function_exists('patlis_get_post_translation_ids')
                ? patlis_get_post_translation_ids($candidate_rate_id)
                : [$candidate_rate_id];

            $translation_ids = array_values(array_unique(array_filter(array_map('intval', $translation_ids), function ($id) {
                return $id > 0;
            })));

            if (empty($translation_ids)) {
                $translation_ids = [$candidate_rate_id];
            }

            sort($translation_ids, SORT_NUMERIC);
            $group_key = implode(':', $translation_ids);

            if (isset($processed_groups[$group_key])) {
                continue;
            }
            $processed_groups[$group_key] = true;

            $source_rate_id = $candidate_rate_id;
            if ($default_lang !== '' && function_exists('pll_get_post')) {
                $default_rate_id = (int) pll_get_post($candidate_rate_id, $default_lang);
                if ($default_rate_id > 0) {
                    $source_rate_id = $default_rate_id;
                }
            }

            $rate_active = (int) patlis_acc_get_room_rate_meta_with_translation_fallback($source_rate_id, 'patlis_acc_active');
            if ($rate_active !== 1) {
                continue;
            }

            $linked_rooms_raw = patlis_acc_get_room_rate_meta_with_translation_fallback($source_rate_id, 'patlis_acc_room_ids');
            $linked_rooms = patlis_acc_parse_room_rate_room_ids($linked_rooms_raw);
            $linked_rooms = array_values(array_unique(array_filter(array_map('patlis_acc_room_id_to_default_language_front', $linked_rooms), function ($id) {
                return (int) $id > 0;
            })));

            if (empty($linked_rooms) || !in_array($default_room_id, $linked_rooms, true)) {
                continue;
            }

            $source_period_id = (int) patlis_acc_get_room_rate_meta_with_translation_fallback($source_rate_id, 'patlis_acc_period_id');
            if ($source_period_id <= 0 || get_post_type($source_period_id) !== 'hotel_rate_periods') {
                continue;
            }

            if ((int) get_post_meta($source_period_id, 'hotel_rate_period_active', true) !== 1) {
                continue;
            }

            $render_rate_id = $source_rate_id;
            $render_period_id = $source_period_id;

            if (
                $current_lang !== ''
                && $default_lang !== ''
                && $current_lang !== $default_lang
                && function_exists('pll_get_post')
            ) {
                $render_rate_id = patlis_acc_translate_post_id($source_rate_id, $current_lang);
                $render_period_id = patlis_acc_translate_post_id($source_period_id, $current_lang);
            }

            $rate_post = get_post($render_rate_id);
            if (!($rate_post instanceof WP_Post) || $rate_post->post_type !== 'patlis_room_rate') {
                $rate_post = get_post($source_rate_id);
            }

            $period_post = get_post($render_period_id);
            if (!($period_post instanceof WP_Post) || $period_post->post_type !== 'hotel_rate_periods') {
                $period_post = get_post($source_period_id);
            }

            if (!($rate_post instanceof WP_Post) || !($period_post instanceof WP_Post)) {
                continue;
            }

            $get_period_int = function (string $key) use ($source_period_id, $render_period_id): int {
                $render_val = (int) get_post_meta($render_period_id, $key, true);
                if ($render_val > 0 || $key === 'hotel_rate_period_order') {
                    return $render_val;
                }

                return (int) get_post_meta($source_period_id, $key, true);
            };

            $priority = $get_period_int('hotel_rate_period_priority');
            if ($priority <= 0) {
                $priority = 1;
            }

            $format_dd = static function (int $value): string {
                $value = max(0, $value);
                return str_pad((string) $value, 2, '0', STR_PAD_LEFT);
            };

            $start_day = $get_period_int('hotel_rate_period_start_day');
            $start_month = $get_period_int('hotel_rate_period_start_month');
            $end_day = $get_period_int('hotel_rate_period_end_day');
            $end_month = $get_period_int('hotel_rate_period_end_month');

            $rows[] = [
                'rate_id' => (int) $rate_post->ID,
                'source_rate_id' => $source_rate_id,
                'period_id' => (int) $period_post->ID,
                'source_period_id' => $source_period_id,

                'period_name' => (string) get_the_title($period_post->ID),
                'period_start_day' => $format_dd($start_day),
                'period_start_month' => $format_dd($start_month),
                'period_end_day' => $format_dd($end_day),
                'period_end_month' => $format_dd($end_month),
                'period_priority' => $priority,
                'period_order' => max(0, (int) get_post_meta($source_period_id, 'hotel_rate_period_order', true)),

                'rate_price' => function_exists('patlis_format_currency')
                    ? patlis_format_currency((string) patlis_acc_get_room_rate_meta_with_translation_fallback($source_rate_id, 'patlis_acc_price'))
                    : (string) patlis_acc_get_room_rate_meta_with_translation_fallback($source_rate_id, 'patlis_acc_price'),
                'rate_price_raw' => (string) patlis_acc_get_room_rate_meta_with_translation_fallback($source_rate_id, 'patlis_acc_price'),
                'rate_price_type' => (int) patlis_acc_get_room_rate_meta_with_translation_fallback($source_rate_id, 'patlis_acc_price_type'),
                'rate_price_surfix' => (int) patlis_acc_get_room_rate_meta_with_translation_fallback($source_rate_id, 'patlis_acc_price_surfix'),
                'rate_min_nights' => (int) patlis_acc_get_room_rate_meta_with_translation_fallback($source_rate_id, 'patlis_acc_min_nights'),
                'rate_title' => (string) get_the_title($rate_post->ID),
                'rate_content' => function_exists('patlis_acc_prepare_rate_content')
                    ? patlis_acc_prepare_rate_content($rate_post)
                    : (string) $rate_post->post_content,
            ];
        }

        if (empty($rows)) {
            return [];
        }

        usort($rows, function (array $a, array $b): int {
            $priority_cmp = ((int) $a['period_priority']) <=> ((int) $b['period_priority']);
            if ($priority_cmp !== 0) {
                return $priority_cmp;
            }

            $order_cmp = ((int) $a['period_order']) <=> ((int) $b['period_order']);
            if ($order_cmp !== 0) {
                return $order_cmp;
            }

            $start_month_cmp = ((int) $a['period_start_month']) <=> ((int) $b['period_start_month']);
            if ($start_month_cmp !== 0) {
                return $start_month_cmp;
            }

            $start_day_cmp = ((int) $a['period_start_day']) <=> ((int) $b['period_start_day']);
            if ($start_day_cmp !== 0) {
                return $start_day_cmp;
            }

            return strcasecmp((string) $a['period_name'], (string) $b['period_name']);
        });

        return $rows;
    }
}

if (!function_exists('patlis_acc_prepare_rate_content')) {
    /**
     * Normalize rate content for JSON payload:
     * - Remove Gutenberg block comments.
     * - Remove leading/trailing empty paragraphs produced by editors/formatters.
     */
    function patlis_acc_prepare_rate_content(WP_Post $rate_post): string
    {
        $content = (string) $rate_post->post_content;
        if ($content === '') {
            return '';
        }

        if (function_exists('has_blocks') && function_exists('do_blocks') && has_blocks($content)) {
            $content = (string) do_blocks($content);
        }

        $content = preg_replace('/<!--\s*\/?wp:[^>]*-->/i', '', $content);
        $content = is_string($content) ? $content : '';
        $content = trim($content);

        $empty_p = '<p>(?:\s|&nbsp;|<br\s*\/?>)*<\/p>';
        $content = preg_replace('/^(?:\s*' . $empty_p . '\s*)+/i', '', $content);
        $content = preg_replace('/(?:\s*' . $empty_p . '\s*)+$/i', '', $content);

        return trim(is_string($content) ? $content : '');
    }
}

