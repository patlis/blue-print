<?php
if (!defined('ABSPATH')) exit;

return [

    'about' => [
        'type'            => 'page',
        'label'           => __('About', 'patlis-core'),
        'shortcode_slug'  => 'page-about',
        'default_variant' => 'timeline',
        'variants' => [
            'timeline' => [
                'label'    => __('Timeline', 'patlis-core'),
                'template' => 'about-timeline',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-about/timeline.webp',
                'fields'   => [],
            ],
            'editorial' => [
                'label'    => __('Editorial', 'patlis-core'),
                'template' => 'about-editorial',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-about/editorial.webp',
                'fields'   => [],
            ]
        ],
    ],

    'events' => [
        'type'            => 'page',
        'label'           => __('Events', 'patlis-core'),
        'shortcode_slug'  => 'page-events',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'page-events-classic',
                'description' => __('Card-based event overview with images, descriptions and dates, ideal for presenting multiple upcoming and previous events in a familiar visual format.', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-events/classic.webp',
                'fields'   => [],
            ],
            'agenda' => [
                'label'    => __('Agenda', 'patlis-core'),
                'template' => 'page-events-agenda',
                'description' => __('Date-focused list layout with prominent day and month blocks, ideal for presenting many events clearly with strong emphasis on schedule and timing.', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-events/agenda.webp',
                'fields'   => [],
            ],
            'editorial' => [
                'label'    => __('Editorial', 'patlis-core'),
                'template' => 'page-events-editorial',
                'description' => __('Featured-event layout combining one prominent event with a structured grid of additional events, ideal for highlighting an important occasion while keeping the rest easy to explore.', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-events/editorial.webp',
                'fields'   => [],
            ],
        ],
    ],

     'event' => [
        'type'            => 'page',
        'label'           => __('Event (Single page)', 'patlis-core'),
        'shortcode_slug'  => 'event-single',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'event-single-classic',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/event-single/classic.webp',
                'fields'   => [],
            ],
            'split-screen' => [
                'label'    => __('Split Screen', 'patlis-core'),
                'template' => 'event-single-split-screen',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/event-single/split-screen.webp',
                'fields'   => [],
            ],
            'floating' => [
                'label'    => __('Floating', 'patlis-core'),
                'template' => 'event-single-floating',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/event-single/floating.webp',
                'fields'   => [],
            ],
        ],
    ], 

    'gallery' => [
        'type'            => 'page',
        'label'           => __('Gallery', 'patlis-core'),
        'shortcode_slug'  => 'gallery',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic Gallery', 'patlis-core'),
                'template' => 'gallery-classic',
                'description' => __('Clean uniform grid layout with equally sized images, ideal for displaying larger galleries in a structured, predictable and easy-to-browse format.', 'patlis-core'),
                'preview'  => 'assets/design-previews/gallery/classic.webp',
                'fields'   => [],
            ],
            'mosaic' => [
                'label'    => __('Mosaic Gallery', 'patlis-core'),
                'template' => 'gallery-mosaic',
                'description' => __('Dynamic asymmetrical grid with varied image sizes, ideal for creating a richer visual rhythm and making strong photography more prominent.', 'patlis-core'),
                'preview'  => 'assets/design-previews/gallery/mosaic.webp',
                    'fields'   => [],
            ],
            'cinematic' => [
                'label'    => __('Cinematic Gallery', 'patlis-core'),
                'template' => 'gallery-cinematic',
                'description' => __('Image-led layout combining a large featured image with smaller supporting images, ideal for a more immersive and premium gallery presentation.', 'patlis-core'),
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

    'offers' => [
        'type'            => 'page',
        'label'           => __('Offers & Packages', 'patlis-core'),
        'shortcode_slug'  => 'page-offers',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'offers-hub-page-classic',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-offers/classic.webp',
                'fields'   => [],
            ],
            'list' => [
                'label'    => __('List', 'patlis-core'),
                'template' => 'offers-hub-page-list',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-offers/list.webp',
                'fields'   => [],
            ],
            /*
            'editorial' => [
                'label'    => __('Editorial', 'patlis-core'),
                'template' => 'offers-hub-page-editorial',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-offers/editorial.webp',
                'fields'   => [],
            ],
            */
        ],
    ],

    'offer' => [
        'type'            => 'page',
        'label'           => __('Offer (Single page)', 'patlis-core'),
        'shortcode_slug'  => 'offer-single',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'offer-single-classic',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/offer-single/classic.webp',
                'fields'   => [],
            ],
            'split-screen' => [
                'label'    => __('Split Screen', 'patlis-core'),
                'template' => 'offer-single-split-screen',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/offer-single/split-screen.webp',
                'fields'   => [],
            ],
        ],
    ],

    'reviews' => [
        'type'            => 'page',
        'label'           => __('Reviews', 'patlis-core'),
        'shortcode_slug'  => 'page-reviews',
        'default_variant' => 'editorial',
        'variants' => [
            'editorial' => [
                'label'    => __('Editorial', 'patlis-core'),
                'template' => 'page-reviews-editorial',
                'description' => __('Spacious single-column review layout with stronger focus on each testimonial, ideal for a cleaner, more elegant presentation and longer review content.', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-reviews/editorial.webp',
                'fields'   => [],
            ],
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'page-reviews-classic',
                'description' => __('Grid-based review layout displaying multiple testimonials at once, ideal for presenting a larger volume of guest feedback in a compact and easy-to-scan format.', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-reviews/classic.webp',
                'fields'   => [],
            ],
        ],
    ],

    'rooms' => [
        'type'            => 'page',
        'label'           => __('Rooms', 'patlis-core'),
        'shortcode_slug'  => 'page-rooms',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'rooms-hub-page-classic',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-rooms/classic.webp',
                'fields'   => [],
            ],
            'list' => [
                'label'    => __('List', 'patlis-core'),
                'template' => 'rooms-hub-page-list',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-rooms/list.webp',
                'fields'   => [],
            ],
            'editorial' => [
                'label'    => __('Editorial', 'patlis-core'),
                'template' => 'rooms-hub-page-editorial',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-rooms/editorial.webp',
                'fields'   => [],
            ],
        ],
    ],

    'room' => [
        'type'            => 'page',
        'label'           => __('Room (Single page)', 'patlis-core'),
        'shortcode_slug'  => 'room-single',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'room-single-classic',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/room-single/classic.webp',
                'fields'   => [],
            ],
            'slider' => [
                'label'    => __('Slider', 'patlis-core'),
                'template' => 'room-single-slider',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/room-single/slider.webp',
                'fields'   => [],
            ],
        ],
    ],
    
    'services' => [
        'type'            => 'page',
        'label'           => __('Services', 'patlis-core'),
        'shortcode_slug'  => 'page-services',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'services-hub-page-classic',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-services/classic.webp',
                'fields'   => [],
            ],
            'list' => [
                'label'    => __('List', 'patlis-core'),
                'template' => 'services-hub-page-list',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-services/list.webp',
                'fields'   => [],
            ],
            'editorial' => [
                'label'    => __('Editorial', 'patlis-core'),
                'template' => 'services-hub-page-editorial',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/page-services/editorial.webp',
                'fields'   => [],
            ],
        ],
    ],

    'service' => [
        'type'            => 'page',
        'label'           => __('Service (Single page)', 'patlis-core'),
        'shortcode_slug'  => 'service-single',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'service-single-classic',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/service-single/classic.webp',
                'fields'   => [],
            ],
            'floating' => [
                'label'    => __('Floating', 'patlis-core'),
                'template' => 'service-single-floating',
                'description' => __('', 'patlis-core'),
                'preview'  => 'assets/design-previews/service-single/floating.webp',
                'fields'   => [],
            ],
        ],
    ],

];
