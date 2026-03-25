<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_seo_title(?int $post_id = null): string
{
    $post_id = $post_id ?: get_queried_object_id();
    $custom_title = $post_id > 0 ? (string) get_post_meta($post_id, 'seo_title', true) : '';

    if ($custom_title !== '') {
        return $custom_title;
    }

    if ($post_id > 0) {
        return trim(get_the_title($post_id) . ' | ' . get_bloginfo('name'));
    }

    return get_bloginfo('name');
}

function ccc_get_seo_description(?int $post_id = null): string
{
    $post_id = $post_id ?: get_queried_object_id();

    if ($post_id <= 0) {
        return get_bloginfo('description');
    }

    $description = (string) get_post_meta($post_id, 'meta_description', true);

    if ($description !== '') {
        return $description;
    }

    foreach (['intro_text', 'page_content', 'main_content', 'final_verdict'] as $key) {
        $value = trim(wp_strip_all_tags((string) get_post_meta($post_id, $key, true)));

        if ($value !== '') {
            return wp_trim_words($value, 30, '...');
        }
    }

    $excerpt = trim(wp_strip_all_tags((string) get_post_field('post_excerpt', $post_id)));

    return $excerpt !== '' ? $excerpt : get_bloginfo('description');
}

function ccc_get_seo_image_url(?int $post_id = null): string
{
    $post_id = $post_id ?: get_queried_object_id();

    if ($post_id <= 0) {
        return '';
    }

    $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');

    if (is_string($thumbnail_url) && $thumbnail_url !== '') {
        return $thumbnail_url;
    }

    $logo_id = (int) get_post_meta($post_id, 'logo', true);

    if ($logo_id > 0) {
        $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        return is_string($logo_url) ? $logo_url : '';
    }

    return '';
}

function ccc_get_og_type(?int $post_id = null): string
{
    $post_id = $post_id ?: get_queried_object_id();

    if ($post_id <= 0) {
        return 'article';
    }

    if (get_post_type($post_id) === 'landing') {
        $landing_type = (string) get_post_meta($post_id, 'landing_type', true);

        if (in_array($landing_type, ['hub', 'trust'], true)) {
            return 'website';
        }
    }

    return 'article';
}

function ccc_filter_document_title(string $title): string
{
    if (!is_singular(['casino', 'casino_subpage', 'landing', 'guide'])) {
        return $title;
    }

    return ccc_get_seo_title() ?: $title;
}
add_filter('pre_get_document_title', 'ccc_filter_document_title');

function ccc_output_seo_meta(): void
{
    if (!is_singular(['casino', 'casino_subpage', 'landing', 'guide'])) {
        return;
    }

    $post_id = get_queried_object_id();

    if ($post_id <= 0) {
        return;
    }

    $title = ccc_get_seo_title($post_id);
    $description = ccc_get_seo_description($post_id);
    $image = ccc_get_seo_image_url($post_id);
    $canonical = get_permalink($post_id);

    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr(ccc_get_og_type($post_id)) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";

    if ($image !== '') {
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
    }
}
add_action('wp_head', 'ccc_output_seo_meta', 5);

function ccc_filter_sitemaps_post_types(array $post_types): array
{
    foreach (['casino', 'landing', 'guide', 'casino_subpage'] as $post_type) {
        $object = get_post_type_object($post_type);

        if ($object instanceof WP_Post_Type) {
            $post_types[$post_type] = $object;
        }
    }

    return $post_types;
}
add_filter('wp_sitemaps_post_types', 'ccc_filter_sitemaps_post_types');

function ccc_filter_sitemaps_taxonomies(array $taxonomies): array
{
    unset($taxonomies['casino_license'], $taxonomies['casino_feature'], $taxonomies['payment_method'], $taxonomies['game_type']);

    return $taxonomies;
}
add_filter('wp_sitemaps_taxonomies', 'ccc_filter_sitemaps_taxonomies');

function ccc_filter_subpage_sitemap_query_args(array $args, string $post_type): array
{
    if ($post_type !== 'casino_subpage') {
        return $args;
    }

    $args['meta_query'] = [
        'relation' => 'OR',
        [
            'key' => 'main_content',
            'value' => '',
            'compare' => '!=',
        ],
        [
            'key' => 'intro_text',
            'value' => '',
            'compare' => '!=',
        ],
    ];

    return $args;
}
add_filter('wp_sitemaps_posts_query_args', 'ccc_filter_subpage_sitemap_query_args', 10, 2);