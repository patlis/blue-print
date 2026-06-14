<?php
/**
 * Plugin Name: Disable WP Core Sitemaps
 * Description: Disables the built-in WordPress XML sitemaps (wp-sitemap.xml).
 */
add_filter('wp_sitemaps_enabled', '__return_false');
