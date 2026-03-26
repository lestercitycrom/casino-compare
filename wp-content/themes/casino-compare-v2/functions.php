<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// =====================================================
// THEME SETUP
// =====================================================

function ccv2_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'script', 'style']);

    register_nav_menus([
        'primary' => __('Primary Navigation', 'casino-compare-v2'),
        'footer'  => __('Footer Navigation', 'casino-compare-v2'),
    ]);
}
add_action('after_setup_theme', 'ccv2_theme_setup');

// =====================================================
// ENQUEUE ASSETS
// =====================================================

function ccv2_enqueue_assets(): void
{
    $version = '1.0.0';

    // Google Fonts — Space Grotesk 300–700
    wp_enqueue_style(
        'ccv2-google-fonts',
        'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap',
        [],
        null
    );

    // Main stylesheet (style.css — vars + reset)
    wp_enqueue_style(
        'ccv2-style',
        get_stylesheet_uri(),
        ['ccv2-google-fonts'],
        $version
    );

    // Component styles
    wp_enqueue_style(
        'ccv2-main',
        get_template_directory_uri() . '/assets/css/main.css',
        ['ccv2-style'],
        $version
    );

    // Main JS (footer)
    wp_enqueue_script(
        'ccv2-main-js',
        get_template_directory_uri() . '/assets/js/main.js',
        [],
        $version,
        ['in_footer' => true]
    );

    // Compare JS (footer)
    wp_enqueue_script(
        'ccv2-compare',
        get_template_directory_uri() . '/assets/js/compare.js',
        [],
        $version,
        ['in_footer' => true]
    );

    // Localize compare script
    wp_localize_script('ccv2-compare', 'cccTheme', [
        'restUrl' => esc_url_raw(rest_url()),
    ]);
}
add_action('wp_enqueue_scripts', 'ccv2_enqueue_assets');

// =====================================================
// HELPER FUNCTIONS (copied from casino-compare-theme)
// =====================================================

/**
 * Get a single post meta value with a default fallback.
 */
function cct_get_meta(string $key, ?int $post_id = null, $default = '')
{
    $post_id = $post_id ?: get_the_ID();
    $value   = get_post_meta($post_id, $key, true);

    if ($value === '' || $value === null) {
        return $default;
    }

    return $value;
}

/**
 * Check whether a value has meaningful content.
 */
function cct_has_content($value): bool
{
    if (is_array($value)) {
        return !empty(array_filter($value, static fn($item) => $item !== '' && $item !== null && $item !== []));
    }

    return trim(wp_strip_all_tags((string) $value)) !== '';
}

/**
 * Normalize an ACF/custom repeater field value into a clean array.
 */
function cct_normalize_repeater($rows): array
{
    return is_array($rows)
        ? array_values(array_filter($rows, static fn($row) => is_array($row) ? !empty(array_filter($row)) : !empty($row)))
        : [];
}

/**
 * Extract a list of text values from a repeater field.
 */
function cct_repeater_text_list($rows, string $key = 'name'): array
{
    $normalized = cct_normalize_repeater($rows);

    return array_values(array_filter(array_map(static function ($row) use ($key): string {
        return is_array($row) ? trim((string) ($row[$key] ?? '')) : '';
    }, $normalized)));
}

/**
 * Normalize a repeater of link rows (label + url).
 */
function cct_normalize_link_rows($rows): array
{
    return array_values(array_filter(array_map(static function ($row): array {
        if (!is_array($row)) {
            return [];
        }

        return [
            'label' => trim((string) ($row['label'] ?? $row['title'] ?? '')),
            'url'   => trim((string) ($row['url'] ?? '')),
        ];
    }, cct_normalize_repeater($rows)), static fn(array $row): bool => $row['label'] !== '' && $row['url'] !== ''));
}

/**
 * Get the permalink of the first published landing page of a given type.
 */
function cct_get_first_landing_url(string $landing_type): string
{
    $posts = get_posts([
        'post_type'      => 'landing',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => 'landing_type',
        'meta_value'     => $landing_type,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ]);

    if ($posts === []) {
        return '';
    }

    return (string) get_permalink((int) $posts[0]);
}

/**
 * Get the permalink of the most recent published guide.
 */
function cct_get_first_guide_url(): string
{
    $posts = get_posts([
        'post_type'      => 'guide',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
    ]);

    if ($posts === []) {
        return '';
    }

    return (string) get_permalink((int) $posts[0]);
}

/**
 * Get the top N casinos sorted by overall_rating descending.
 */
function cct_get_top_casinos(int $limit = 3): array
{
    // First: casinos with a rating, sorted desc
    $rated = get_posts([
        'post_type'      => 'casino',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'meta_key'       => 'overall_rating',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ]);

    if (count($rated) >= $limit) {
        return $rated;
    }

    // Fill remaining slots with unrated casinos
    $rated_ids = array_column($rated, 'ID');
    $unrated   = get_posts([
        'post_type'      => 'casino',
        'post_status'    => 'publish',
        'posts_per_page' => $limit - count($rated),
        'post__not_in'   => $rated_ids ?: [0],
        'orderby'        => 'title',
        'order'          => 'ASC',
        'meta_query'     => [['key' => 'overall_rating', 'compare' => 'NOT EXISTS']],
    ]);

    return array_merge($rated, $unrated);
}
