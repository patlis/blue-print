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
            $manual_keys = patlis_get_manual_translation_keys();
            if (($k = array_search($delete_key, $manual_keys, true)) !== false) {
                unset($manual_keys[$k]);
                $manual_keys = array_values($manual_keys);
                update_option(patlis_translation_option_name(), $manual_keys, false);
                // Optionally, remove translations for this key
                $translations = function_exists('patlis_get_translations') ? patlis_get_translations() : [];
                if (isset($translations[$delete_key])) {
                    unset($translations[$delete_key]);
                    update_option(patlis_translations_option_name(), $translations, false);
                }
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

            if (patlis_is_valid_translation_key($new_key)) {
                $manual_keys = patlis_get_manual_translation_keys();

                if (!in_array($new_key, $manual_keys, true)) {
                    $manual_keys[] = $new_key;
                    $manual_keys = array_map('patlis_normalize_translation_key', $manual_keys);
                    $manual_keys = array_filter($manual_keys, 'patlis_is_valid_translation_key');
                    $manual_keys = array_values(array_unique($manual_keys));
                    sort($manual_keys);

                    update_option(patlis_translation_option_name(), $manual_keys, false);
                }
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

        if (
            isset($_POST['patlis_translations_nonce']) &&
            wp_verify_nonce($_POST['patlis_translations_nonce'], 'patlis_save_translations')
        ) {
            $posted = $_POST['patlis_translations'] ?? [];
            $clean  = $translations;

            if (!is_array($clean)) {
                $clean = [];
            }

            foreach ($keys as $key) {
                // Ignore keys that are not in manual keys anymore (e.g. deleted)
                if (!in_array($key, patlis_get_manual_translation_keys(), true)) {
                    continue;
                }
                if (!isset($clean[$key]) || !is_array($clean[$key])) {
                    $clean[$key] = [];
                }

                foreach ($languages as $lang) {
                    $value = $posted[$key][$lang] ?? '';
                    $value = stripslashes((string) $value);
                    $value = wp_kses_post($value);
                    $value = trim($value);

                    if ($value === '') {
                        unset($clean[$key][$lang]);
                    } else {
                        $clean[$key][$lang] = $value;
                    }
                }

                if (empty($clean[$key])) {
                    unset($clean[$key]);
                }
            }

            update_option(patlis_translations_option_name(), $clean, false);
            $translations = $clean;

            echo '<div class="notice notice-success is-dismissible"><p>Translations saved.</p></div>';
        }

        ?>
        <div class="wrap">
            <h1 style="display:flex;align-items:center;justify-content:space-between;">
                <span>Patlis Translations</span>
                <?php if (current_user_can('manage_options')) : ?>
                    <button id="patlis-add-translation-key" type="button" class="button button-secondary">Add New</button>
                    <button id="patlis-delete-translation-key" type="button" class="button button-danger" style="margin-left:8px;">Delete Key</button>
                <?php endif; ?>
            </h1>

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

            <form method="post">
                <?php wp_nonce_field('patlis_save_translations', 'patlis_translations_nonce'); ?>

                <table class="widefat striped" style="max-width:1100px;">
                    <thead>
                        <tr>
                            <th style="width:260px;">Key</th>
                            <th style="width:120px;">Language</th>
                            <th>Translation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($keys)) : ?>
                            <tr>
                                <td colspan="3">No keys found.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($keys as $key) : ?>
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

                <p style="margin-top:16px;">
                    <button type="submit" class="button button-primary">Save translations</button>
                </p>
            </form>
        </div>
        <?php
    }
}