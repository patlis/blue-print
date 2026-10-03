<?php
if (!defined('ABSPATH')) exit;

return [
        /* Gallery page */
    'gallery' => [
        'type'            => 'page',
        'label'           => __('Gallery', 'patlis-core'),
        'shortcode_slug'  => 'gallery',
        'availability'    => 'gallery',
        'default_variant' => 'mosaic',
        'variants' => [
            'mosaic' => [
                'label'    => __('Mosaic Gallery', 'patlis-core'),
                'template' => 'gallery-mosaic',
                'preview'  => 'assets/design-previews/gallery/mosaic.webp',
                    'fields'   => [],
            ],
            'classic' => [
                'label'    => __('Classic Gallery', 'patlis-core'),
                'template' => 'gallery-classic',
                'preview'  => 'assets/design-previews/gallery/classic.webp',
                'fields'   => [],
            ],
            'cinematic' => [
                'label'    => __('Cinematic Gallery', 'patlis-core'),
                'template' => 'gallery-cinematic',
                'preview'  => 'assets/design-previews/gallery/cinematic.webp',
                'fields'   => [],
            ],
        ],
    ],
    'menu' => [
        'type'            => 'page',
        'label'           => __('Menu', 'patlis-core'),
        'shortcode_slug'  => 'menu',
        'availability'    => 'menu',
        'default_variant' => 'accordion',
        'variants' => [
            'accordion' => [
                'label'    => __('Accordion Menu', 'patlis-core'),
                'template' => 'menu-accordion',
                'description' => __('Mobile-friendly accordion layout, ideal for menus with many categories.', 'patlis-core'),
                'preview'  => 'assets/design-previews/menu/accordion.webp',
                'fields'   => [],
            ],
            'centered' => [
                'label'    => __('Centered Menu', 'patlis-core'),
                'template' => 'menu-centered',
                'description' => __('Elegant centered layout for smaller or more curated menus.', 'patlis-core'),
                'preview'  => 'assets/design-previews/menu/centered.webp',
                'fields'   => [],
            ],
            'classic' => [
                'label'    => __('Classic Menu', 'patlis-core'),
                'template' => 'menu-classic',
                'description' => __('Traditional menu layout with category images and easy-to-scan dish lists.', 'patlis-core'),
                'preview'  => 'assets/design-previews/menu/classic.webp',
                'fields'   => [],
            ],
            'compact' => [
                'label'    => __('Compact Menu', 'patlis-core'),
                'template' => 'menu-compact',
                'description' => __('Text-only layout with 3 columns on desktop, 2 on tablet, and 1 on mobile.', 'patlis-core'),
                'preview'  => 'assets/design-previews/menu/compact.webp',
                'fields'   => [],
            ],
        ],
    ],
    'reviews' => [
        'type'            => 'page',
        'label'           => __('Reviews', 'patlis-core'),
        'shortcode_slug'  => 'page-reviews',
        'availability'    => 'reviews',
        'default_variant' => 'editorial',
        'variants' => [
            'classic' => [
                'label'    => __('Classic Reviews', 'patlis-core'),
                'template' => 'page-reviews-classic',
                'preview'  => 'assets/design-previews/page-reviews/classic.webp',
                'fields'   => [],
            ],
            'editorial' => [
                'label'    => __('Editorial Reviews', 'patlis-core'),
                'template' => 'page-reviews-editorial',
                'preview'  => 'assets/design-previews/page-reviews/editorial.webp',
                'fields'   => [],
            ],
        ],
    ],


];
