<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_register_filter_endpoint(): void
{
    register_rest_route('ccc/v1', '/filter', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ccc_handle_filter_endpoint',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'ccc_register_filter_endpoint');

function ccc_get_filter_query_args(array $params): array
{
    $tax_query = [];
    $map = [
        'license' => 'casino_license',
        'feature' => 'casino_feature',
        'payment' => 'payment_method',
        'game' => 'game_type',
    ];

    foreach ($map as $param => $taxonomy) {
        $values = array_filter(array_map('sanitize_title', (array) ($params[$param] ?? [])));

        if ($values === []) {
            continue;
        }

        $tax_query[] = [
            'taxonomy' => $taxonomy,
            'field' => 'slug',
            'terms' => $values,
        ];
    }

    $query_args = [
        'post_type' => 'casino',
        'post_status' => 'publish',
        'posts_per_page' => 20,
        'update_post_meta_cache' => true,
    ];

    if (count($tax_query) > 1) {
        $query_args['tax_query'] = array_merge(['relation' => 'AND'], $tax_query);
    } elseif ($tax_query !== []) {
        $query_args['tax_query'] = $tax_query;
    }

    $sort = sanitize_key((string) ($params['sort'] ?? ''));
    if ($sort === 'rating') {
        $query_args['meta_key'] = 'overall_rating';
        $query_args['orderby'] = 'meta_value_num';
        $query_args['order'] = 'DESC';
    } elseif ($sort === 'name') {
        $query_args['orderby'] = 'title';
        $query_args['order'] = 'ASC';
    }

    return $query_args;
}

function ccc_render_filter_results(array $params): string
{
    $cache_key = 'ccc_filter_' . md5(serialize($params));
    $cached = get_transient($cache_key);

    if (is_string($cached)) {
        return $cached;
    }

    $query = new WP_Query(ccc_get_filter_query_args($params));

    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            get_template_part('template-parts/casino-card', null, ['casino_id' => get_the_ID()]);
        }
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__('No casinos found.', 'casino-compare-core') . '</p>';
    }
    $html = (string) ob_get_clean();

    set_transient($cache_key, $html, HOUR_IN_SECONDS);

    return $html;
}

function ccc_handle_filter_endpoint(WP_REST_Request $request): WP_REST_Response
{
    $html = ccc_render_filter_results($request->get_params());

    return new WP_REST_Response(['html' => $html], 200);
}

function ccc_clear_filter_cache(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'casino') {
        return;
    }

    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ccc_filter_%' OR option_name LIKE '_transient_timeout_ccc_filter_%'");
}
add_action('save_post', 'ccc_clear_filter_cache', 30, 2);
