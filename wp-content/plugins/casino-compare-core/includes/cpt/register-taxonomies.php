<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_register_taxonomies(): void
{
    $definitions = [
        'casino_license' => __('Casino Licenses', 'casino-compare-core'),
        'casino_feature' => __('Casino Features', 'casino-compare-core'),
        'payment_method' => __('Payment Methods', 'casino-compare-core'),
        'game_type' => __('Game Types', 'casino-compare-core'),
    ];

    foreach ($definitions as $taxonomy => $label) {
        register_taxonomy($taxonomy, ['casino'], [
            'labels' => [
                'name' => $label,
                'singular_name' => $label,
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => false,
            'hierarchical' => false,
        ]);
    }
}

function ccc_get_base_terms(): array
{
    return [
        'casino_license' => [
            'mga' => 'MGA',
            'curacao' => 'Curacao',
            'kahnawake' => 'Kahnawake',
        ],
        'casino_feature' => [
            'live-casino' => 'Live Casino',
            'mobile' => 'Mobile',
            'vip' => 'VIP',
            'no-deposit' => 'No Deposit',
        ],
        'payment_method' => [
            'visa' => 'Visa',
            'skrill' => 'Skrill',
            'bitcoin' => 'Bitcoin',
            'paypal' => 'PayPal',
        ],
        'game_type' => [
            'slots' => 'Slots',
            'roulette' => 'Roulette',
            'blackjack' => 'Blackjack',
            'live-dealer' => 'Live Dealer',
        ],
    ];
}

function ccc_seed_base_terms(): void
{
    foreach (ccc_get_base_terms() as $taxonomy => $terms) {
        foreach ($terms as $slug => $name) {
            if (!term_exists($slug, $taxonomy)) {
                wp_insert_term($name, $taxonomy, ['slug' => $slug]);
            }
        }
    }
}

function ccc_filter_taxonomy_robots(array $robots): array
{
    if (is_tax(['casino_license', 'casino_feature', 'payment_method', 'game_type'])) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }

    return $robots;
}
add_filter('wp_robots', 'ccc_filter_taxonomy_robots');
