<?php
/**
 * Plugin Name: Disable MainWP Child Logs
 * Description: Disables MainWP Child reporting logs and purges residual rows daily.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Try to disable logging/reporting from MainWP Child via documented filters.
 */
add_filter('mainwp_child_reports_log_limit', '__return_zero', 99);
add_filter('mainwp_child_site_stats_report', '__return_false', 99);

/**
 * Hard block writes to MainWP Child log tables when plugin filters are ignored.
 */
add_filter('query', static function (string $query): string {
	if ($query === '') {
		return $query;
	}

	if (strpos($query, '/* patlis_mainwp_purge */') !== false) {
		return $query;
	}

	$has_logs_table = stripos($query, 'mainwp_child_changes_logs') !== false;
	$has_meta_table = stripos($query, 'mainwp_child_changes_meta') !== false;

	if (!$has_logs_table && !$has_meta_table) {
		return $query;
	}

	if (!preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE|TRUNCATE)\b/i', $query)) {
		return $query;
	}

	// Return a harmless read query instead of an empty string.
	return 'SELECT 1';
}, 9999);

/**
 * Cron hook name used for periodic cleanup.
 */
const PATLIS_MAINWP_LOGS_PURGE_HOOK = 'patlis_mainwp_child_purge_logs';

/**
 * Schedule daily cleanup as a fallback in case MainWP still writes rows.
 */
add_action('init', static function (): void {
	if (!wp_next_scheduled(PATLIS_MAINWP_LOGS_PURGE_HOOK)) {
		wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', PATLIS_MAINWP_LOGS_PURGE_HOOK);
	}
});

/**
 * Purge MainWP Child log tables.
 */
add_action(PATLIS_MAINWP_LOGS_PURGE_HOOK, static function (): void {
	global $wpdb;

	$logs_table = $wpdb->prefix . 'mainwp_child_changes_logs';
	$meta_table = $wpdb->prefix . 'mainwp_child_changes_meta';

	$existing_logs_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $logs_table));
	$existing_meta_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $meta_table));

	if ($existing_meta_table === $meta_table) {
		$wpdb->query("DELETE /* patlis_mainwp_purge */ FROM `{$meta_table}`");
	}

	if ($existing_logs_table === $logs_table) {
		$wpdb->query("DELETE /* patlis_mainwp_purge */ FROM `{$logs_table}`");
	}
});

/**
 * Extra safety: purge on admin requests at most once every 10 minutes.
 */
add_action('admin_init', static function (): void {
	if (get_transient('patlis_mainwp_logs_recent_purge') !== false) {
		return;
	}

	do_action(PATLIS_MAINWP_LOGS_PURGE_HOOK);
	set_transient('patlis_mainwp_logs_recent_purge', '1', 10 * MINUTE_IN_SECONDS);
});

