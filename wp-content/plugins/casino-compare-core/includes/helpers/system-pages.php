<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_system_page_marker_key(): string
{
    return '_ccc_system_page';
}

function ccc_get_compare_page_marker(): string
{
    return 'compare';
}

function ccc_is_owned_system_page(int $page_id, string $marker): bool
{
    return get_post_meta($page_id, ccc_get_system_page_marker_key(), true) === $marker;
}

function ccc_ensure_compare_page(): int
{
    $page = get_page_by_path('comparer', OBJECT, 'page');
    $page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
    $compare_template = 'templates/compare-page.php';
    $compare_marker = ccc_get_compare_page_marker();

    if ($page_id > 0) {
        if (ccc_is_owned_system_page($page_id, $compare_marker)) {
            update_post_meta($page_id, '_wp_page_template', $compare_template);
            return $page_id;
        }

        if (get_post_meta($page_id, '_wp_page_template', true) === $compare_template) {
            return $page_id;
        }

        return 0;
    }

    $page_id = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => __('Comparer', 'casino-compare-core'),
        'post_name' => 'comparer',
    ]);

    if (!is_wp_error($page_id) && $page_id > 0) {
        update_post_meta($page_id, ccc_get_system_page_marker_key(), $compare_marker);
        update_post_meta($page_id, '_wp_page_template', $compare_template);
        return (int) $page_id;
    }

    return 0;
}

function ccc_ensure_system_pages(): void
{
    ccc_ensure_compare_page();
}
add_action('init', 'ccc_ensure_system_pages', 20);
