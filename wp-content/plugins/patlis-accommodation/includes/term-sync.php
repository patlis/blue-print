<?php
if (!defined('ABSPATH')) exit;

/**
 * Sync room amenities from source room post to translated room posts.
 * Polylang doesn't automatically copy term relationships to translations, so we do it manually.
 * 
 * Taxonomy to sync:
 * - room_amenity (for patlis_room posts)
 */

function patlis_room_synced_taxonomies(): array
{
    return ['room_amenity'];
}

/**
 * Sync one taxonomy from source room post to all translated room posts.
 * Full replace behavior: checked are added, unchecked are removed.
 */
function patlis_sync_room_taxonomy_to_translations(int $source_post_id, string $taxonomy, array $source_term_ids): void
{
    static $is_syncing = false;

    if ($is_syncing) {
        return;
    }

    if (!function_exists('pll_get_post_translations')) {
        return;
    }

    $translations = pll_get_post_translations($source_post_id);
    if (!is_array($translations) || count($translations) < 2) {
        return;
    }

    $is_syncing = true;

    foreach ($translations as $translated_id) {
        $translated_id = (int) $translated_id;
        if ($translated_id <= 0 || $translated_id === $source_post_id) {
            continue;
        }

        $target_lang = function_exists('pll_get_post_language') ? pll_get_post_language($translated_id) : null;
        $translated_term_ids = [];

        foreach ($source_term_ids as $term_id) {
            $term_id = (int) $term_id;
            if ($term_id <= 0) {
                continue;
            }

            if (function_exists('pll_get_term') && is_string($target_lang) && $target_lang !== '') {
                $mapped_id = (int) pll_get_term($term_id, $target_lang);
                // Only keep terms that truly exist in target language.
                if ($mapped_id > 0) {
                    $translated_term_ids[] = $mapped_id;
                }
            } else {
                $translated_term_ids[] = $term_id;
            }
        }

        wp_set_object_terms(
            $translated_id,
            array_values(array_unique($translated_term_ids)),
            $taxonomy,
            false
        );
    }

    $is_syncing = false;
}

/**
 * Primary hook: fires when terms are actually written.
 * This reliably catches both checking and unchecking in Gutenberg/classic editors.
 */
add_action('set_object_terms', function ($object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids) {
    $object_id = (int) $object_id;
    if ($object_id <= 0) {
        return;
    }

    if (!in_array($taxonomy, patlis_room_synced_taxonomies(), true)) {
        return;
    }

    if (get_post_type($object_id) !== 'patlis_room') {
        return;
    }

    // Always use current DB state to include unchecked removals.
    $source_term_ids = wp_get_object_terms($object_id, $taxonomy, ['fields' => 'ids']);
    if (is_wp_error($source_term_ids)) {
        return;
    }

    patlis_sync_room_taxonomy_to_translations(
        $object_id,
        $taxonomy,
        array_values(array_map('intval', (array) $source_term_ids))
    );
}, 100, 6);

/**
 * Fallback hook: sync all relevant room taxonomies after post insert/update.
 * Useful for flows where term updates happen inside broader save routines.
 */
add_action('wp_after_insert_post', function ($post_id, $post, $update, $post_before) {
    if (!($post instanceof WP_Post) || (int) $post->ID !== (int) $post_id) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    if ($post->post_type !== 'patlis_room') {
        return;
    }

    foreach (patlis_room_synced_taxonomies() as $taxonomy) {
        $source_term_ids = wp_get_object_terms((int) $post_id, $taxonomy, ['fields' => 'ids']);
        if (is_wp_error($source_term_ids)) {
            continue;
        }

        patlis_sync_room_taxonomy_to_translations(
            (int) $post_id,
            $taxonomy,
            array_values(array_map('intval', (array) $source_term_ids))
        );
    }
}, 100, 4);
