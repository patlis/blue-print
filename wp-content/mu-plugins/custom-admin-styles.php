<?php
/*
Plugin Name: Custom Admin Styles
Description: Add custom CSS to the admin panel.
Author: Ioannis Patlis
*/

function custom_admin_css_get_config(): array {
    return [
        'colors' => [
            'main'       => '#800305',
            'second'     => '#cc4202',
            'text'       => '#fff',
            'hover-text' => '#000',
        ],
        'main' => [
            'toplevel_page_patlis-basic',
        ],
        'secondary' => [
            'toplevel_page_patlis-menu',
            'toplevel_page_patlis-reservations',
            'toplevel_page_patlis-cookies',
            'toplevel_page_patlis-accommodation',
            'toplevel_page_patlis-kiosk-mode',
            'menu-icon-patlis_gallery',
            'menu-icon-events',
            'menu-icon-services',
            'menu-icon-reviews',
            'menu-icon-gallery_images',
            'menu-icon-timeline_item',
            'menu-icon-slide',
        ],
    ];
}

function custom_admin_css_join_selectors(array $selectors): string {
    return implode(",\n    ", $selectors);
}

function custom_admin_css_anchor_selectors(array $classes, array $states = ['']): array {
    $selectors = [];

    foreach ($classes as $class) {
        foreach ($states as $state) {
            $selectors[] = 'a.' . $class . $state;
        }
    }

    return $selectors;
}

function custom_admin_css_icon_selectors(array $classes, array $states): array {
    $selectors = [];

    foreach (custom_admin_css_anchor_selectors($classes, $states) as $selector) {
        $selectors[] = $selector . ' .wp-menu-image';
        $selectors[] = $selector . ' .wp-menu-image::before';
    }

    return $selectors;
}

function custom_admin_css_body_selectors(array $classes): array {
    return array_map(static function (string $class): string {
        return 'body.' . $class;
    }, $classes);
}

function custom_admin_css_enqueue() {
    $config            = custom_admin_css_get_config();
    $main_classes      = $config['main'];
    $secondary_classes = $config['secondary'];
    $all_classes       = array_merge($main_classes, $secondary_classes);
    $active_states     = [':hover', '.current', '.wp-has-current-submenu'];

    echo '<style type="text/css">' . "\n";
    echo '    :root {' . "\n";

    foreach ($config['colors'] as $name => $value) {
        echo '        --' . esc_html($name) . ': ' . esc_html($value) . ';' . "\n";
    }

    echo '    }' . "\n\n";
    echo '    ' . custom_admin_css_join_selectors(custom_admin_css_anchor_selectors($main_classes, array_merge([''], $active_states))) . ' {' . "\n";
    echo '        background-color: var(--main) !important;' . "\n";
    echo '        color: var(--text) !important;' . "\n";
    echo '    }' . "\n\n";
    echo '    ' . custom_admin_css_join_selectors(custom_admin_css_anchor_selectors($secondary_classes)) . ' {' . "\n";
    echo '        background-color: var(--second) !important;' . "\n";
    echo '    }' . "\n\n";
    echo '    ' . custom_admin_css_join_selectors(custom_admin_css_anchor_selectors($secondary_classes, $active_states)) . ' {' . "\n";
    echo '        background-color: var(--second) !important;' . "\n";
    echo '        color: var(--hover-text) !important;' . "\n";
    echo '    }' . "\n\n";
    echo '    ' . custom_admin_css_join_selectors(custom_admin_css_icon_selectors($secondary_classes, $active_states)) . ' {' . "\n";
    echo '        color: var(--hover-text) !important;' . "\n";
    echo '    }' . "\n\n";
    echo '    ' . custom_admin_css_join_selectors(custom_admin_css_body_selectors($all_classes)) . ' {' . "\n";
    echo '        background-color: transparent !important;' . "\n";
    echo '    }' . "\n";
    echo '</style>';
}
add_action( 'admin_head', 'custom_admin_css_enqueue' );

