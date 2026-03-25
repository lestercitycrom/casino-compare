<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_breadcrumb_schema(?int $post_id = null): array
{
    $items = [];

    foreach (ccc_get_breadcrumbs($post_id) as $index => $crumb) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $crumb['label'],
            'item' => $crumb['url'],
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    ];
}

function ccc_get_faq_schema(?int $post_id = null): ?array
{
    $post_id = $post_id ?: get_queried_object_id();
    $faq = get_post_meta($post_id, 'faq', true);

    if (!is_array($faq) || $faq === []) {
        return null;
    }

    $entities = [];

    foreach ($faq as $item) {
        if (!is_array($item) || empty($item['question']) || empty($item['answer'])) {
            continue;
        }

        $entities[] = [
            '@type' => 'Question',
            'name' => wp_strip_all_tags((string) $item['question']),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => wp_kses_post((string) $item['answer']),
            ],
        ];
    }

    if ($entities === []) {
        return null;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $entities,
    ];
}

function ccc_get_review_schema(int $post_id): array
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Review',
        'itemReviewed' => [
            '@type' => 'Organization',
            'name' => get_the_title($post_id),
        ],
        'reviewRating' => [
            '@type' => 'Rating',
            'ratingValue' => (string) get_post_meta($post_id, 'overall_rating', true),
            'bestRating' => '5',
            'worstRating' => '0',
        ],
        'name' => get_the_title($post_id),
        'description' => (string) get_post_meta($post_id, 'intro_text', true),
    ];
}

function ccc_get_landing_item_list_schema(int $post_id): ?array
{
    $cards = get_post_meta($post_id, 'casino_cards', true);

    if (!is_array($cards) || $cards === []) {
        return null;
    }

    $elements = [];

    foreach ($cards as $index => $card) {
        $casino_id = (int) ($card['casino_id'] ?? 0);

        if ($casino_id <= 0) {
            continue;
        }

        $elements[] = [
            '@type' => 'ListItem',
            'position' => (int) ($card['rank'] ?? ($index + 1)),
            'url' => get_permalink($casino_id),
            'name' => get_the_title($casino_id),
        ];
    }

    if ($elements === []) {
        return null;
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => get_the_title($post_id),
        'itemListElement' => $elements,
    ];
}

function ccc_get_guide_article_schema(int $post_id): array
{
    $published = get_the_date(DATE_W3C, $post_id);
    $modified = get_the_modified_date(DATE_W3C, $post_id);
    $image_url = get_the_post_thumbnail_url($post_id, 'full');

    return [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title($post_id),
        'datePublished' => $published ?: null,
        'dateModified' => $modified ?: null,
        'author' => [
            '@type' => 'Person',
            'name' => (string) get_post_meta($post_id, 'author_name', true) ?: get_bloginfo('name'),
        ],
        'description' => (string) get_post_meta($post_id, 'intro_text', true),
        'mainEntityOfPage' => get_permalink($post_id),
        'image' => $image_url ?: null,
    ];
}

function ccc_get_schema_graph(?int $post_id = null): array
{
    $post_id = $post_id ?: get_queried_object_id();
    $graph = [];

    if ($post_id <= 0) {
        return $graph;
    }

    $post = get_post($post_id);

    if (!$post instanceof WP_Post) {
        return $graph;
    }

    $graph[] = ccc_get_breadcrumb_schema($post_id);

    if ($post->post_type === 'casino') {
        $graph[] = ccc_get_review_schema($post_id);
    }

    if ($post->post_type === 'landing' && get_post_meta($post_id, 'landing_type', true) === 'comparison') {
        $item_list = ccc_get_landing_item_list_schema($post_id);

        if ($item_list !== null) {
            $graph[] = $item_list;
        }
    }

    if ($post->post_type === 'guide') {
        $graph[] = ccc_get_guide_article_schema($post_id);
    }

    $faq_schema = ccc_get_faq_schema($post_id);

    if ($faq_schema !== null) {
        $graph[] = $faq_schema;
    }

    return $graph;
}

function ccc_output_schema(): void
{
    if (!is_singular(['casino', 'casino_subpage', 'landing', 'guide'])) {
        return;
    }

    foreach (ccc_get_schema_graph() as $schema) {
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
add_action('wp_head', 'ccc_output_schema', 30);
