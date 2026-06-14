<?php
/**
 * Plugin Name: Patlis Version & Feature Flags
 * Description: Feature flags based on PATLIS_VERSION constant (comma-separated).
 */

if (!defined('PATLIS_VERSION')) {
    define('PATLIS_VERSION', '');
}

function patlis_version_has_gastro(): bool {
    return preg_match('~(^|,\s*)gastro(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_general(): bool {
    return preg_match('~(^|,\s*)general(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_hotel(): bool {
    return preg_match('~(^|,\s*)hotel(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}


function patlis_version_has_shop(): bool {
    return preg_match('~(^|,\s*)shop(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_amenities(): bool {
    return preg_match('~(^|,\s*)amenities(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_dining(): bool {
    return preg_match('~(^|,\s*)dining(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_locations(): bool {
    return preg_match('~(^|,\s*)locations(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_has_kiosk(): bool {
    return preg_match('~(^|,\s*)kiosk(\s*,|$)~i', (string) PATLIS_VERSION) === 1;
}

function patlis_version_get_parts(): array {
    return array_values(array_filter(array_map(static function (string $part): string {
        return strtolower(trim($part));
    }, explode(',', (string) PATLIS_VERSION))));
}

function patlis_version_get_managed_plugins(): array {
    return [
        'patlis-core/patlis-core.php'                   => [],
        'patlis-cookies/patlis-cookies.php'             => [],
        'patlis-menu/patlis-menu.php'                   => ['gastro', 'dining'],
        'patlis-reservations/patlis-reservations.php'   => ['gastro', 'dining'],
        'patlis-accommodation/patlis-accommodation.php' => ['hotel'],
        'patlis-kiosk-mode/patlis-kiosk-mode.php'       => ['kiosk'],
    ];
}

function patlis_version_is_plugin_allowed(string $plugin_file): bool {
    $managed_plugins = patlis_version_get_managed_plugins();

    if (!array_key_exists($plugin_file, $managed_plugins)) {
        return true;
    }

    $allowed_versions = $managed_plugins[$plugin_file];

    if ($allowed_versions === []) {
        return true;
    }

    return count(array_intersect(patlis_version_get_parts(), $allowed_versions)) > 0;
}

add_filter('all_plugins', function (array $plugins): array {
    if (!is_admin()) {
        return $plugins;
    }

    foreach (array_keys(patlis_version_get_managed_plugins()) as $plugin_file) {
        if (!patlis_version_is_plugin_allowed($plugin_file)) {
            unset($plugins[$plugin_file]);
        }
    }

    return $plugins;
});

function patlis_version_get_template_bundle_taxonomy(): string {
    if (taxonomy_exists('template_bundle')) {
        return 'template_bundle';
    }

    foreach (get_object_taxonomies('bricks_template', 'objects') as $taxonomy) {
        $label = strtolower((string) ($taxonomy->label ?? ''));
        $name  = strtolower((string) ($taxonomy->name ?? ''));

        if ($name === 'template_bundle' || $label === 'template bundle') {
            return (string) $taxonomy->name;
        }
    }

    return 'template_bundle';
}

function patlis_version_get_page_template_taxonomy(): string {
    if (taxonomy_exists('bricks-template')) {
        return 'bricks-template';
    }

    if (taxonomy_exists('template')) {
        return 'template';
    }

    if (taxonomy_exists('templates')) {
        return 'templates';
    }

    foreach (get_object_taxonomies('page', 'objects') as $taxonomy) {
        $label = strtolower((string) ($taxonomy->label ?? ''));
        $name  = strtolower((string) ($taxonomy->name ?? ''));

        if (in_array($name, ['bricks-template', 'template', 'templates'], true) || in_array($label, ['template', 'templates'], true)) {
            return (string) $taxonomy->name;
        }
    }

    return 'template';
}

function patlis_version_get_page_template_term_map(): array {
    return [
        'all-versions' => [
            'about',
            'coming-soon',
            'contact',
            'home',
            'image-gallery',
            'reviews',
            'services',
            'text-pages',
            'slider-section',
        ],
        'gastro' => [
            'menu',
            'menu-carousel-section',
            'reservation',
        ],
        'dining' => [
            'menu',
            'menu-carousel-section',
            'reservation',
        ],
        'hotel' => [
            'room-booking',
            'rooms',
            'top-rooms-section',
            'offers-packages',
            'offers-packages-section',
            'experience',
            'experience-section',
        ],
        'kiosk' => [
            'kiosk',
        ],
    ];
}

function patlis_version_get_allowed_page_template_terms(): array {
    $term_map      = patlis_version_get_page_template_term_map();
    $allowed_terms = $term_map['all-versions'] ?? [];

    foreach (patlis_version_get_parts() as $version) {
        if (!isset($term_map[$version])) {
            continue;
        }

        $allowed_terms = array_merge($allowed_terms, $term_map[$version]);
    }

    return array_values(array_unique($allowed_terms));
}

function patlis_version_build_optional_taxonomy_filter(string $taxonomy, array $allowed_terms): array {
    return [
        'relation' => 'OR',
        [
            'taxonomy' => $taxonomy,
            'field'    => 'slug',
            'terms'    => $allowed_terms,
            'operator' => 'IN',
        ],
        [
            'taxonomy' => $taxonomy,
            'operator' => 'NOT EXISTS',
        ],
    ];
}

function patlis_version_apply_tax_query(WP_Query $query, array $tax_query): void {
    $existing_tax_query = (array) $query->get('tax_query');

    if ($existing_tax_query !== []) {
        $query->set('tax_query', [
            'relation' => 'AND',
            $existing_tax_query,
            $tax_query,
        ]);
        return;
    }

    $query->set('tax_query', $tax_query);
}

add_action('pre_get_posts', function (WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $post_type = $query->get('post_type');

    if ($post_type !== 'bricks_template') {
        return;
    }

    $version_parts = patlis_version_get_parts();

    if ($version_parts === []) {
        return;
    }

    $taxonomy = patlis_version_get_template_bundle_taxonomy();

    if (!taxonomy_exists($taxonomy)) {
        return;
    }

    patlis_version_apply_tax_query($query, patlis_version_build_optional_taxonomy_filter($taxonomy, $version_parts));
});

add_action('pre_get_posts', function (WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $post_type = $query->get('post_type');

    if ($post_type === '') {
        $post_type = 'page';
    }

    if ($post_type !== 'page') {
        return;
    }

    $allowed_terms = patlis_version_get_allowed_page_template_terms();

    if ($allowed_terms === []) {
        return;
    }

    $taxonomy = patlis_version_get_page_template_taxonomy();

    if (!taxonomy_exists($taxonomy)) {
        return;
    }

    patlis_version_apply_tax_query($query, patlis_version_build_optional_taxonomy_filter($taxonomy, $allowed_terms));
});

function allowed_section(string $slug): int {
    $slug = strtolower(trim($slug));
    
    if (!$slug) {
        return 1;
    }

    $term_map = patlis_version_get_page_template_term_map();
    $version_parts = patlis_version_get_parts();

    if (empty($version_parts)) {
        return 1;
    }

    if (in_array($slug, $term_map['all-versions'] ?? [], true)) {
        return 1;
    }

    foreach ($version_parts as $version) {
        if (in_array($slug, $term_map[$version] ?? [], true)) {
            return 1;
        }
    }

    return 0;
}
