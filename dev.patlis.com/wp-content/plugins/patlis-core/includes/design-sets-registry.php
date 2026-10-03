<?php
if (!defined('ABSPATH')) exit;

return [

    /*
     * 1. BALANCED
     * Safe, elegant, με αρκετή ποικιλία χωρίς πολλά full-image sections.
     */
    'balanced' => [
        'label'   => __('Balanced Set', 'patlis-core'),
        'preview' => 'assets/design-previews/design-sets/balanced.webp',
        'description' => __('A balanced design set with a mix of different section types.', 'patlis-core'),

        'sections' => [
            'welcome_section' => 'default',
            'top_services'    => 'split-screen',
            'top_menu'        => 'editorial-cards',
            'top_reviews'     => 'minimal',
            'top_gallery'     => 'default',
            'cta'             => 'split-screen',
            'footer'          => 'footer-classic',
        ],
    ],


    /*
     * 2. CINEMATIC
     * Δυνατά visual σημεία, αλλά όχι full-image section πάνω σε full-image section.
     */
    'cinematic' => [
        'label'   => __('Cinematic Set', 'patlis-core'),
        'preview' => 'assets/design-previews/design-sets/cinematic.webp',
        'description' => __('Do not use slides.', 'patlis-core'),

        'sections' => [
            'welcome_section' => 'cinematic',
            'top_services'    => 'split-screen',
            'top_menu'        => 'editorial-cards',
            'top_reviews'     => 'cinematic',
            'top_gallery'     => 'default',
            'cta'             => 'framed',
            'footer'          => 'footer-cinematic',
        ],
        'colors' => [
            'primary'   => '#ff0000',
            'secondary' => '#00ff00',
            'accent'    => '#0000ff',
        ],
    ],


    /*
     * 3. EDITORIAL
     * Περισσότερο restaurant / boutique χαρακτήρας.
     * Πιο ήρεμο, με cards και whitespace.
     */
    'editorial' => [
        'label'   => __('Editorial Set', 'patlis-core'),
        'preview' => 'assets/design-previews/design-sets/editorial.webp',
        'description' => __('An editorial design set with a calm, card-based layout.', 'patlis-core'),

        'sections' => [
            'welcome_section' => 'image-with-corners',
            'top_services'    => 'default',
            'top_menu'        => 'editorial-cards',
            'top_reviews'     => 'classic',
            'top_gallery'     => 'default',
            'cta'             => 'floating',
            'footer'          => 'footer-floating',
        ],
    ],


    /*
     * 4. VISUAL
     * Πιο έντονο design, αλλά κρατάμε spacing μεταξύ των image-heavy sections.
     */
    'visual' => [
        'label'   => __('Visual Set', 'patlis-core'),
        'preview' => 'assets/design-previews/design-sets/visual.webp',
        'description' => __('Do not use Slides.', 'patlis-core'),

        'sections' => [
            'welcome_section' => 'image-floating-content',
            'top_services'    => 'cinematic',
            'top_menu'        => 'default',
            'top_reviews'     => 'minimal',
            'top_gallery'     => 'cinematic',
            'cta'             => 'split-screen',
            'footer'          => 'footer-floating',
        ],
    ],

];