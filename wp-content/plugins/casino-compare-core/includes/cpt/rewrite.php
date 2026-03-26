<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_register_query_vars(array $vars): array
{
    $vars[] = 'ccc_casino_slug';
    $vars[] = 'ccc_subpage_type';
    $vars[] = 'ccc_landing_path';

    return $vars;
}
add_filter('query_vars', 'ccc_register_query_vars');

function ccc_register_rewrite_rules(): void
{
    add_rewrite_rule(
        '^avis/([^/]+)/([^/]+)/?$',
        'index.php?post_type=casino_subpage&ccc_casino_slug=$matches[1]&ccc_subpage_type=$matches[2]',
        'top'
    );

    add_rewrite_rule(
        '^(.?.+?)/?$',
        'index.php?ccc_landing_path=$matches[1]',
        'bottom'
    );
}

function ccc_force_singular_query(WP_Query $query, WP_Post $post): void
{
    $query->set('post_type', $post->post_type);
    $query->set('p', (int) $post->ID);
    $query->set('page_id', 0);
    $query->set('name', (string) $post->post_name);
    $query->set('pagename', '');
    $query->set('posts_per_page', 1);
    $query->set('post__in', [(int) $post->ID]);
    $query->set('meta_query', []);

    $query->is_single = true;
    $query->is_singular = true;
    $query->is_page = false;
    $query->is_home = false;
    $query->is_archive = false;
    $query->is_post_type_archive = false;
    $query->is_404 = false;
}

function ccc_prepare_subpage_query(WP_Query $query): void
{
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    $casino_slug = $query->get('ccc_casino_slug');
    $subpage_type = $query->get('ccc_subpage_type');

    if (!$casino_slug || !$subpage_type) {
        return;
    }

    $casino = get_page_by_path(sanitize_title((string) $casino_slug), OBJECT, 'casino');

    if (!$casino instanceof WP_Post) {
        $query->set_404();
        status_header(404);
        return;
    }

    $subpages = get_posts([
        'post_type' => 'casino_subpage',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'parent_casino',
                'value' => (string) $casino->ID,
            ],
            [
                'key' => 'subpage_type',
                'value' => sanitize_key((string) $subpage_type),
            ],
        ],
    ]);

    $subpage = $subpages[0] ?? null;

    if (!$subpage instanceof WP_Post) {
        $query->set_404();
        status_header(404);
        return;
    }

    ccc_force_singular_query($query, $subpage);
}
add_action('pre_get_posts', 'ccc_prepare_subpage_query');

function ccc_find_landing_by_path(string $path): ?WP_Post
{
    $normalized_path = trim($path, '/');

    if ($normalized_path === '') {
        return null;
    }

    $landing = get_page_by_path($normalized_path, OBJECT, 'landing');

    return $landing instanceof WP_Post ? $landing : null;
}

function ccc_filter_landing_request(array $query_vars): array
{
    if (is_admin()) {
        return $query_vars;
    }

    $path = trim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
    $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');

    if ($home_path !== '' && str_starts_with($path, $home_path . '/')) {
        $path = substr($path, strlen($home_path) + 1);
    }

    if ($path === '') {
        return $query_vars;
    }

    if (str_starts_with($path, 'wp-admin') || str_starts_with($path, 'wp-json')) {
        return $query_vars;
    }

    $page = get_page_by_path($path, OBJECT, 'page');

    if ($page instanceof WP_Post) {
        return $query_vars;
    }

    $landing = ccc_find_landing_by_path(sanitize_text_field(wp_unslash($path)));

    if (!$landing instanceof WP_Post) {
        return $query_vars;
    }

    return [
        'post_type' => 'landing',
        'p' => $landing->ID,
        'name' => $landing->post_name,
    ];
}
add_filter('request', 'ccc_filter_landing_request');

function ccc_prepare_landing_query(WP_Query $query): void
{
    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    $landing_path = (string) $query->get('ccc_landing_path');

    if ($landing_path === '') {
        return;
    }

    $landing = ccc_find_landing_by_path(sanitize_text_field(wp_unslash($landing_path)));

    if (!$landing instanceof WP_Post) {
        return;
    }

    ccc_force_singular_query($query, $landing);
}
add_action('pre_get_posts', 'ccc_prepare_landing_query', 20);

function ccc_filter_subpage_permalink(string $permalink, WP_Post $post): string
{
    if ($post->post_type !== 'casino_subpage') {
        return $permalink;
    }

    $parent_casino = (int) get_post_meta($post->ID, 'parent_casino', true);
    $subpage_type = (string) get_post_meta($post->ID, 'subpage_type', true);

    if (!$parent_casino || $subpage_type === '') {
        return $permalink;
    }

    $parent_slug = get_post_field('post_name', $parent_casino);

    if (!$parent_slug) {
        return $permalink;
    }

    return home_url(sprintf('/avis/%s/%s/', $parent_slug, $subpage_type));
}
add_filter('post_type_link', 'ccc_filter_subpage_permalink', 10, 2);

function ccc_filter_landing_permalink(string $permalink, WP_Post $post): string
{
    if ($post->post_type !== 'landing') {
        return $permalink;
    }

    $landing_path = get_page_uri($post);

    if ($landing_path === '') {
        return $permalink;
    }

    return home_url('/' . $landing_path . '/');
}
add_filter('post_type_link', 'ccc_filter_landing_permalink', 10, 2);