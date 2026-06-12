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
            wp_die('Not allowed.');
        }

        if (!function_exists('pll_languages_list')) {
            echo '<div class="wrap"><h1>Translations</h1><div class="notice notice-error"><p>Polylang is required.</p></div></div>';
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
                <span><?php esc_html_e('Translations', 'patlis-core'); ?></span>
                <?php if (current_user_can('manage_options')) : ?>
                    <span>
                        <button id="patlis-add-translation-key" type="button" class="button button-secondary">Add New</button>
                        <button id="patlis-delete-translation-key" type="button" class="button button-danger" style="margin-left:8px;">Delete Key</button>
                    </span>
                <?php endif; ?>
            </h1>

            <?php if (isset($_GET['patlis_saved'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Translations saved.</p></div>
            <?php endif; ?>

            <?php if (current_user_can('manage_options')) : ?>
                <form method="post" id="patlis-add-key-form" style="display:none; margin-bottom:18px; max-width:600px;">
                    <?php wp_nonce_field('patlis_add_translation_key', 'patlis_add_translation_key_nonce'); ?>
                    <label for="patlis_new_key"><strong>New Key:</strong></label>
                    <input type="text" id="patlis_new_key" name="patlis_new_key" style="width:300px;" required />
                    <button type="submit" class="button button-primary">Add</button>
                    <button type="button" class="button" id="patlis-cancel-add-key">Cancel</button>
                </form>

                <form method="post" id="patlis-delete-key-form" style="display:none; margin-bottom:18px; max-width:600px;">
                    <?php wp_nonce_field('patlis_delete_translation_key', 'patlis_delete_translation_key_nonce'); ?>
                    <label for="patlis_delete_key"><strong>Delete Key:</strong></label>
                    <input type="text" id="patlis_delete_key" name="patlis_delete_key" style="width:300px;" required />
                    <button type="submit" class="button button-danger">Delete</button>
                    <button type="button" class="button" id="patlis-cancel-delete-key">Cancel</button>
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
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Filter keys..." style="width:280px;">
                <button type="submit" class="button">Filter</button>
                <?php if ($search !== '') : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=patlis-translations')); ?>" class="button">Clear</a>
                <?php endif; ?>
                <span style="margin-left:12px; color:#666;">
                    <?php echo esc_html($total_keys); ?> key<?php echo $total_keys !== 1 ? 's' : ''; ?>
                    <?php if ($search !== '') echo ' matching <em>' . esc_html($search) . '</em>'; ?>
                </span>
            </form>

            <form method="post">
                <?php wp_nonce_field('patlis_save_translations', 'patlis_translations_nonce'); ?>
                <input type="hidden" name="_search" value="<?php echo esc_attr($search); ?>">
                <input type="hidden" name="_paged" value="<?php echo esc_attr($paged); ?>">

                <table class="widefat striped" style="max-width:1100px;">
                    <thead>
                        <tr>
                            <th style="width:260px;">Key</th>
                            <th style="width:120px;">Language</th>
                            <th>Translation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($page_keys)) : ?>
                            <tr>
                                <td colspan="3">No keys found.</td>
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
                                                <div style="font-size:12px; opacity:.7;">Default</div>
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
                    <button type="submit" class="button button-primary">Save translations</button>

                    <?php if ($total_pages > 1) : ?>
                        <span style="color:#666;">Page <?php echo $paged; ?> of <?php echo $total_pages; ?></span>
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