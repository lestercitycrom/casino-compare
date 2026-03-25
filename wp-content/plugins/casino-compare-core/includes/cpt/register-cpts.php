<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_register_post_types(): void
{
    register_post_type('casino', [
        'labels' => [
            'name' => __('Casinos', 'casino-compare-core'),
            'singular_name' => __('Casino', 'casino-compare-core'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-awards',
        'supports' => ['title', 'thumbnail', 'revisions'],
        'rewrite' => [
            'slug' => 'avis',
            'with_front' => false,
        ],
    ]);

    register_post_type('casino_subpage', [
        'labels' => [
            'name' => __('Casino Subpages', 'casino-compare-core'),
            'singular_name' => __('Casino Subpage', 'casino-compare-core'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-media-spreadsheet',
        'supports' => ['title', 'revisions'],
        'rewrite' => false,
    ]);

    register_post_type('landing', [
        'labels' => [
            'name' => __('Landings', 'casino-compare-core'),
            'singular_name' => __('Landing', 'casino-compare-core'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'hierarchical' => true,
        'menu_icon' => 'dashicons-layout',
        'supports' => ['title', 'page-attributes', 'revisions'],
        'rewrite' => false,
    ]);

    register_post_type('guide', [
        'labels' => [
            'name' => __('Guides', 'casino-compare-core'),
            'singular_name' => __('Guide', 'casino-compare-core'),
        ],
        'public' => true,
        'show_in_rest' => true,
        'has_archive' => false,
        'menu_icon' => 'dashicons-media-document',
        'supports' => ['title', 'revisions'],
        'rewrite' => [
            'slug' => 'guide',
            'with_front' => false,
        ],
    ]);
}
