<?php
if (!defined('ABSPATH')) exit;

/**
 * Return all active Polylang language slugs.
 */
function patlis_get_all_polylang_language_slugs(): array
{
    if (!function_exists('pll_languages_list')) {
        return [];
    }

    $languages = pll_languages_list([
        'fields' => 'slug',
    ]);

    if (!is_array($languages)) {
        return [];
    }

    return array_values(array_filter(array_map('sanitize_key', $languages)));
}

/**
 * Return the site-visible language slugs saved in options.
 * Example saved value: ['de', 'en', 'el']
 */
function patlis_get_site_visible_language_slugs(): array
{
    $saved = get_option('patlis_visible_languages', []);

    if (!is_array($saved)) {
        $saved = [];
    }

    $saved = array_values(array_filter(array_map('sanitize_key', $saved)));

    $all_languages = patlis_get_all_polylang_language_slugs();

    if (empty($all_languages)) {
        return [];
    }

    return array_values(array_intersect($saved, $all_languages));
}

/**
 * Only real admins can manage all languages / settings page.
 */
function patlis_user_can_manage_languages_visibility(): bool
{
    return current_user_can('manage_options');
}

/**
 * Only true admins can see all languages.
 */
function patlis_user_can_see_all_languages(): bool
{
    return current_user_can('manage_options');
}

/**
 * Return the effective visible languages for current user.
 */
function patlis_get_effective_language_slugs_for_current_user(): array
{
    if (patlis_user_can_see_all_languages()) {
        return patlis_get_all_polylang_language_slugs();
    }

    return patlis_get_site_visible_language_slugs();
}

/**
 * Should backend language restrictions apply for current user?
 * Rule:
 * - admin => no restriction
 * - all other roles => restrict
 */
function patlis_should_restrict_backend_languages(): bool
{
    if (!is_admin()) {
        return false;
    }

    if (patlis_user_can_see_all_languages()) {
        return false;
    }

    return true;
}

/**
 * Return frontend languages allowed for this site.
 * Useful later for custom switchers if needed.
 */
function patlis_get_frontend_languages(): array
{
    if (!function_exists('pll_the_languages')) {
        return [];
    }

    $allowed = patlis_get_site_visible_language_slugs();

    $languages = pll_the_languages([
        'raw' => 1,
        'hide_if_empty' => 0,
        'hide_if_no_translation' => 0,
        'echo' => 0,
    ]);

    if (!is_array($languages)) {
        return [];
    }

    $languages = array_filter($languages, function ($lang) use ($allowed) {
        if (empty($lang['slug'])) {
            return false;
        }

        return in_array($lang['slug'], $allowed, true);
    });

    return array_values($languages);
}

/**
 * Restrict Polylang language switcher items inside WP menus on frontend.
 * Applies to the current header menu language switcher.
 */
add_filter('wp_nav_menu_objects', function ($items, $args) {

    if (is_admin() || !is_array($items) || empty($items)) {
        return $items;
    }

    $allowed = patlis_get_site_visible_language_slugs();

    // Αν δεν υπάρχουν επιτρεπόμενες γλώσσες, μην κρύβεις τίποτα (για ασφάλεια)
    if (empty($allowed)) {
        return $items;
    }

    foreach ($items as $key => $item) {
        // Το Polylang προσθέτει την κλάση 'lang-item' σε όλα τα στοιχεία εναλλαγής γλώσσας
        $classes = is_array($item->classes) ? $item->classes : [];
        if (!in_array('lang-item', $classes)) {
            continue;
        }

        $item_lang_slug = '';

        // 1. Προσπάθεια εύρεσης μέσω του property 'lang' που βάζει το Polylang
        if (!empty($item->lang)) {
            $item_lang_slug = $item->lang;
        } 
        // 2. Εναλλακτικά, αναζήτηση στα classes για το pattern 'lang-item-XX'
        else {
            foreach ($classes as $class) {
                if (strpos($class, 'lang-item-') === 0) {
                    $item_lang_slug = substr($class, strlen('lang-item-'));
                    break;
                }
            }
        }

        // Καθαρισμός slug (π.χ. μετατροπή el-gr σε el)
        $item_lang_slug = strtolower(explode('-', $item_lang_slug)[0]);

        // Αφαίρεση αν η γλώσσα ΔΕΝ είναι στην επιτρεπόμενη λίστα
        if (!empty($item_lang_slug) && !in_array($item_lang_slug, $allowed, true)) {
            unset($items[$key]);
        }
    }

    return $items;

}, 20, 2);


/**
 * Register Languages Visibility admin page under Patlis.com
 * Admin only.
 *
 * Priority 99 so it is added AFTER the existing Patlis submenu items.
 * This keeps "Basic settings" as the first submenu and prevents parent menu hijack.
 */
add_action('admin_menu', function () {
    if (!patlis_user_can_manage_languages_visibility()) {
        return;
    }

    add_submenu_page(
        'patlis-basic',
        __('Languages Visibility', 'patlis-core'),
        __('Languages', 'patlis-core'),
        'manage_options',
        'patlis-languages-visibility',
        'patlis_render_languages_visibility_page'
    );
}, 99);

/**
 * Save Languages Visibility settings
 */
add_action('admin_post_patlis_save_languages_visibility', function () {
    if (!patlis_user_can_manage_languages_visibility()) {
        wp_die('Not allowed.');
    }

    check_admin_referer('patlis_save_languages_visibility', 'patlis_languages_visibility_nonce');

    $raw = $_POST['patlis_visible_languages'] ?? [];

    if (!is_array($raw)) {
        $raw = [];
    }

    $raw = array_values(array_filter(array_map('sanitize_key', $raw)));

    $all_languages = patlis_get_all_polylang_language_slugs();
    $clean = array_values(array_intersect($raw, $all_languages));

    update_option('patlis_visible_languages', $clean);

    wp_safe_redirect(admin_url('admin.php?page=patlis-languages-visibility&updated=1'));
    exit;
});

/**
 * Render Languages Visibility admin page
 */
function patlis_render_languages_visibility_page(): void
{
    if (!patlis_user_can_manage_languages_visibility()) {
        wp_die('Not allowed.');
    }

    $all_languages     = patlis_get_all_polylang_language_slugs();
    $visible_languages = patlis_get_site_visible_language_slugs();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Languages Visibility', 'patlis-core'); ?></h1>

        <?php if (!empty($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Languages saved.', 'patlis-core'); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="patlis_save_languages_visibility">
            <?php wp_nonce_field('patlis_save_languages_visibility', 'patlis_languages_visibility_nonce'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <?php foreach ($all_languages as $slug) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html(strtoupper($slug)); ?></th>
                            <td>
                                <label>
                                    <input
                                        type="checkbox"
                                        name="patlis_visible_languages[]"
                                        value="<?php echo esc_attr($slug); ?>"
                                        <?php checked(in_array($slug, $visible_languages, true)); ?>
                                    >
                                    <?php esc_html_e('Visible on site', 'patlis-core'); ?>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button(__('Save Languages', 'patlis-core')); ?>
        </form>
    </div>
    <?php
}

/**
 * Hide Polylang language columns for non-admin users
 * when the language is not enabled in patlis_visible_languages.
 */
add_action('admin_head', function () {
    if (!patlis_should_restrict_backend_languages()) {
        return;
    }

    $allowed = patlis_get_site_visible_language_slugs();

    if (empty($allowed)) {
        return;
    }

    $all = patlis_get_all_polylang_language_slugs();
    $hidden = array_values(array_diff($all, $allowed));

    if (empty($hidden)) {
        return;
    }

    $hidden_json = wp_json_encode($hidden);
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const hidden = <?php echo $hidden_json; ?>;
        if (!Array.isArray(hidden) || !hidden.length) {
            return;
        }

        const tables = document.querySelectorAll('.wp-list-table');

        tables.forEach(function (table) {
            const headRow = table.querySelector('thead tr');
            if (!headRow) {
                return;
            }

            const headers = Array.from(headRow.children);
            const hideIndexes = [];

            headers.forEach(function (th, index) {
                const classText = (th.className || '').toLowerCase();
                const htmlText  = (th.innerHTML || '').toLowerCase();
                const text      = (th.textContent || '').trim().toLowerCase();

                let matched = false;

                hidden.forEach(function (slug) {
                    const s = String(slug).toLowerCase();

                    if (
                        classText.includes('language_' + s) ||
                        classText.includes('pll_' + s) ||
                        classText.includes('lang-' + s) ||
                        classText.includes('lang_' + s) ||
                        htmlText.includes('hreflang="' + s) ||
                        htmlText.includes('/' + s + '/') ||
                        text === s
                    ) {
                        matched = true;
                    }
                });

                if (matched) {
                    hideIndexes.push(index);
                    th.style.display = 'none';
                }
            });

            if (!hideIndexes.length) {
                return;
            }

            table.querySelectorAll('tbody tr, tfoot tr').forEach(function (row) {
                const cells = Array.from(row.children);

                hideIndexes.forEach(function (index) {
                    if (cells[index]) {
                        cells[index].style.display = 'none';
                    }
                });
            });
        });
    });
    </script>
    <?php
});

/**
 * Remove hidden-language hreflang <link rel="alternate"> tags from <head>.
 *
 * Works via output buffering so it catches tags output by any plugin
 * (Polylang, Rank Math, etc.) regardless of which hook they use.
 */
add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }

    $visible = patlis_get_site_visible_language_slugs();
    if (empty($visible)) {
        return;
    }

    ob_start(function (string $output) use ($visible): string {
        // Match <link rel="alternate" ... hreflang="XX" ...> (self-closing or not)
        return preg_replace_callback(
            '/<link\b[^>]*\brel=["\']alternate["\'][^>]*\bhreflang=["\']([^"\']+)["\'][^>]*\/?>/i',
            function (array $m) use ($visible): string {
                $lang   = strtolower($m[1]);
                $short  = explode('-', $lang)[0];

                if (in_array($short, $visible, true) || in_array($lang, $visible, true)) {
                    return $m[0];
                }

                return '';
            },
            $output
        );
    });
}, 0);

add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }

    // Only flush if we actually started a buffer above
    $visible = patlis_get_site_visible_language_slugs();
    if (!empty($visible)) {
        ob_end_flush();
    }
}, PHP_INT_MAX);