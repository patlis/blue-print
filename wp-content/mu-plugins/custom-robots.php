<?php
/**
 * Plugin Name: Custom Robots TXT
 * Description: Custom robots.txt output.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('robots_txt', function ($output, $public) {
    $site_url = home_url();

    $output = "User-agent: *\n";
    $output .= "Disallow: /wp-admin/\n";
    $output .= "Allow: /wp-admin/admin-ajax.php\n\n";
    $output .= "Sitemap: " . $site_url . "/sitemap_index.xml\n";

    return $output;
}, 10, 2);
