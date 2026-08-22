<?php
if (!defined('ABSPATH')) {
    exit;
}

final class Patlis_Admin_Page_Translations
{
    public static function render(): void
    {

        // Handle delete key (admin only) - must be before add key
        if (
            current_user_can('manage_options') &&
            isset($_POST['patlis_delete_translation_key_nonce']) &&
            wp_verify_nonce($_POST['patlis_delete_translation_key_nonce'], 'patlis_delete_translation_key') &&
            !empty($_POST['patlis_delete_key'])
        ) {
            $delete_key = patlis_normalize_translation_key(sanitize_text_field((string) $_POST['patlis_delete_key']));
            if (function_exists('patlis_core_delete_translation_key_from_db')) {
                patlis_core_delete_translation_key_from_db($delete_key);
            }
            wp_safe_redirect(admin_url('admin.php?page=patlis-translations&patlis_deleted=1'));
            exit;
        }

        // Handle add key
        if (
            current_user_can('manage_options') &&
            isset($_POST['patlis_add_translation_key_nonce']) &&
            wp_verify_nonce($_POST['patlis_add_translation_key_nonce'], 'patlis_add_translation_key') &&
            !empty($_POST['patlis_new_key'])
        ) {
            $new_key = patlis_normalize_translation_key(sanitize_text_field((string) $_POST['patlis_new_key']));

            if (patlis_is_valid_translation_key($new_key) && function_exists('patlis_core_upsert_translation') && function_exists('patlis_core_manual_translation_lang_marker')) {
                patlis_core_upsert_translation($new_key, patlis_core_manual_translation_lang_marker(), '');
            }

            wp_safe_redirect(admin_url('admin.php?page=patlis-translations&patlis_added=1'));
            exit;
        }

        if (!current_user_can('patlis_manage')) {
            wp_die(__('Not allowed.', 'patlis-core'));
        }

        $labels = [
            'translations'        => __('Translations', 'patlis-core'),
            'polylang_required'   => __('Polylang is required.', 'patlis-core'),
            'add_new'             => __('Add new', 'patlis-core'),
            'delete_key'          => __('Delete key', 'patlis-core'),
            'translations_saved'  => __('Translations saved.', 'patlis-core'),
            'new_key'             => __('New key:', 'patlis-core'),
            'add'                 => __('Add', 'patlis-core'),
            'cancel'              => __('Cancel', 'patlis-core'),
            'delete'              => __('Delete', 'patlis-core'),
            'filter_keys'         => __('Filter keys...', 'patlis-core'),
            'filter'              => __('Filter', 'patlis-core'),
            'clear'               => __('Clear filter', 'patlis-core'),
            'key'                 => __('Key', 'patlis-core'),
            'language'            => __('Language', 'patlis-core'),
            'translation'         => __('Translation', 'patlis-core'),
            'no_keys_found'       => __('No keys found.', 'patlis-core'),
            'default'             => __('Default', 'patlis-core'),
            'save_translations'   => __('Save translations', 'patlis-core'),
            'key_singular'        => __('%d key', 'patlis-core'),
            'key_plural'          => __('%d keys', 'patlis-core'),
            'matching'            => __('matching %s', 'patlis-core'),
            'page_of'             => __('Page %1$d of %2$d', 'patlis-core'),
        ];

        if (!function_exists('pll_languages_list')) {
            echo '<div class="wrap"><h1>' . esc_html($labels['translations']) . '</h1><div class="notice notice-error"><p>' . esc_html($labels['polylang_required']) . '</p></div></div>';
            return;
        }

        if (current_user_can('manage_options')) {
            $languages = pll_languages_list(['fields' => 'slug']);
        } elseif (function_exists('patlis_get_effective_language_slugs_for_current_user')) {
            $languages = patlis_get_effective_language_slugs_for_current_user();
        } else {
            $languages = pll_languages_list(['fields' => 'slug']);
        }

        $default = function_exists('patlis_get_default_language') ? patlis_get_default_language() : '';

        if ($default !== '' && in_array($default, $languages, true)) {
            $languages = array_values(array_unique(array_merge([$default], $languages)));
        }

        $keys = patlis_get_manual_translation_keys();
        $translations = function_exists('patlis_get_translations') ? patlis_get_translations() : [];

        // Search & pagination params
        $search   = isset($_GET['s']) ? sanitize_text_field((string) $_GET['s']) : '';
        $per_page = 20;
        $paged    = max(1, (int) ($_GET['paged'] ?? 1));

        if (
            isset($_POST['patlis_translations_nonce']) &&
            wp_verify_nonce($_POST['patlis_translations_nonce'], 'patlis_save_translations')
        ) {
            // Restore search/page state from hidden inputs after save
            $search = isset($_POST['_search']) ? sanitize_text_field((string) $_POST['_search']) : '';
            $paged  = max(1, (int) ($_POST['_paged'] ?? 1));

            $posted = $_POST['patlis_translations'] ?? [];

            // Only update keys visible on the current page (filtered + paged)
            $all_keys_for_save = patlis_get_manual_translation_keys();
            if ($search !== '') {
                $all_keys_for_save = array_values(array_filter($all_keys_for_save, function ($k) use ($search, $translations) {
                    if (stripos($k, $search) !== false) return true;
                    if (!empty($translations[$k]) && is_array($translations[$k])) {
                        foreach ($translations[$k] as $val) {
                            if (is_string($val) && stripos($val, $search) !== false) return true;
                        }
                    }
                    return false;
                }));
            }
            $offset_save = ($paged - 1) * $per_page;
            $page_keys   = array_slice($all_keys_for_save, $offset_save, $per_page);

            foreach ($page_keys as $key) {
                if (function_exists('patlis_core_delete_translation_row') && function_exists('patlis_core_manual_translation_lang_marker')) {
                    patlis_core_delete_translation_row($key, patlis_core_manual_translation_lang_marker());
                }

                $has_saved_language_row = false;

                foreach ($languages as $lang) {
                    $value = $posted[$key][$lang] ?? '';
                    $value = stripslashes((string) $value);
                    $value = trim(wp_kses_post($value));

                    if (function_exists('patlis_core_upsert_translation') && patlis_core_upsert_translation($key, $lang, $value)) {
                        $has_saved_language_row = true;
                    }
                }

                if (!$has_saved_language_row && function_exists('patlis_core_upsert_translation') && function_exists('patlis_core_manual_translation_lang_marker')) {
                    patlis_core_upsert_translation($key, patlis_core_manual_translation_lang_marker(), '');
                }
            }

            $translations = function_exists('patlis_get_translations') ? patlis_get_translations() : [];

            $redirect = admin_url('admin.php?page=patlis-translations&patlis_saved=1');
            if ($search !== '') $redirect = add_query_arg('s', urlencode($search), $redirect);
            if ($paged > 1)     $redirect = add_query_arg('paged', $paged, $redirect);
            wp_safe_redirect($redirect);
            exit;
        }

        // Apply search filter
        if ($search !== '') {
            $keys = array_values(array_filter($keys, function ($k) use ($search, $translations) {
                if (stripos($k, $search) !== false) return true;
                if (!empty($translations[$k]) && is_array($translations[$k])) {
                    foreach ($translations[$k] as $val) {
                        if (is_string($val) && stripos($val, $search) !== false) return true;
                    }
                }
                return false;
            }));
        }

        // Pagination
        $total_keys  = count($keys);
        $total_pages = max(1, (int) ceil($total_keys / $per_page));
        $paged       = min($paged, $total_pages);
        $offset      = ($paged - 1) * $per_page;
        $page_keys   = array_slice($keys, $offset, $per_page);

        $base_url = admin_url('admin.php?page=patlis-translations');
        if ($search !== '') $base_url = add_query_arg('s', urlencode($search), $base_url);

        ?>
        <div class="wrap">
            <h1 style="display:flex;align-items:center;justify-content:space-between;">
                <span><?php echo esc_html($labels['translations']); ?></span>
                <?php if (current_user_can('manage_options')) : ?>
                    <span>
                        <button id="patlis-add-translation-key" type="button" class="button button-secondary"><?php echo esc_html($labels['add_new']); ?></button>
                        <button id="patlis-delete-translation-key" type="button" class="button button-danger" style="margin-left:8px;"><?php echo esc_html($labels['delete_key']); ?></button>
                    </span>
                <?php endif; ?>
            </h1>

            <?php if (isset($_GET['patlis_saved'])) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($labels['translations_saved']); ?></p></div>
            <?php endif; ?>

            <?php if (current_user_can('manage_options')) : ?>
                <form method="post" id="patlis-add-key-form" style="display:none; margin-bottom:18px; max-width:600px;">
                    <?php wp_nonce_field('patlis_add_translation_key', 'patlis_add_translation_key_nonce'); ?>
                    <label for="patlis_new_key"><strong><?php echo esc_html($labels['new_key']); ?></strong></label>
                    <input type="text" id="patlis_new_key" name="patlis_new_key" style="width:300px;" required />
                    <button type="submit" class="button button-primary"><?php echo esc_html($labels['add']); ?></button>
                    <button type="button" class="button" id="patlis-cancel-add-key"><?php echo esc_html($labels['cancel']); ?></button>
                </form>

                <form method="post" id="patlis-delete-key-form" style="display:none; margin-bottom:18px; max-width:600px;">
                    <?php wp_nonce_field('patlis_delete_translation_key', 'patlis_delete_translation_key_nonce'); ?>
                    <label for="patlis_delete_key"><strong><?php echo esc_html($labels['delete_key']); ?></strong></label>
                    <input type="text" id="patlis_delete_key" name="patlis_delete_key" style="width:300px;" required />
                    <button type="submit" class="button button-danger"><?php echo esc_html($labels['delete']); ?></button>
                    <button type="button" class="button" id="patlis-cancel-delete-key"><?php echo esc_html($labels['cancel']); ?></button>
                </form>

                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var btn = document.getElementById('patlis-add-translation-key');
                    var form = document.getElementById('patlis-add-key-form');
                    var cancel = document.getElementById('patlis-cancel-add-key');

                    var btnDel = document.getElementById('patlis-delete-translation-key');
                    var formDel = document.getElementById('patlis-delete-key-form');
                    var cancelDel = document.getElementById('patlis-cancel-delete-key');

                    if (btn && form && cancel) {
                        btn.addEventListener('click', function() {
                            form.style.display = 'block';
                            btn.style.display = 'none';
                            if (btnDel) btnDel.style.display = 'none';
                        });

                        cancel.addEventListener('click', function() {
                            form.style.display = 'none';
                            btn.style.display = '';
                            if (btnDel) btnDel.style.display = '';
                        });
                    }

                    if (btnDel && formDel && cancelDel) {
                        btnDel.addEventListener('click', function() {
                            formDel.style.display = 'block';
                            btnDel.style.display = 'none';
                            if (btn) btn.style.display = 'none';
                        });

                        cancelDel.addEventListener('click', function() {
                            formDel.style.display = 'none';
                            btnDel.style.display = '';
                            if (btn) btn.style.display = '';
                        });
                    }
                });
                </script>
            <?php endif; ?>

            <!-- Search filter -->
            <form method="get" style="margin-bottom:16px;">
                <input type="hidden" name="page" value="patlis-translations">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php echo esc_attr($labels['filter_keys']); ?>" style="width:280px;">
                <button type="submit" class="button"><?php echo esc_html($labels['filter']); ?></button>
                <?php if ($search !== '') : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=patlis-translations')); ?>" class="button"><?php echo esc_html($labels['clear']); ?></a>
                <?php endif; ?>
                <span style="margin-left:12px; color:#666;">
                    <?php echo esc_html(sprintf($total_keys === 1 ? $labels['key_singular'] : $labels['key_plural'], $total_keys)); ?>
                    <?php if ($search !== '') echo ' ' . esc_html(sprintf($labels['matching'], $search)); ?>
                </span>
            </form>

            <form method="post">
                <?php wp_nonce_field('patlis_save_translations', 'patlis_translations_nonce'); ?>
                <input type="hidden" name="_search" value="<?php echo esc_attr($search); ?>">
                <input type="hidden" name="_paged" value="<?php echo esc_attr($paged); ?>">

                <table class="widefat striped" style="max-width:1100px;">
                    <thead>
                        <tr>
                            <th style="width:260px;"><?php echo esc_html($labels['key']); ?></th>
                            <th style="width:120px;"><?php echo esc_html($labels['language']); ?></th>
                            <th><?php echo esc_html($labels['translation']); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($page_keys)) : ?>
                            <tr>
                                <td colspan="3"><?php echo esc_html($labels['no_keys_found']); ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($page_keys as $key) : ?>
                                <?php foreach ($languages as $index => $lang) : ?>
                                    <tr>
                                        <?php if ($index === 0) : ?>
                                            <td rowspan="<?php echo esc_attr(count($languages)); ?>" style="vertical-align:top;">
                                                <code><?php echo esc_html($key); ?></code>
                                            </td>
                                        <?php endif; ?>

                                        <td style="vertical-align:top; white-space:nowrap;">
                                            <strong><?php echo esc_html(strtoupper($lang)); ?></strong>
                                            <?php if ($lang === $default) : ?>
                                                <div style="font-size:12px; opacity:.7;"><?php echo esc_html($labels['default']); ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <textarea
                                                name="patlis_translations[<?php echo esc_attr($key); ?>][<?php echo esc_attr($lang); ?>]"
                                                rows="2"
                                                style="width:100%;"
                                            ><?php echo esc_textarea($translations[$key][$lang] ?? ''); ?></textarea>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div style="display:flex;align-items:center;gap:16px;margin-top:16px;">
                    <button type="submit" class="button button-primary"><?php echo esc_html($labels['save_translations']); ?></button>

                    <?php if ($total_pages > 1) : ?>
                        <span style="color:#666;"><?php echo esc_html(sprintf($labels['page_of'], $paged, $total_pages)); ?></span>
                        <div>
                            <?php for ($p = 1; $p <= $total_pages; $p++) : ?>
                                <?php $page_url = add_query_arg('paged', $p, $base_url); ?>
                                <?php if ($p === $paged) : ?>
                                    <span style="display:inline-block;padding:4px 10px;background:#0073aa;color:#fff;border-radius:3px;margin:0 2px;"><?php echo $p; ?></span>
                                <?php else : ?>
                                    <a href="<?php echo esc_url($page_url); ?>" style="display:inline-block;padding:4px 10px;border:1px solid #ccc;border-radius:3px;margin:0 2px;"><?php echo $p; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
    }
}