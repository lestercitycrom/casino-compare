<?php

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'casino-compare.local';
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require dirname(__DIR__) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$failures = [];
$tracked_post_ids = [];
$original_permalink_structure = (string) get_option('permalink_structure', '');
$permalink_structure_changed = false;

function smoke_log(string $type, string $message): void
{
    echo sprintf("[%s] %s\n", $type, $message);
}

function smoke_pass(string $message): void
{
    smoke_log('PASS', $message);
}

function smoke_fail(string $message): void
{
    global $failures;

    $failures[] = $message;
    smoke_log('FAIL', $message);
}

function smoke_assert(bool $condition, string $message): void
{
    if ($condition) {
        smoke_pass($message);
        return;
    }

    smoke_fail($message);
}

function smoke_track_post(int $post_id): void
{
    global $tracked_post_ids;

    if ($post_id > 0 && !in_array($post_id, $tracked_post_ids, true)) {
        $tracked_post_ids[] = $post_id;
    }
}

function smoke_create_post(array $postarr, string $label): int
{
    $post_id = wp_insert_post($postarr, true);

    if ($post_id instanceof WP_Error) {
        throw new RuntimeException(sprintf('%s creation failed: %s', $label, $post_id->get_error_message()));
    }

    smoke_track_post((int) $post_id);

    return (int) $post_id;
}

function smoke_http_status(string $url): int
{
    $response = wp_remote_get($url, [
        'timeout' => 15,
        'redirection' => 1,
    ]);

    if (is_wp_error($response)) {
        return 0;
    }

    return (int) wp_remote_retrieve_response_code($response);
}

function smoke_find_subpage(int $casino_id, string $subpage_type): ?WP_Post
{
    $posts = get_posts([
        'post_type' => 'casino_subpage',
        'post_status' => ['draft', 'publish'],
        'posts_per_page' => 1,
        'meta_query' => [
            [
                'key' => 'parent_casino',
                'value' => (string) $casino_id,
            ],
            [
                'key' => 'subpage_type',
                'value' => $subpage_type,
            ],
        ],
    ]);

    return $posts[0] ?? null;
}

register_shutdown_function(static function (): void {
    global $tracked_post_ids, $original_permalink_structure, $permalink_structure_changed;

    rsort($tracked_post_ids);

    foreach ($tracked_post_ids as $post_id) {
        wp_delete_post($post_id, true);
    }

    if ($permalink_structure_changed) {
        update_option('permalink_structure', $original_permalink_structure);
        flush_rewrite_rules();
    }
});

try {
    smoke_log('INFO', 'Starting casino compare smoke test');

    smoke_assert(is_plugin_active('casino-compare-core/plugin.php'), 'Core plugin is active');
    smoke_assert(wp_get_theme()->get_stylesheet() === 'casino-compare-theme', 'Casino compare theme is active');
    smoke_assert(post_type_exists('casino'), 'Post type `casino` is registered');
    smoke_assert(post_type_exists('casino_subpage'), 'Post type `casino_subpage` is registered');
    smoke_assert(post_type_exists('landing'), 'Post type `landing` is registered');
    smoke_assert(post_type_exists('guide'), 'Post type `guide` is registered');
    smoke_assert(taxonomy_exists('casino_license'), 'Taxonomy `casino_license` is registered');
    smoke_assert(taxonomy_exists('casino_feature'), 'Taxonomy `casino_feature` is registered');
    smoke_assert(taxonomy_exists('payment_method'), 'Taxonomy `payment_method` is registered');
    smoke_assert(taxonomy_exists('game_type'), 'Taxonomy `game_type` is registered');
    smoke_assert((bool) term_exists('mga', 'casino_license'), 'Seed term `mga` exists');
    smoke_assert((bool) term_exists('visa', 'payment_method'), 'Seed term `visa` exists');

    $compare_page_id = function_exists('ccc_ensure_compare_page') ? ccc_ensure_compare_page() : 0;
    smoke_assert($compare_page_id > 0, 'System page `/comparer/` exists');
    smoke_assert(
        get_post_meta($compare_page_id, '_wp_page_template', true) === 'templates/compare-page.php',
        'Compare page uses `templates/compare-page.php`'
    );
    if ($original_permalink_structure !== '/%postname%/') {
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules();
        $permalink_structure_changed = true;
    }

    $suffix = gmdate('YmdHis');

    $casino_a_id = smoke_create_post([
        'post_type' => 'casino',
        'post_status' => 'publish',
        'post_title' => 'Smoke Casino A ' . $suffix,
        'post_name' => 'smoke-casino-a-' . $suffix,
    ], 'casino A');

    update_post_meta($casino_a_id, 'seo_title', 'Smoke Casino A SEO');
    update_post_meta($casino_a_id, 'meta_description', 'Smoke Casino A description');
    update_post_meta($casino_a_id, 'overall_rating', '4.7');
    update_post_meta($casino_a_id, 'welcome_bonus_text', '200% + 100 FS');
    update_post_meta($casino_a_id, 'wagering', '35x');
    update_post_meta($casino_a_id, 'withdrawal_time_min', '1h');
    update_post_meta($casino_a_id, 'withdrawal_time_max', '24h');
    update_post_meta($casino_a_id, 'trustpilot_score', '4.4');
    update_post_meta($casino_a_id, 'license', 'MGA');
    update_post_meta($casino_a_id, 'intro_text', 'Smoke intro casino A');
    update_post_meta($casino_a_id, 'summary_1_title', 'Smoke Summary Heading');
    update_post_meta($casino_a_id, 'summary_1', '<p>Smoke summary content</p>');
    wp_set_post_terms($casino_a_id, ['mga'], 'casino_license');
    wp_set_post_terms($casino_a_id, ['live-casino'], 'casino_feature');
    wp_set_post_terms($casino_a_id, ['visa'], 'payment_method');
    wp_set_post_terms($casino_a_id, ['slots'], 'game_type');

    $casino_b_id = smoke_create_post([
        'post_type' => 'casino',
        'post_status' => 'publish',
        'post_title' => 'Smoke Casino B ' . $suffix,
        'post_name' => 'smoke-casino-b-' . $suffix,
    ], 'casino B');

    update_post_meta($casino_b_id, 'overall_rating', '4.3');
    update_post_meta($casino_b_id, 'welcome_bonus_text', '100% + 50 FS');
    update_post_meta($casino_b_id, 'wagering', '30x');
    update_post_meta($casino_b_id, 'withdrawal_time_min', '2h');
    update_post_meta($casino_b_id, 'withdrawal_time_max', '36h');
    update_post_meta($casino_b_id, 'trustpilot_score', '4.1');
    update_post_meta($casino_b_id, 'license', 'MGA');
    update_post_meta($casino_b_id, 'intro_text', 'Smoke intro casino B');
    wp_set_post_terms($casino_b_id, ['mga'], 'casino_license');
    wp_set_post_terms($casino_b_id, ['mobile'], 'casino_feature');
    wp_set_post_terms($casino_b_id, ['visa'], 'payment_method');
    wp_set_post_terms($casino_b_id, ['slots'], 'game_type');

    $phase_one_subpages = get_posts([
        'post_type' => 'casino_subpage',
        'post_status' => ['draft', 'publish'],
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'parent_casino',
                'value' => (string) $casino_a_id,
            ],
        ],
    ]);

    foreach ($phase_one_subpages as $subpage) {
        smoke_track_post((int) $subpage->ID);
    }

    smoke_assert(count($phase_one_subpages) === count(ccc_get_phase_one_subpage_types()), 'Publishing a casino seeds 8 phase 1 subpages');

    $bonus_subpage = smoke_find_subpage($casino_a_id, 'bonus');
    $free_spins_subpage = smoke_find_subpage($casino_a_id, 'free_spins');

    smoke_assert($bonus_subpage instanceof WP_Post, 'Seeded bonus subpage exists');
    smoke_assert($free_spins_subpage instanceof WP_Post, 'Seeded free spins subpage exists');

    if ($bonus_subpage instanceof WP_Post) {
        wp_update_post([
            'ID' => $bonus_subpage->ID,
            'post_status' => 'publish',
            'post_title' => 'Smoke Bonus ' . $suffix,
        ]);
        update_post_meta($bonus_subpage->ID, 'hero_title', 'Smoke Bonus Hero');
        update_post_meta($bonus_subpage->ID, 'intro_text', 'Bonus intro');
        update_post_meta($bonus_subpage->ID, 'main_content', '<p>Bonus content</p>');
    }

    if ($free_spins_subpage instanceof WP_Post) {
        wp_update_post([
            'ID' => $free_spins_subpage->ID,
            'post_status' => 'publish',
            'post_title' => 'Smoke Free Spins ' . $suffix,
        ]);
        update_post_meta($free_spins_subpage->ID, 'hero_title', 'Smoke Free Spins Hero');
        update_post_meta($free_spins_subpage->ID, 'intro_text', 'Free spins intro');
        update_post_meta($free_spins_subpage->ID, 'main_content', '<p>Free spins content</p>');
    }

    $hub_landing_id = smoke_create_post([
        'post_type' => 'landing',
        'post_status' => 'publish',
        'post_title' => 'Smoke Bonus Hub ' . $suffix,
        'post_name' => 'smoke-bonus-hub-' . $suffix,
    ], 'hub landing');

    update_post_meta($hub_landing_id, 'landing_type', 'hub');
    update_post_meta($hub_landing_id, 'hero_title', 'Smoke Hub Hero');
    update_post_meta($hub_landing_id, 'intro_text', 'Smoke hub intro');
    update_post_meta($hub_landing_id, 'top_casino_list', [$casino_a_id, $casino_b_id]);

    $comparison_landing_id = smoke_create_post([
        'post_type' => 'landing',
        'post_status' => 'publish',
        'post_title' => 'Smoke Sans Depot ' . $suffix,
        'post_name' => 'sans-depot',
        'post_parent' => $hub_landing_id,
    ], 'comparison landing');

    update_post_meta($comparison_landing_id, 'landing_type', 'comparison');
    update_post_meta($comparison_landing_id, 'hero_title', 'Smoke Comparison Hero');
    update_post_meta($comparison_landing_id, 'intro_text', 'Smoke comparison intro');
    update_post_meta($comparison_landing_id, 'last_updated', '2026-03-25');
    update_post_meta($comparison_landing_id, 'author_name', 'Operator Smoke');
    update_post_meta($comparison_landing_id, 'casinos_tested_count', '2');
    update_post_meta($comparison_landing_id, 'casino_cards', [
        [
            'casino_id' => $casino_a_id,
            'rank' => 1,
            'short_review' => 'Casino A short review',
        ],
        [
            'casino_id' => $casino_b_id,
            'rank' => 2,
            'short_review' => 'Casino B short review',
        ],
    ]);
    update_post_meta($comparison_landing_id, 'methodology_content', '<p>Methodology</p>');
    update_post_meta($comparison_landing_id, 'bottom_content', '<p>Bottom content</p>');
    update_post_meta($comparison_landing_id, 'seo_title', 'Smoke Comparison SEO');
    update_post_meta($comparison_landing_id, 'meta_description', 'Smoke comparison description');

    $trust_landing_id = smoke_create_post([
        'post_type' => 'landing',
        'post_status' => 'publish',
        'post_title' => 'Smoke Trust ' . $suffix,
        'post_name' => 'smoke-trust-' . $suffix,
    ], 'trust landing');

    update_post_meta($trust_landing_id, 'landing_type', 'trust');
    update_post_meta($trust_landing_id, 'hero_title', 'Smoke Trust Hero');
    update_post_meta($trust_landing_id, 'intro_text', 'Smoke trust intro');
    update_post_meta($trust_landing_id, 'show_author', '1');
    update_post_meta($trust_landing_id, 'trust_author_name', 'Trust Operator');
    update_post_meta($trust_landing_id, 'trust_last_updated', '2026-03-26');
    update_post_meta($trust_landing_id, 'page_content', '<p>Trust content</p>');

    $guide_id = smoke_create_post([
        'post_type' => 'guide',
        'post_status' => 'publish',
        'post_title' => 'Smoke Guide ' . $suffix,
        'post_name' => 'smoke-guide-' . $suffix,
    ], 'guide');

    update_post_meta($guide_id, 'author_name', 'Operator Smoke');
    update_post_meta($guide_id, 'intro_text', 'Guide intro');
    update_post_meta($guide_id, 'main_content', '<p>Guide content</p>');
    update_post_meta($guide_id, 'sidebar_casino_list', [$casino_b_id]);

    update_post_meta($casino_a_id, 'money_page_links', [$comparison_landing_id]);
    update_post_meta($casino_a_id, 'alternative_casinos', [$casino_b_id]);

    $casino_permalink = get_permalink($casino_a_id);
    $bonus_permalink = $bonus_subpage instanceof WP_Post ? get_permalink($bonus_subpage->ID) : '';
    $comparison_permalink = get_permalink($comparison_landing_id);
    $trust_permalink = get_permalink($trust_landing_id);
    $guide_permalink = get_permalink($guide_id);
    $compare_page_permalink = get_permalink($compare_page_id);

    smoke_assert(str_contains((string) $casino_permalink, '/avis/smoke-casino-a-' . $suffix . '/'), 'Casino permalink matches `/avis/{slug}/`');
    smoke_assert(str_contains((string) $bonus_permalink, '/avis/smoke-casino-a-' . $suffix . '/bonus/'), 'Subpage permalink matches `/avis/{casino}/{subpage}/`');
    smoke_assert(str_contains((string) $comparison_permalink, '/smoke-bonus-hub-' . $suffix . '/sans-depot/'), 'Nested landing permalink matches parent/child path');
    smoke_assert(str_contains((string) $trust_permalink, '/smoke-trust-' . $suffix . '/'), 'Trust landing permalink resolves');
    smoke_assert(str_contains((string) $guide_permalink, '/guide/smoke-guide-' . $suffix . '/'), 'Guide permalink matches `/guide/{slug}/`');
    smoke_assert(str_contains((string) $compare_page_permalink, '/comparer/'), 'Compare page permalink matches `/comparer/`');

    smoke_assert(smoke_http_status(home_url('/')) === 200, 'Homepage returns HTTP 200');
    smoke_assert(smoke_http_status((string) $casino_permalink) === 200, 'Casino page returns HTTP 200');
    smoke_assert(smoke_http_status((string) $bonus_permalink) === 200, 'Casino subpage returns HTTP 200');
    smoke_assert(smoke_http_status((string) $comparison_permalink) === 200, 'Landing page returns HTTP 200');
    smoke_assert(smoke_http_status((string) $trust_permalink) === 200, 'Trust landing page returns HTTP 200');
    smoke_assert(smoke_http_status((string) $guide_permalink) === 200, 'Guide page returns HTTP 200');
    smoke_assert(smoke_http_status((string) $compare_page_permalink) === 200, 'Compare page returns HTTP 200');

    $casino_page_html = wp_remote_retrieve_body(wp_remote_get((string) $casino_permalink, ['timeout' => 15, 'redirection' => 1]));
    $comparison_page_html = wp_remote_retrieve_body(wp_remote_get((string) $comparison_permalink, ['timeout' => 15, 'redirection' => 1]));
    $trust_page_html = wp_remote_retrieve_body(wp_remote_get((string) $trust_permalink, ['timeout' => 15, 'redirection' => 1]));
    smoke_assert(str_contains((string) $casino_page_html, 'Smoke Summary Heading'), 'Casino page renders custom summary heading');
    smoke_assert(((int) substr_count((string) $comparison_page_html, 'var cccTheme =')) === 1, 'Comparison landing localizes cccTheme only once');
    smoke_assert(str_contains((string) $trust_page_html, 'og:type') && str_contains((string) $trust_page_html, 'website'), 'Trust landing outputs og:type website');

    $money_page_links = ccc_get_money_pages_for_casino($casino_a_id);
    smoke_assert(count($money_page_links) === 1 && $money_page_links[0]['url'] === $comparison_permalink, 'Money page links helper returns linked landing');

    $alternative_links = ccc_get_alternative_casinos($casino_a_id);
    smoke_assert(count($alternative_links) === 1 && $alternative_links[0]['url'] === get_permalink($casino_b_id), 'Alternative casinos helper returns linked casino');

    $sibling_links = $bonus_subpage instanceof WP_Post ? ccc_get_sibling_subpages($bonus_subpage->ID) : [];
    smoke_assert(count($sibling_links) >= 1, 'Sibling subpages helper returns related subpages');

    $cross_silo_links = ccc_get_cross_silo_links($guide_id);
    $cross_silo_urls = array_column($cross_silo_links, 'url');
    smoke_assert(in_array($comparison_permalink, $cross_silo_urls, true) || in_array(get_permalink($hub_landing_id), $cross_silo_urls, true), 'Cross-silo helper derives related landing links');

    $compare_request = new WP_REST_Request('GET', '/ccc/v1/compare');
    $compare_request->set_param('ids', $casino_a_id . ',' . $casino_b_id);
    $compare_response = rest_do_request($compare_request);
    $compare_payload = $compare_response->get_data();

    smoke_assert($compare_response->get_status() === 200, 'Compare REST endpoint returns HTTP 200');
    smoke_assert(count((array) ($compare_payload['items'] ?? [])) === 2, 'Compare REST endpoint returns 2 casinos');
    smoke_assert(array_keys((array) ($compare_payload['fields'] ?? [])) === array_keys(ccc_comparison_fields()), 'Compare REST fields match `ccc_comparison_fields()`');

    $filter_request = new WP_REST_Request('GET', '/ccc/v1/filter');
    $filter_request->set_param('license', ['mga']);
    $filter_response = rest_do_request($filter_request);
    $filter_payload = $filter_response->get_data();

    smoke_assert($filter_response->get_status() === 200, 'Filter REST endpoint returns HTTP 200');
    smoke_assert(
        str_contains((string) ($filter_payload['html'] ?? ''), 'Smoke Casino A ' . $suffix) &&
        str_contains((string) ($filter_payload['html'] ?? ''), 'Smoke Casino B ' . $suffix),
        'Filter REST endpoint renders matching casino cards'
    );

    $breadcrumbs = ccc_get_breadcrumbs($comparison_landing_id);
    smoke_assert(count($breadcrumbs) === 3, 'Breadcrumb helper returns home + parent landing + child landing');

    $casino_schema_graph = ccc_get_schema_graph($casino_a_id);
    $comparison_schema_graph = ccc_get_schema_graph($comparison_landing_id);
    $casino_schema_types = array_column($casino_schema_graph, '@type');
    $comparison_schema_types = array_column($comparison_schema_graph, '@type');

    smoke_assert(in_array('Review', $casino_schema_types, true), 'Casino schema graph contains Review schema');
    smoke_assert(in_array('ItemList', $comparison_schema_types, true), 'Comparison landing schema graph contains ItemList schema');

    $review_schema = ccc_get_review_schema($casino_a_id);
    smoke_assert(($review_schema['author']['@type'] ?? '') === 'Organization', 'Casino review schema includes author organization');

    smoke_assert(ccc_get_seo_title($comparison_landing_id) === 'Smoke Comparison SEO', 'SEO title helper returns custom title');
    smoke_assert(ccc_get_seo_description($comparison_landing_id) === 'Smoke comparison description', 'SEO description helper returns custom description');

    $filter_cache_version_before = ccc_get_filter_cache_version();
    wp_update_post([
        'ID' => $casino_a_id,
        'post_title' => 'Smoke Casino A ' . $suffix . ' Updated',
    ]);
    clean_post_cache($casino_a_id);
    smoke_assert(ccc_get_filter_cache_version() === ($filter_cache_version_before + 1), 'Filter cache version increments on casino save');

    smoke_assert((string) get_post_meta($trust_landing_id, 'trust_author_name', true) === 'Trust Operator', 'Trust landing keeps trust-specific author meta');
    smoke_assert((string) get_post_meta($trust_landing_id, 'trust_last_updated', true) === '2026-03-26', 'Trust landing keeps trust-specific last updated meta');
    smoke_assert((string) get_post_meta($comparison_landing_id, 'author_name', true) === 'Operator Smoke', 'Comparison landing author meta is not overwritten by trust fields');
    smoke_assert((string) get_post_meta($comparison_landing_id, 'last_updated', true) === '2026-03-25', 'Comparison landing last updated meta is not overwritten by trust fields');

    $sitemap_query_args = apply_filters('wp_sitemaps_posts_query_args', [], 'casino_subpage');
    smoke_assert(isset($sitemap_query_args['meta_query']), 'Subpage sitemap query adds populated-content restriction');

    if ($failures === []) {
        smoke_log('INFO', 'Smoke test finished successfully');
        exit(0);
    }

    smoke_log('INFO', sprintf('Smoke test finished with %d failure(s)', count($failures)));
    exit(1);
} catch (Throwable $throwable) {
    smoke_fail('Unhandled exception: ' . $throwable->getMessage());
    smoke_log('INFO', 'Smoke test aborted due to fatal runtime exception');
    exit(1);
}


