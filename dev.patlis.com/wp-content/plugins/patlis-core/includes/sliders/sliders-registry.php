<?php

if (!defined('ABSPATH')) exit;

return [
    'default_layout' => 'slider-floating-box',
    'layouts' => [
        'slider-floating-box' => [
            'label' => __('Floating', 'patlis-core'),
            'template' => 'slider-floating-box',
            'preview' => 'assets/design-previews/slider/floating-box.webp',
            'description' => __('Full-background layout with a floating content box. Choose left, center or right placement and a light or dark content style.', 'patlis-core'),
            'fields' => [
                'colors' => [
                    'label' => __('Background Color', 'patlis-core'),
                    'type' => 'dropdown',
                    'option_key' => 'background_color',
                    'default' => 'white',
                    'options' => [
                        'white' => __('Text White / Background Black', 'patlis-core'),
                        'black' => __('Text Black / Background White', 'patlis-core'),
                    ],
                ],
                'position' => [
                    'label' => __('Content Position', 'patlis-core'),
                    'type' => 'dropdown',
                    'option_key' => 'direction',
                    'default' => 'left',
                    'options' => [
                        'left' => __('Left', 'patlis-core'),
                        'center' => __('Center', 'patlis-core'),
                        'right' => __('Right', 'patlis-core'),
                    ],
                ],
            ],
        ],
        'slider-split-screen' => [
            'label' => __('Split Screen', 'patlis-core'),
            'template' => 'slider-split-screen',
            'preview' => 'assets/design-previews/slider/split-screen.webp',
            'description' => __('Clean two-column layout with image and content side by side. The image can appear on the left or right.', 'patlis-core'),
            'fields' => [
                'position' => [
                    'label' => __('Content Position', 'patlis-core'),
                    'type' => 'dropdown',
                    'option_key' => 'direction',
                    'default' => 'left',
                    'options' => [
                        'left' => __('Left', 'patlis-core'),
                        'right' => __('Right', 'patlis-core'),
                    ],
                ],
            ],
        ],
        'slider-overlay' => [
            'label' => __('Overlay', 'patlis-core'),
            'template' => 'slider-overlay',
            'preview' => 'assets/design-previews/slider/overlay.webp',
            'description' => __('Full-background layout with content displayed directly over the image. Choose left, center or right placement and adjust the overlay strength.', 'patlis-core'),
            'fields' => [
                'position' => [
                    'label' => __('Content Position', 'patlis-core'),
                    'type' => 'dropdown',
                    'option_key' => 'direction',
                    'default' => 'left',
                    'options' => [
                        'left' => __('Left', 'patlis-core'),
                        'center' => __('Center', 'patlis-core'),
                        'right' => __('Right', 'patlis-core'),
                    ],
                ],
                'overlay_opacity' => [
                    'label' => __('Overlay Opacity', 'patlis-core'),
                    'type' => 'range',
                    'option_key' => 'opacity',
                    'default' => '0.5',
                    'min' => '0.0',
                    'max' => '1.0',
                    'step' => '0.05',
                ],
            ],
        ],
        'slider-text-only' => [
            'label' => __('Text Only', 'patlis-core'),
            'template' => 'slider-text-only',
            'preview' => 'assets/design-previews/slider/text-only.webp',
            'description' => __('Clean text-focused slider without an image. Choose left, center or right placement with a theme-based background style.', 'patlis-core'),
            'fields' => [
                'position' => [
                    'label' => __('Content Position', 'patlis-core'),
                    'type' => 'dropdown',
                    'option_key' => 'direction',
                    'default' => 'left',
                    'options' => [
                        'left' => __('Left', 'patlis-core'),
                        'center' => __('Center', 'patlis-core'),
                        'right' => __('Right', 'patlis-core'),
                    ],
                ],
                'color-scheme' => [
                    'label' => __('Color Scheme', 'patlis-core'),
                    'type' => 'dropdown',
                    'option_key' => 'color_scheme',
                    'default' => 'light',
                    'options' => [
                        'head' => __('Header BG', 'patlis-core'),
                        'body' => __('Page BG', 'patlis-core'),
                        'body-zebra' => __('Alternate page BG', 'patlis-core'),
                        'footer' => __('Footer BG', 'patlis-core'),
                        'gradient' => __('Gradient Header to Page BG', 'patlis-core'),
                    ],
                ],
            ],
        ],
    ],
];