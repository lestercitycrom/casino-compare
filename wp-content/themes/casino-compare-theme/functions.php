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
            true
        );
    }

    if ($needs_filter) {
        wp_enqueue_script(
            'cct-filter',
            get_template_directory_uri() . '/assets/js/filter.js',
            [],
            $version,
            true
        );
    }

    if ($needs_compare) {
        wp_localize_script('cct-compare', 'cccTheme', [
            'restUrl' => esc_url_raw(rest_url()),
        ]);
    }

    if ($needs_filter) {
        wp_localize_script('cct-filter', 'cccTheme', [
            'restUrl' => esc_url_raw(rest_url()),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'cct_enqueue_assets');

function cct_defer_theme_scripts(string $tag, string $handle, string $src): string
{
    if (!in_array($handle, ['cct-compare', 'cct-filter'], true)) {
        return $tag;
    }

    return sprintf('<script src="%s" defer></script>', esc_url($src));
}
add_filter('script_loader_tag', 'cct_defer_theme_scripts', 10, 3);

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
