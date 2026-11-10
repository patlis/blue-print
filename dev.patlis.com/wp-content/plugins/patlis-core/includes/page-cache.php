<?php
/**
 * Allow users with edit_posts to purge WP-Optimize page cache only.
 * Does not grant access to WP-Optimize settings, minify or database optimizations.
 */

if (!defined('ABSPATH')) exit;

function patlis_get_page_cache(): ?WPO_Page_Cache {
    if (!function_exists('WP_Optimize') || !class_exists('WPO_Page_Cache')) {
        return null;
    }

    $cache = WP_Optimize()->get_page_cache();

    return $cache instanceof WPO_Page_Cache && $cache->is_enabled() ? $cache : null;
}

add_action('admin_bar_menu', function (WP_Admin_Bar $admin_bar): void {
    if (!current_user_can('edit_posts') || !patlis_get_page_cache()) {
        return;
    }

    $admin_bar->add_node([
        'id'    => 'patlis-purge-page-cache',
        'title' => '<span class="ab-icon dashicons dashicons-update" aria-hidden="true"></span>'
            . '<span class="ab-label">' . esc_html__('Clear cache', 'patlis-core') . '</span>',
        'href'  => wp_nonce_url(
            add_query_arg('action', 'patlis_purge_page_cache', admin_url('admin-post.php')),
            'patlis_purge_page_cache'
        ),
    ]);
}, 100);

add_action('admin_post_patlis_purge_page_cache', function (): void {
    if (!current_user_can('edit_posts')) {
        wp_die(
            esc_html__('You are not allowed to perform this action.', 'patlis-core'),
            '',
            ['response' => 403]
        );
    }

    check_admin_referer('patlis_purge_page_cache');

    $cache = patlis_get_page_cache();

    if (!$cache) {
        wp_die(
            esc_html__('WP-Optimize page caching is not available or is disabled.', 'patlis-core'),
            '',
            ['response' => 503, 'back_link' => true]
        );
    }

    if (!$cache->purge()) {
        wp_die(
            esc_html__('Cache could not be cleared. Please contact your administrator.', 'patlis-core'),
            '',
            ['response' => 500, 'back_link' => true]
        );
    }

    wp_safe_redirect(add_query_arg('patlis_page_cache_cleared', '1', admin_url('index.php')));
    exit;
});

add_action('admin_notices', function (): void {
    if (!current_user_can('edit_posts') || !isset($_GET['patlis_page_cache_cleared']) || $_GET['patlis_page_cache_cleared'] !== '1') {
        return;
    }
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e('Cache cleared.', 'patlis-core'); ?></p>
    </div>
    <?php
});
