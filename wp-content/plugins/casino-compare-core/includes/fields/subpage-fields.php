<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_subpage_types(): array
{
    return [
        'bonus' => 'bonus',
        'bonus_sans_depot' => 'bonus_sans_depot',
        'bonus_bienvenue' => 'bonus_bienvenue',
        'free_spins' => 'free_spins',
        'code_promo' => 'code_promo',
        'promotions' => 'promotions',
        'fiable' => 'fiable',
        'arnaque' => 'arnaque',
        'licence' => 'licence',
        'trustpilot' => 'trustpilot',
        'verification_kyc' => 'verification_kyc',
        'inscription' => 'inscription',
        'connexion' => 'connexion',
        'app' => 'app',
        'contact' => 'contact',
        'retrait' => 'retrait',
        'modes_de_paiement' => 'modes_de_paiement',
        'limite' => 'limite',
        'jeux' => 'jeux',
        'live' => 'live',
        'rtp' => 'rtp',
        'paris_sportifs' => 'paris_sportifs',
        'alternatives' => 'alternatives',
        'sites_soeurs' => 'sites_soeurs',
    ];
}

function ccc_get_phase_one_subpage_types(): array
{
    return ['bonus', 'bonus_sans_depot', 'bonus_bienvenue', 'free_spins', 'fiable', 'arnaque', 'inscription', 'retrait'];
}

function ccc_get_subpage_field_map(): array
{
    return [
        'parent_casino' => ['label' => __('Parent Casino', 'casino-compare-core'), 'type' => 'relation', 'layout' => 'half', 'post_type' => 'casino'],
        'subpage_type' => ['label' => __('Subpage Type', 'casino-compare-core'), 'type' => 'select', 'layout' => 'half', 'options' => ccc_get_subpage_types(), 'placeholder' => __('Select type', 'casino-compare-core')],
        'hero_title' => ['label' => __('Hero Title', 'casino-compare-core'), 'type' => 'text', 'layout' => 'full'],
        'intro_text' => ['label' => __('Intro Text', 'casino-compare-core'), 'type' => 'textarea'],
        'main_content' => ['label' => __('Main Content', 'casino-compare-core'), 'type' => 'wysiwyg'],
        'cta_text' => ['label' => __('CTA Text', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
        'cta_url' => ['label' => __('CTA URL', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
        'score_enabled' => ['label' => __('Enable Score Block', 'casino-compare-core'), 'type' => 'boolean', 'layout' => 'full'],
        'score_value' => ['label' => __('Score Value', 'casino-compare-core'), 'type' => 'number', 'layout' => 'half', 'step' => '0.1'],
        'score_label' => ['label' => __('Score Label', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
        'table_enabled' => ['label' => __('Enable Table Block', 'casino-compare-core'), 'type' => 'boolean', 'layout' => 'full'],
        'table_headers' => ['label' => __('Table Headers', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['label' => __('Header', 'casino-compare-core')]],
        'table_rows' => ['label' => __('Table Rows', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => [
            'col_1' => ['label' => __('Column 1', 'casino-compare-core'), 'type' => 'text'],
            'col_2' => ['label' => __('Column 2', 'casino-compare-core'), 'type' => 'text'],
            'col_3' => ['label' => __('Column 3', 'casino-compare-core'), 'type' => 'text'],
            'col_4' => ['label' => __('Column 4', 'casino-compare-core'), 'type' => 'text'],
            'col_5' => ['label' => __('Column 5', 'casino-compare-core'), 'type' => 'text'],
            'col_6' => ['label' => __('Column 6', 'casino-compare-core'), 'type' => 'text'],
        ]],
        'faq' => ['label' => __('FAQ', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]],
        'seo_title' => ['label' => __('SEO Title', 'casino-compare-core'), 'type' => 'text'],
        'meta_description' => ['label' => __('Meta Description', 'casino-compare-core'), 'type' => 'textarea'],
    ];
}

function ccc_register_subpage_meta_boxes(): void
{
    add_meta_box('ccc_subpage_main', __('Subpage Settings', 'casino-compare-core'), 'ccc_render_subpage_main_box', 'casino_subpage', 'normal', 'default');
    add_meta_box('ccc_subpage_score', __('Score Block', 'casino-compare-core'), 'ccc_render_subpage_score_box', 'casino_subpage', 'normal', 'default');
    add_meta_box('ccc_subpage_table', __('Table Block', 'casino-compare-core'), 'ccc_render_subpage_table_box', 'casino_subpage', 'normal', 'default');
    add_meta_box('ccc_subpage_seo', __('SEO', 'casino-compare-core'), 'ccc_render_subpage_seo_box', 'casino_subpage', 'side', 'default');
}
add_action('add_meta_boxes_casino_subpage', 'ccc_register_subpage_meta_boxes');

function ccc_render_subpage_main_box(WP_Post $post): void
{
    ccc_render_meta_box_nonce('ccc_save_subpage_fields', 'ccc_subpage_nonce');
    ccc_render_fields_collection(ccc_expand_field_map([
        'parent_casino' => ccc_get_subpage_field_map()['parent_casino'],
        'subpage_type' => ccc_get_subpage_field_map()['subpage_type'],
        'hero_title' => ccc_get_subpage_field_map()['hero_title'],
        'intro_text' => ccc_get_subpage_field_map()['intro_text'],
        'main_content' => ccc_get_subpage_field_map()['main_content'],
        'cta_text' => ccc_get_subpage_field_map()['cta_text'],
        'cta_url' => ccc_get_subpage_field_map()['cta_url'],
    ]), $post->ID);
}

function ccc_render_subpage_score_box(WP_Post $post): void
{
    ccc_admin_grid_open();
    ccc_render_boolean('score_enabled', __('Enable Score Block', 'casino-compare-core'), (bool) ccc_get_meta_value($post->ID, 'score_enabled'), ccc_get_subpage_field_map()['score_enabled']);
    echo '<div data-ccc-condition-field="score_enabled" data-ccc-condition-value="1">';
    ccc_admin_grid_open();
    ccc_render_number('score_value', __('Score Value', 'casino-compare-core'), ccc_get_meta_value($post->ID, 'score_value'), ccc_get_subpage_field_map()['score_value']);
    ccc_render_text('score_label', __('Score Label', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'score_label'), ccc_get_subpage_field_map()['score_label']);
    ccc_admin_grid_close();
    echo '</div>';
    ccc_admin_grid_close();
}

function ccc_render_subpage_table_box(WP_Post $post): void
{
    ccc_admin_grid_open();
    ccc_render_boolean('table_enabled', __('Enable Table Block', 'casino-compare-core'), (bool) ccc_get_meta_value($post->ID, 'table_enabled'), ccc_get_subpage_field_map()['table_enabled']);
    echo '<div data-ccc-condition-field="table_enabled" data-ccc-condition-value="1">';
    ccc_admin_grid_open();
    ccc_render_repeater('table_headers', __('Table Headers', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'table_headers', []), ccc_get_subpage_field_map()['table_headers']['subfields'], ccc_get_subpage_field_map()['table_headers']);
    ccc_render_repeater('table_rows', __('Table Rows', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'table_rows', []), ccc_get_subpage_field_map()['table_rows']['subfields'], ccc_get_subpage_field_map()['table_rows']);
    ccc_admin_grid_close();
    echo '</div>';
    ccc_admin_grid_close();
}

function ccc_render_subpage_seo_box(WP_Post $post): void
{
    ccc_render_fields_collection(ccc_expand_field_map([
        'faq' => ccc_get_subpage_field_map()['faq'],
        'seo_title' => ccc_get_subpage_field_map()['seo_title'],
        'meta_description' => ccc_get_subpage_field_map()['meta_description'],
    ]), $post->ID);
}

function ccc_save_subpage_fields(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'casino_subpage') {
        return;
    }

    if (!isset($_POST['ccc_subpage_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ccc_subpage_nonce'])), 'ccc_save_subpage_fields')) {
        return;
    }

    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (ccc_get_subpage_field_map() as $key => $field) {
        $type = $field['type'];
        $raw = $_POST[$key] ?? ($type === 'boolean' ? 0 : null);
        if ($raw === null) {
            delete_post_meta($post_id, $key);
            continue;
        }
        update_post_meta($post_id, $key, ccc_sanitize_field($type, wp_unslash($raw), $field));
    }
}
add_action('save_post_casino_subpage', 'ccc_save_subpage_fields', 10, 2);

function ccc_seed_phase_one_subpages(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'casino' || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || $post->post_status !== 'publish') {
        return;
    }

    if (get_post_meta($post_id, '_ccc_phase_one_subpages_seeded', true)) {
        return;
    }

    foreach (ccc_get_phase_one_subpage_types() as $subpage_type) {
        $existing = get_posts([
            'post_type' => 'casino_subpage',
            'post_status' => ['draft', 'publish', 'pending', 'private'],
            'posts_per_page' => 1,
            'meta_query' => [
                ['key' => 'parent_casino', 'value' => (string) $post_id],
                ['key' => 'subpage_type', 'value' => $subpage_type],
            ],
            'fields' => 'ids',
        ]);

        if ($existing !== []) {
            continue;
        }

        $subpage_id = wp_insert_post([
            'post_type' => 'casino_subpage',
            'post_status' => 'draft',
            'post_title' => sprintf('%s - %s', $post->post_title, $subpage_type),
        ]);

        if (!is_wp_error($subpage_id) && $subpage_id > 0) {
            update_post_meta($subpage_id, 'parent_casino', $post_id);
            update_post_meta($subpage_id, 'subpage_type', $subpage_type);
        }
    }

    update_post_meta($post_id, '_ccc_phase_one_subpages_seeded', 1);
}
add_action('save_post_casino', 'ccc_seed_phase_one_subpages', 20, 2);