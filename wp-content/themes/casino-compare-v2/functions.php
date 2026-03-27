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
    add_theme_support('custom-logo', [
        'height'      => 60,
        'width'       => 220,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

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
    $tpl_uri = get_template_directory_uri();

    // Self-hosted Space Grotesk (no external request)
    wp_enqueue_style(
        'ccv2-fonts',
        $tpl_uri . '/assets/fonts/space-grotesk.css',
        [],
        $version
    );

    // Main stylesheet (style.css — vars + reset)
    wp_enqueue_style(
        'ccv2-style',
        get_stylesheet_uri(),
        ['ccv2-fonts'],
        $version
    );

    // Component styles
    wp_enqueue_style(
        'ccv2-main',
        $tpl_uri . '/assets/css/main.css',
        ['ccv2-style'],
        $version
    );

    // Main JS — all pages (small: FAQ accordion, mobile menu)
    wp_enqueue_script(
        'ccv2-main-js',
        $tpl_uri . '/assets/js/main.js',
        [],
        $version,
        ['in_footer' => true, 'strategy' => 'defer']
    );

    // Compare JS — only on pages that need it
    $needs_compare = is_front_page()
        || is_singular(['casino', 'landing', 'guide'])
        || is_page_template('templates/compare-page.php');

    if ($needs_compare) {
        wp_enqueue_script(
            'ccv2-compare',
            $tpl_uri . '/assets/js/compare.js',
            [],
            $version,
            ['in_footer' => true, 'strategy' => 'defer']
        );

        wp_localize_script('ccv2-compare', 'cccTheme', [
            'restUrl' => esc_url_raw(rest_url()),
        ]);
    }
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

// =====================================================
// WORDPRESS CUSTOMIZER
// =====================================================

/**
 * Register Customizer sections, settings, and controls.
 */
function ccv2_customize_register(WP_Customize_Manager $wp_customize): void
{
    // ── Brand & Colors ───────────────────────────────────────────────────────
    $wp_customize->add_section('ccv2_brand', [
        'title'    => __('Brand & Colors', 'casino-compare-v2'),
        'priority' => 25,
    ]);

    $wp_customize->add_setting('ccv2_accent_color', [
        'default'           => '#10b981',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ccv2_accent_color', [
        'label'       => __('Accent Color', 'casino-compare-v2'),
        'description' => __('Main CTA/highlight color (buttons, links, accents).', 'casino-compare-v2'),
        'section'     => 'ccv2_brand',
    ]));

    // ── Homepage Hero ────────────────────────────────────────────────────────
    $wp_customize->add_section('ccv2_homepage', [
        'title'    => __('Homepage Hero', 'casino-compare-v2'),
        'priority' => 30,
    ]);

    $wp_customize->add_setting('ccv2_hero_title', [
        'default'           => 'Trouvez le meilleur casino en ligne pour vous',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('ccv2_hero_title', [
        'label'   => __('Hero Title', 'casino-compare-v2'),
        'section' => 'ccv2_homepage',
        'type'    => 'text',
    ]);

    $wp_customize->add_setting('ccv2_hero_subtitle', [
        'default'           => 'Notre équipe teste et compare les meilleurs casinos pour vous aider à faire le bon choix.',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('ccv2_hero_subtitle', [
        'label'   => __('Hero Subtitle', 'casino-compare-v2'),
        'section' => 'ccv2_homepage',
        'type'    => 'textarea',
    ]);

    // ── Footer ───────────────────────────────────────────────────────────────
    $wp_customize->add_section('ccv2_footer', [
        'title'    => __('Footer', 'casino-compare-v2'),
        'priority' => 35,
    ]);

    $wp_customize->add_setting('ccv2_footer_copyright', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('ccv2_footer_copyright', [
        'label'       => __('Footer Copyright Text', 'casino-compare-v2'),
        'description' => __('Leave empty to auto-generate from site name.', 'casino-compare-v2'),
        'section'     => 'ccv2_footer',
        'type'        => 'text',
    ]);
}
add_action('customize_register', 'ccv2_customize_register');

/**
 * Output dynamic CSS vars from Customizer settings.
 * Overrides the :root defaults in style.css.
 */
function ccv2_customizer_head_css(): void
{
    $accent = get_theme_mod('ccv2_accent_color', '#10b981');
    if ($accent === '#10b981') {
        return; // default — no override needed
    }
    ?>
<style id="ccv2-dynamic-css">
:root {
    --color-accent:       <?php echo esc_attr($accent); ?>;
    --color-accent-hover: color-mix(in srgb, <?php echo esc_attr($accent); ?> 80%, black);
}
</style>
    <?php
}
add_action('wp_head', 'ccv2_customizer_head_css');

// =====================================================

/**
 * Get the top N casinos sorted by overall_rating descending.
 */
function cct_get_top_casinos(int $limit = 3): array
{
    // First: casinos with a rating, sorted desc
    $rated = get_posts([
        'post_type'               => 'casino',
        'post_status'             => 'publish',
        'posts_per_page'          => $limit,
        'meta_key'                => 'overall_rating',
        'orderby'                 => 'meta_value_num',
        'order'                   => 'DESC',
        'update_post_meta_cache'  => true,
        'update_post_term_cache'  => false,
    ]);

    if (count($rated) >= $limit) {
        return $rated;
    }

    // Fill remaining slots with unrated casinos
    $rated_ids = array_column($rated, 'ID');
    $unrated   = get_posts([
        'post_type'              => 'casino',
        'post_status'            => 'publish',
        'posts_per_page'         => $limit - count($rated),
        'post__not_in'           => $rated_ids ?: [0],
        'orderby'                => 'title',
        'order'                  => 'ASC',
        'meta_query'             => [['key' => 'overall_rating', 'compare' => 'NOT EXISTS']],
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ]);

    return array_merge($rated, $unrated);
}
