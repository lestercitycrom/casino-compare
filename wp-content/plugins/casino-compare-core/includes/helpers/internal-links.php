<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_money_pages_for_casino(int $casino_id): array
{
    $links = [];
    $landing_ids = array_map('intval', (array) get_post_meta($casino_id, 'money_page_links', true));

    foreach ($landing_ids as $landing_id) {
        if ($landing_id <= 0) {
            continue;
        }

        $links[] = [
            'label' => get_the_title($landing_id),
            'url' => get_permalink($landing_id),
        ];
    }

    return $links;
}

function ccc_get_sibling_subpages(int $subpage_id): array
{
    $parent_casino = (int) get_post_meta($subpage_id, 'parent_casino', true);

    if ($parent_casino <= 0) {
        return [];
    }

    $siblings = get_posts([
        'post_type' => 'casino_subpage',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'post__not_in' => [$subpage_id],
        'meta_query' => [
            [
                'key' => 'parent_casino',
                'value' => (string) $parent_casino,
            ],
        ],
    ]);

    return array_map(static function (WP_Post $post): array {
        return [
            'label' => get_the_title($post),
            'url' => get_permalink($post),
        ];
    }, $siblings);
}

function ccc_get_alternative_casinos(int $casino_id): array
{
    $alternatives = [];
    $alternative_ids = array_map('intval', (array) get_post_meta($casino_id, 'alternative_casinos', true));

    foreach ($alternative_ids as $alternative_id) {
        if ($alternative_id <= 0) {
            continue;
        }

        $alternatives[] = [
            'label' => get_the_title($alternative_id),
            'url' => get_permalink($alternative_id),
        ];
    }

    return $alternatives;
}

function ccc_get_related_casino_ids_for_post(int $post_id): array
{
    $post = get_post($post_id);

    if (!$post instanceof WP_Post) {
        return [];
    }

    if ($post->post_type === 'casino') {
        return [$post_id];
    }

    if ($post->post_type === 'casino_subpage') {
        $parent_casino = (int) get_post_meta($post_id, 'parent_casino', true);
        return $parent_casino > 0 ? [$parent_casino] : [];
    }

    if ($post->post_type === 'guide') {
        return array_values(array_filter(array_map('intval', (array) get_post_meta($post_id, 'sidebar_casino_list', true))));
    }

    if ($post->post_type !== 'landing') {
        return [];
    }

    $landing_type = (string) get_post_meta($post_id, 'landing_type', true);

    if ($landing_type === 'hub') {
        return array_values(array_filter(array_map('intval', (array) get_post_meta($post_id, 'top_casino_list', true))));
    }

    if ($landing_type === 'comparison') {
        $cards = get_post_meta($post_id, 'casino_cards', true);
        $casino_ids = [];

        foreach ((array) $cards as $card) {
            if (!is_array($card) || empty($card['casino_id'])) {
                continue;
            }

            $casino_ids[] = (int) $card['casino_id'];
        }

        return array_values(array_filter($casino_ids));
    }

    return [];
}

function ccc_get_taxonomy_term_ids_for_casinos(array $casino_ids): array
{
    $term_ids = [];

    foreach (array_unique(array_filter(array_map('intval', $casino_ids))) as $casino_id) {
        foreach (['casino_license', 'casino_feature', 'payment_method', 'game_type'] as $taxonomy) {
            $terms = wp_get_post_terms($casino_id, $taxonomy, ['fields' => 'ids']);

            if (is_wp_error($terms) || $terms === []) {
                continue;
            }

            $term_ids = array_merge($term_ids, array_map('intval', $terms));
        }
    }

    return array_values(array_unique(array_filter($term_ids)));
}

function ccc_get_manual_cross_silo_links(int $post_id): array
{
    $raw_links = get_post_meta($post_id, 'cross_silo_links', true);

    if (!is_array($raw_links)) {
        return [];
    }

    return array_values(array_filter($raw_links, static function ($link): bool {
        return is_array($link) && !empty($link['label']) && !empty($link['url']);
    }));
}

function ccc_get_cross_silo_links(int $post_id): array
{
    $reference_term_ids = ccc_get_taxonomy_term_ids_for_casinos(ccc_get_related_casino_ids_for_post($post_id));

    if ($reference_term_ids === []) {
        return ccc_get_manual_cross_silo_links($post_id);
    }

    $candidates = get_posts([
        'post_type' => ['landing', 'guide'],
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'post__not_in' => [$post_id],
    ]);

    $scored_links = [];

    foreach ($candidates as $candidate) {
        $candidate_term_ids = ccc_get_taxonomy_term_ids_for_casinos(ccc_get_related_casino_ids_for_post((int) $candidate->ID));

        if ($candidate_term_ids === []) {
            continue;
        }

        $shared_terms = array_intersect($reference_term_ids, $candidate_term_ids);

        if ($shared_terms === []) {
            continue;
        }

        $scored_links[] = [
            'label' => get_the_title($candidate),
            'url' => get_permalink($candidate),
            'score' => count($shared_terms),
        ];
    }

    if ($scored_links === []) {
        return ccc_get_manual_cross_silo_links($post_id);
    }

    usort($scored_links, static fn(array $left, array $right): int => $right['score'] <=> $left['score']);

    return array_map(static function (array $link): array {
        return [
            'label' => $link['label'],
            'url' => $link['url'],
        ];
    }, array_slice($scored_links, 0, 6));
}
