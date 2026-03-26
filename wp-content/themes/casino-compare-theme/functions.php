<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function cct_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'script', 'style']);

    register_nav_menus([
        'primary' => __('Primary Navigation', 'casino-compare-theme'),
    ]);
}
add_action('after_setup_theme', 'cct_theme_setup');

function cct_enqueue_assets(): void
{
    $theme = wp_get_theme();
    $version = $theme->get('Version') ?: '0.1.0';
    $current_post_id = get_queried_object_id();
    $is_compare_page = is_page_template('templates/compare-page.php');
    $needs_filter = is_singular('landing') && cct_get_meta('landing_type', $current_post_id) === 'comparison';
    $needs_compare = $is_compare_page || is_singular(['casino', 'landing', 'guide']);

    wp_enqueue_style(
        'cct-style',
        get_stylesheet_uri(),
        [],
        $version
    );

    if ($needs_compare) {
        wp_enqueue_script(
            'cct-compare',
            get_template_directory_uri() . '/assets/js/compare.js',
            [],
            $version,
            [
                'strategy' => 'defer',
                'in_footer' => false,
            ]
        );
    }

    if ($needs_filter) {
        wp_enqueue_script(
            'cct-filter',
            get_template_directory_uri() . '/assets/js/filter.js',
            [],
            $version,
            [
                'strategy' => 'defer',
                'in_footer' => false,
            ]
        );
    }

    $localize_handle = $needs_compare ? 'cct-compare' : ($needs_filter ? 'cct-filter' : '');

    if ($localize_handle !== '') {
        wp_localize_script($localize_handle, 'cccTheme', [
            'restUrl' => esc_url_raw(rest_url()),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'cct_enqueue_assets');

function cct_get_meta(string $key, ?int $post_id = null, $default = '')
{
    $post_id = $post_id ?: get_the_ID();
    $value = get_post_meta($post_id, $key, true);

    if ($value === '' || $value === null) {
        return $default;
    }

    return $value;
}

function cct_has_content($value): bool
{
    if (is_array($value)) {
        return !empty(array_filter($value, static fn($item) => $item !== '' && $item !== null && $item !== []));
    }

    return trim(wp_strip_all_tags((string) $value)) !== '';
}

function cct_normalize_repeater($rows): array
{
    return is_array($rows) ? array_values(array_filter($rows, static fn($row) => is_array($row) ? !empty(array_filter($row)) : !empty($row))) : [];
}

function cct_repeater_text_list($rows, string $key = 'name'): array
{
    $normalized = cct_normalize_repeater($rows);

    return array_values(array_filter(array_map(static function ($row) use ($key): string {
        return is_array($row) ? trim((string) ($row[$key] ?? '')) : '';
    }, $normalized)));
}

function cct_normalize_link_rows($rows): array
{
    return array_values(array_filter(array_map(static function ($row): array {
        if (!is_array($row)) {
            return [];
        }

        return [
            'label' => trim((string) ($row['label'] ?? $row['title'] ?? '')),
            'url' => trim((string) ($row['url'] ?? '')),
        ];
    }, cct_normalize_repeater($rows)), static fn(array $row): bool => $row['label'] !== '' && $row['url'] !== ''));
}

function cct_get_first_landing_url(string $landing_type): string
{
    $posts = get_posts([
        'post_type' => 'landing',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'meta_key' => 'landing_type',
        'meta_value' => $landing_type,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
        'fields' => 'ids',
    ]);

    if ($posts === []) {
        return '';
    }

    return (string) get_permalink((int) $posts[0]);
}

function cct_get_first_guide_url(): string
{
    $posts = get_posts([
        'post_type' => 'guide',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids',
    ]);

    if ($posts === []) {
        return '';
    }

    return (string) get_permalink((int) $posts[0]);
}

function cct_get_top_casinos(int $limit = 3): array
{
    return get_posts([
        'post_type' => 'casino',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'meta_key' => 'overall_rating',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
    ]);
}
