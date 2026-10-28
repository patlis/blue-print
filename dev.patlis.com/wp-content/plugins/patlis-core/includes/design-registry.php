<?php
if (!defined('ABSPATH')) exit;

return [

    'header' => [
        'type'     => 'section',
        'label'    => __('Header', 'patlis-core'),
        'shortcode_slug' => 'header',
        'default_variant' => 'header-classic',
        'fields'   => [],
        'variants' => [
            'header-classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'header-classic',
                'description' => __('Traditional horizontal header with logo on the left and navigation on the right. Clear, compact and suitable for most business types.', 'patlis-core'),
                'preview'  => 'assets/design-previews/header/classic.webp',
                'fields'   => [],
            ],
            'header-editorial' => [
                'label'    => __('Editorial', 'patlis-core'),
                'template' => 'header-editorial',
                'description' => __('Centered logo with navigation arranged below for a more elegant, boutique-style presentation.', 'patlis-core'),
                'preview'  => 'assets/design-previews/header/editorial.webp',
                'fields'   => [],
            ],
            'minimal' => [
                'label'    => __('Minimal', 'patlis-core'),
                'template' => 'header-minimal',
                'description' => __('Reduced header with centered branding and a hamburger menu, keeping the interface clean and unobtrusive.', 'patlis-core'),
                'preview'  => 'assets/design-previews/header/minimal.webp',
                'fields'   => [],
            ],
        ],
    ],

    'footer' => [
        'type'     => 'section',
        'label'    => __('Footer', 'patlis-core'),
        'shortcode_slug' => 'footer',
        'default_variant' => 'footer-classic',
        'fields'   => [],
        'variants' => [
            'footer-classic' => [
                'label'    => __('Footer Classic', 'patlis-core'),
                'template' => 'footer-classic',
                'description' => __('Traditional footer layout presenting contact information, opening hours, social links and legal details in a clear and structured arrangement.', 'patlis-core'),
                'preview'  => 'assets/design-previews/footer/footer-classic.webp',
                'fields'   => [
                    'dividerimage' => [
                        'label'      => __('Divider Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'dividerimage',
                        'description' => __('Optional image displayed above the footer. Transparent PNG or WebP recommended', 'patlis-core'),
                    ],
                    'monochrome' => [
                        'label'      => __('Match footer color', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'monochrome',
                        'default'    => false,
                        'description' => __('Recolors the divider image using the current footer color while preserving transparency.', 'patlis-core'),

                    ],
                ],
            ],
            'footer-cinematic' => [
                'label'    => __('Footer Cinematic', 'patlis-core'),
                'template' => 'footer-cinematic',
                'description' => __('Full-width footer with an atmospheric background image, combining essential business information with a visually engaging presentation.', 'patlis-core'),
                'preview'  => 'assets/design-previews/footer/footer-cinematic.webp',
                'fields'   => [
                    'image' => [
                        'label'      => __('Background Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                    'opacity' => [
                        'label'      => __('Background Opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'opacity',
                        'default'    => 0.5,
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,

                    ],
                ],
            ],
            'footer-floating' => [
                'label'    => __('Footer Floating', 'patlis-core'),
                'template' => 'footer-floating',
                'description' => __('Elegant floating footer panel with subtle depth and separation from the page background, ideal for a refined and modern presentation of essential business information.', 'patlis-core'),
                'preview'  => 'assets/design-previews/footer/footer-floating.webp',
                'fields'   => [
                ],
            ],
        ],
    ],

    'welcome_section' => [
        'type'            => 'section',
        'label'           => __('Welcome Section', 'patlis-core'),
        'shortcode_slug'  => 'welcome-section',
        'description'     => __('This section contains the main page heading (H1), which is important for SEO. We recommend keeping it enabled.<br>The image is the Home page featured image<br><span style="color: #ff0000;">Video is available only in the classic Template.</span>', 'patlis-core'),
        'default_variant' => 'classic',
        'fields'          => [
            'cta' => [
                'label'      => __('Show Call to Action Buttons', 'patlis-core'),
                'type'       => 'checkbox',
                'option_key' => 'show_cta',
                'default'    => false,
            ],
        ],
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'description'     => __('Clean and balanced introductory layout combining a decorative visual element with structured welcome text, ideal for a timeless and elegant presentation.
                This template uses the Home page featured image OR a <b>custom video</b>.', 'patlis-core'),
                'template' => 'welcome-classic',
                'preview'  => 'assets/design-previews/welcome-section/classic.webp',
                'fields'   => [                  
                     'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                        'default'    => false,
                    ],                     
                ],
            ],
            'cinematic' => [
                'label'    => __('Cinematic', 'patlis-core'),
                'description'     => __('Full-width image background with centered content overlay, ideal for creating a strong first impression and an immersive atmosphere.
                This template uses the Home page featured image.', 'patlis-core'),
                'template' => 'welcome-section-cinematic',
                'preview'  => 'assets/design-previews/welcome-section/cinematic.webp',
                'fields'   => [
                    'image-opacity' => [
                        'label'      => __('Overlay Opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'image_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                ],
            ],
            'framed' => [
                'label'    => __('Framed', 'patlis-core'),
                'description'     => __('Editorial layout with framed imagery and structured text, ideal for combining a personal visual element with a refined presentation
                This template uses the Home page featured image.', 'patlis-core'),
                'template' => 'welcome-framed',
                'preview'  => 'assets/design-previews/welcome-section/framed.webp',
                'fields'   => [
                    'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                        'default'    => false,
                    ],
                ],
            ],
            'book' => [
                'label'    => __('Book', 'patlis-core'),
                'description'     => __('Distinctive editorial layout inspired by an open-book composition, ideal for storytelling and presenting more detailed introductory content.', 'patlis-core'),
                'template' => 'welcome-book',
                'preview'  => 'assets/design-previews/welcome-section/book.webp',
                'fields'   => [
                    'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                        'default'    => false,
                    ],                    
                ],
            ],
            'floating' => [
                'label'    => __('Floating', 'patlis-core'),
                'description'     => __('Large background image with a floating content panel, ideal for combining strong imagery with clearly separated welcome text and calls to action.
                This template uses the Home page featured image.', 'patlis-core'),
                'template' => 'welcome-floating',
                'preview'  => 'assets/design-previews/welcome-section/floating.webp',
                'fields'   => [],
            ],
            'split-screen' => [
                'label'    => __('Split Screen', 'patlis-core'),
                'description'     => __('Balanced two-column layout with content on one side and a large image on the other, ideal for a modern and highly structured introduction.
                This template uses the Home page featured image.', 'patlis-core'),
                'template' => 'welcome-section-split-screen',
                'preview'  => 'assets/design-previews/welcome-section/split-screen.webp',
                'fields'   => [
                    'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                        'default'    => false,
                    ],
                ],
            ],
            'signature-intro' => [
                'label'    => __('Signature Intro', 'patlis-core'),
                'description'     => __('Refined brand-focused layout combining title, decorative signature element and supporting text, ideal for a more distinctive and premium introduction.', 'patlis-core'),
                'template' => 'welcome-section-signature-intro',
                'preview'  => 'assets/design-previews/welcome-section/signature-intro.webp',
                'fields'   => [
                    'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                        'default'    => false,
                    ],
                ],
            ],

        ],
    ],

    'cta' => [
        'type'     => 'section',
        'label'    => __('CTA (Call to Action)', 'patlis-core'),
        'shortcode_slug' => 'cta-section',
        'description' => __('You can customize Text and images in every page.', 'patlis-core'),
        'default_variant' => 'cinematic',
        'fields' => [
            'header' => [
                'label'      => __('Header Text', 'patlis-core'),
                'type'       => 'text',
                'option_key' => 'header',
            ],
            'subheader' => [
                'label'      => __('Subheader Text', 'patlis-core'),
                'type'       => 'textarea',
                'option_key' => 'subheader',
            ],
        ],
        'variants' => [
            'cinematic' => [
                'label'    => __('Cinematic', 'patlis-core'),
                'template' => 'cta-cinematic',
                'description' => __('Cinematic layout with a large background image and overlaid content, ideal for creating a visually striking call-to-action section.', 'patlis-core'),
                'preview'  => 'assets/design-previews/cta/cinematic.webp',
                'fields'   => [
                    'image' => [
                        'label'      => __('Background Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                    'opacity' => [
                        'label'      => __('Background Opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'opacity',
                        'step'       => 0.05,
                        'default'    => 0.5,
                        'min'        => 0,
                        'max'        => 1,
                    ],
                ],
            ],
            'framed' => [
                'label'    => __('Framed', 'patlis-core'),
                'template' => 'cta-section-framed',
                'description' => __('Clean bordered layout with centered text and buttons, ideal for a refined call to action that stands out without relying on large imagery.', 'patlis-core'),
                'preview'  => 'assets/design-previews/cta/framed.webp',
                'fields'   => [],
            ],
            'floating' => [
                'label'    => __('Floating', 'patlis-core'),
                'template' => 'cta-section-floating',
                'description' => __('Large background image with a floating content panel, ideal for combining strong visual impact with clearly separated call-to-action content.', 'patlis-core'),
                'preview'  => 'assets/design-previews/cta/floating.webp',
                'fields'   => [
                                'image' => [
                                'label'      => __('Background Image', 'patlis-core'),
                                'type'       => 'image',
                                'option_key' => 'image',
                                ],
                ],
            ],
            'split-screen' => [
                'label'    => __('Split Screen', 'patlis-core'),
                'template' => 'cta-section-split-screen',
                'description' => __('Two-column layout pairing call-to-action text with a large supporting image, ideal for a modern and structured presentation with clear visual balance.', 'patlis-core'),
                'preview'  => 'assets/design-previews/cta/split-screen.webp',
                'fields'   => [
                    'image' => [
                        'label'      => __('Background Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                ],
            ],
        ],
    ],

    'top_events' => [
        'type'            => 'section',
        'label'           => __('Events', 'patlis-core'),
        'shortcode_slug'  => 'top-events',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'top-events-classic',
                'description' => __('Card-based carousel displaying multiple upcoming events with image, title, description and date, ideal for a clear and familiar overview.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-events/classic.webp',
                'fields'   => [],
            ],
            'cinematic' => [
                'label'    => __('Cinematic', 'patlis-core'),
                'template' => 'top-events-cinematic',
                'description' => __('Immersive background-image layout with event cards layered on top, ideal for creating a strong visual impact and highlighting special occasions.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-events/cinematic.webp',
                'fields'   => [
                    'bg_image' => [
                        'label'      => __('Background Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'bg_image_id',
                    ],
                    'overlay_opacity' => [
                        'label'      => __('Overlay opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'overlay_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                ],
            ],
            'split-screen' => [
                'label'    => __('Split Screen', 'patlis-core'),
                'template' => 'top-events-split-screen',
                'description' => __('Two-column layout combining introductory content with a structured list of selected events, ideal for balancing storytelling with detailed event information.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-events/split-screen.webp',
                'fields'   => [
                    'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                        'default'    => false,
                    ],
                    'description' => [
                        'label'      => __('Description (optional)', 'patlis-core'),
                        'type'       => 'textarea',
                        'option_key' => 'description',
                    ],
                    'image' => [
                        'label'      => __('Background Image (optional)', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image_id',
                    ],
                    'overlay_opacity' => [
                        'label'      => __('Overlay opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'overlay_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                ],
            ],
            'agenda' => [
                'label'    => __('Agenda', 'patlis-core'),
                'template' => 'top-events-agenda',
                'description' => __('Date-focused list layout with prominent day and month blocks, ideal for presenting multiple events clearly even when strong imagery is not available.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-events/agenda.webp',
                'fields'   => [
                    'description' => [
                        'label'      => __('Description (optional)', 'patlis-core'),
                        'type'       => 'textarea',
                        'option_key' => 'description',
                    ],
                ],
            ],
        ],
    ],
       
    'top_experiences' => [
        'type'     => 'section',
        'label'    => __('Experiences', 'patlis-core'),
        'shortcode_slug' => 'top-experiences',
        'availability'    => 'experience-section',
        'description' => __('Only experiences with images will be displayed in this section.', 'patlis-core'),
        'default_variant' => 'editorial',
        'fields'   => [],
        'variants' => [
            'minimal' => [
                'label'    => __('Minimal', 'patlis-core'),
                'template' => 'top-experiences-minimal',
                'preview'  => 'assets/design-previews/top-experiences/minimal.webp',
                'description' => __('Clean visual layout with circular images and concise titles, ideal for highlighting selected experiences with a light, elegant feel.', 'patlis-core'),
                'fields'   => [],
            ],
            'editorial' => [
                'label'    => __('Editorial', 'patlis-core'),
                'template' => 'top-experiences-editorial',
                'preview'  => 'assets/design-previews/top-experiences/editorial.webp',
                'description' => __('Card-based layout with image, title, description and link, ideal for presenting experiences with more context while keeping them easy to scan.', 'patlis-core'),
                'fields'   => [],
            ],
            'split-screen' => [
                'label'    => __('Split Screen', 'patlis-core'),
                'template' => 'top-experiences-split-screen',
                'preview'  => 'assets/design-previews/top-experiences/split-screen.webp',
                'description' => __('Balanced two-column layout combining an introduction area with a structured list of experiences, ideal for storytelling and a more editorial presentation.', 'patlis-core'),
                'fields'   => [
                    'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                        'default'    => false,
                    ],
                    'image' => [
                        'label'      => __('Image (optional)', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                    'overlay_opacity' => [
                        'label'      => __('Overlay opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'overlay_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                    'description' => [
                        'label'      => __('Description (optional)', 'patlis-core'),
                        'type'       => 'textarea',
                        'option_key' => 'description',
                    ],
                ],
            ],
            'cinematic' => [
                'label'    => __('Cinematic', 'patlis-core'),
                'template' => 'top-experiences-cinematic',
                'preview'  => 'assets/design-previews/top-experiences/cinematic.webp',
                'description' => __('Immersive layout with a large background image and prominent experience cards, ideal for creating a strong visual impact and emotional atmosphere.', 'patlis-core'),
                'fields'   => [
                    'image' => [
                        'label'      => __('Background Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                    'opacity' => [
                        'label'      => __('Background Opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,   
                        'default'    => 0.5,
                    ],
                ],
            ],
        ],
    ],    

    'top_gallery' => [
        'type'     => 'section',
        'label'    => __('Gallery', 'patlis-core'),
        'shortcode_slug' => 'top-gallery',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'top-gallery-classic',
                'description' => __('Compact carousel layout displaying multiple gallery images in a clean horizontal presentation, ideal for adding visual variety without taking up too much space.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-gallery/classic.webp',
                'fields'   => [],
            ],
            'cinematic' => [
                'label'    => __('Cinematic', 'patlis-core'),
                'template' => 'top-gallery-cinematic',
                'preview'  => 'assets/design-previews/top-gallery/cinematic.webp',
                'description' => __('Immersive gallery layout with a large background image and floating image carousel, ideal for creating a strong atmospheric section with high visual impact.', 'patlis-core'),
                'fields'   => [
                    'image-opacity' => [
                        'label'      => __('Overlay Opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'image_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                    'image' => [
                        'label'      => __('Background Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                ],
            ],
            'mosaic' => [
                'label'    => __('Mosaic', 'patlis-core'),
                'template' => 'top-gallery-mosaic',
                'preview'  => 'assets/design-previews/top-gallery/mosaic.webp',
                'description' => __('Large asymmetrical image grid with varied image sizes, ideal for image-rich sites that want to make the gallery a prominent visual feature of the page.', 'patlis-core'),
                'fields'   => [],
            ],
        ],
    ],

    'top_menu' => [
        'type'            => 'section',
        'label'           => __('Menu', 'patlis-core'),
        'shortcode_slug'  => 'top-menu',
        'availability'    => 'top-menu',
        'description' => __('Only items with images will be displayed in this section.', 'patlis-core'),
        'default_variant' => 'editorial-cards',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'top-menu-classic',
                'description' => __('Clean visual layout with circular dish images and concise titles, ideal for highlighting selected menu items in a simple and elegant way.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-menu/classic.webp',
                'fields'   => [],
            ],
            'editorial-cards' => [
                'label'    => __('Editorial', 'patlis-core'),
                'template' => 'menu-editorial-cards',
                'description' => __('Detailed card-based layout combining dish image, title, description and price, with Carousel or Grid display options for a richer presentation of featured menu items.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-menu/editorial-cards.webp',
                'fields'   => [
                    'grid' => [
                        'label'      => __('Display in Grid mode (Default is Carousel)', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'display_in_grid',
                        'default'    => false,  
                    ],
                ],
            ],
        ],
    ],

    'top_offers' => [
        'type'            => 'section',
        'label'           => __('Offers & Packages', 'patlis-core'),
        'shortcode_slug'  => 'top-offers',
        'availability'    => 'offers-packages-section',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'top-offers-classic',
                'description' => __('Clean card-based layout presenting selected offers and packages with images, key details and promotional information, ideal for a clear and easy-to-scan overview.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-offers/classic.webp',
                'fields'   => [],
            ],
            'split-screen' => [
                'label'    => __('Split Screen', 'patlis-core'),
                'template' => 'top-offers-split-screen',
                'description' => __('Two-column layout combining an introductory content area with a structured list of offers and packages, ideal for highlighting promotions alongside supporting text and optional imagery.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-offers/split-screen.webp',
                'fields'   => [
                    'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                        'default'    => false,
                    ],
                    'image' => [
                        'label'      => __('Image (optional)', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                    'overlay_opacity' => [
                        'label'      => __('Overlay opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'overlay_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                    'description' => [
                        'label'      => __('Description (optional)', 'patlis-core'),
                        'type'       => 'textarea',
                        'option_key' => 'description',
                    ]
                ],
            ],
            'cinematic' => [
                'label'    => __('Cinematic', 'patlis-core'),
                'template' => 'top-offers-cinematic',
                'preview'  => 'assets/design-previews/top-offers/cinematic.webp',
                'description' => __('Immersive background-image layout with promotional cards layered on top, ideal for showcasing special offers and creating a strong visual impact.', 'patlis-core'),
                'fields'   => [
                    'image' => [
                        'label'      => __('Background Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                    'opacity' => [
                        'label'      => __('Background Opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,   
                        'default'    => 0.5,
                    ],
                ],
            ],
        ],
    ], 
    
    'top_reviews' => [
        'type'            => 'section',
        'label'           => __('Top Reviews', 'patlis-core'),
        'shortcode_slug'  => 'top-reviews',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'reviews-classic',
                'description' => __('Card-based carousel displaying multiple guest reviews at once, ideal for presenting a broader selection of feedback in a clear and familiar layout.', 'patlis-core'),
                'preview'  => 'assets/design-previews/reviews/classic.webp',
                'fields'   => [],
            ],
            'minimal' => [
                'label'    => __('Minimal', 'patlis-core'),
                'template' => 'reviews-minimal',
                'preview'  => 'assets/design-previews/reviews/minimal.webp',
                'description' => __('Clean single-review layout with subtle navigation and generous whitespace, ideal for highlighting one testimonial at a time with an elegant and focused presentation.
                The image is not mandatory.', 'patlis-core'),
                'fields'   => [
                    'decorative-image' => [
                        'label'      => __('Decorative Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'decorative_image_id',
                    ],
                    'image-opacity' => [
                        'label'      => __('Overlay Opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'image_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                ],
            ],
            'cinematic' => [
                'label'    => __('Cinematic', 'patlis-core'),
                'template' => 'reviews-cinematic',
                'description' => __('Immersive background-image layout combining review highlights with overall rating information, ideal for creating a stronger visual impact and premium atmosphere.', 'patlis-core'),
                'preview'  => 'assets/design-previews/reviews/cinematic.webp',
                'fields'   => [
                    'background-image' => [
                        'label'      => __('Background Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'background_image_id',
                    ],
                    'image-opacity' => [
                        'label'      => __('Overlay Opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'image_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                    'google-summary' => [
                        'label'      => __('Google Summary', 'patlis-core'),
                        'type'       => 'text',
                        'option_key' => 'google_summary',
                    ],
                    'google-score' => [
                        'label'      => __('Google Score', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'google_score',
                        'min'        => 0,
                        'max'        => 5,
                        'step'       => 0.1,
                    ],
                    
                ],
            ],
        ],
    ],
    
    'top_rooms' => [
        'type'            => 'section',
        'label'           => __('Rooms', 'patlis-core'),
        'shortcode_slug'  => 'top-rooms',
        'availability'    => 'top-rooms-section',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'template' => 'top-rooms-classic',
                'description' => __('Clean card-based layout with room image, title, view and key room details.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-rooms/classic.webp',
                'fields'   => [
                    'grid' => [
                        'label'      => __('Display in Grid mode (Default is Carousel)', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'display_in_grid',
                        'default'    => false,  
                    ],
                ],
            ],
            'showcase' => [
                'label'    => __('Showcase', 'patlis-core'),
                'template' => 'top-rooms-showcase',
                'description' => __('Image-focused layout with room details overlaid directly on the photos.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-rooms/showcase.webp',
                'fields'   => [
                    'grid' => [
                        'label'      => __('Display in Grid mode (Default is Carousel)', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'display_in_grid',
                        'default'    => false,  
                    ],
                ],
            ],
            'cinematic' => [
                'label'    => __('Cinematic', 'patlis-core'),
                'template' => 'top-rooms-cinematic',
                'description' => __('Full-background layout with floating room cards for a more immersive presentation.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-rooms/cinematic.webp',
                'fields'   => [
                    'image' => [
                        'label'      => __('Image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                    'opacity' => [
                        'label'      => __('Overlay Opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                ],
            ],
            'split-screen' => [
                'label'    => __('Split Screen', 'patlis-core'),
                'template' => 'top-rooms-split-screen',
                'description' => __('Two-column layout with introductory content on the left and up to 3 selected rooms displayed in compact rows on the right.', 'patlis-core'),
                'preview'  => 'assets/design-previews/top-rooms/split-screen.webp',
                'fields'   => [
                    'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                    ],
                    'image' => [
                        'label'      => __('Image (optional)', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image',
                    ],
                    'overlay_opacity' => [
                        'label'      => __('Overlay opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'overlay_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                    'description' => [
                        'label'      => __('Description (optional)', 'patlis-core'),
                        'type'       => 'textarea',
                        'option_key' => 'description',
                    ],
                ],
            ],
        ],
    ],

    'top_services' => [
        'type'            => 'section',
        'label'           => __('Services', 'patlis-core'),
        'shortcode_slug'  => 'top-services',
        'default_variant' => 'classic',
        'variants' => [
            'classic' => [
                'label'    => __('Classic', 'patlis-core'),
                'description' => __('Clean card-based layout presenting selected services with image, title and short description, ideal for a clear and easy-to-scan overview.', 'patlis-core'),
                'template' => 'top-services-classic',
                'preview'  => 'assets/design-previews/top-services/classic.webp',
                'fields'   => [],
            ],
            'cinematic' => [
                'label'    => __('Cinematic', 'patlis-core'),
                'description' => __('Immersive background-image layout with service cards layered on top, ideal for creating a stronger visual impact and atmospheric presentation.', 'patlis-core'),
                'template' => 'top-services-cinematic',
                'preview'  => 'assets/design-previews/top-services/cinematic.webp',
                    'fields'   => [
                        'background_image' => [
                        'label'      => __('Background image', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'background_image_id',
                        ],
                        'overlay_opacity' => [
                            'label'      => __('Overlay opacity', 'patlis-core'),
                            'type'       => 'range',
                            'option_key' => 'overlay_opacity',
                            'min'        => 0,
                            'max'        => 1,
                            'step'       => 0.05,
                            'default'    => 0.5,
                        ],
                    ],
            ],
            'split-screen' => [
                'label'    => __('Split Screen', 'patlis-core'),
                'template' => 'top-services-split-screen',
                'preview'  => 'assets/design-previews/top-services/split-screen.webp',
                'description' => __('Two-column layout combining an introductory content area with a structured list of services, ideal for balancing storytelling with detailed service highlights.', 'patlis-core'),
                'fields'   => [
                    'reverse' => [
                        'label'      => __('Reverse Order', 'patlis-core'),
                        'type'       => 'checkbox',
                        'option_key' => 'reverse',
                        'default'    => false,
                    ],
                    'description' => [
                        'label'      => __('Description (optional)', 'patlis-core'),
                        'type'       => 'textarea',
                        'option_key' => 'description',
                    ],
                    'image' => [
                        'label'      => __('Decorative Image', 'patlis-core'),
                        'description' => __('Optional decorative image for the split-screen layout.', 'patlis-core'),
                        'type'       => 'image',
                        'option_key' => 'image_id',
                    ],
                    'overlay_opacity' => [
                        'label'      => __('Overlay opacity', 'patlis-core'),
                        'type'       => 'range',
                        'option_key' => 'overlay_opacity',
                        'min'        => 0,
                        'max'        => 1,
                        'step'       => 0.05,
                        'default'    => 0.5,
                    ],
                ],
            ],
        ],
    ],    
];