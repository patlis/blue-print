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
