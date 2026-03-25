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
        'parent_casino' => ['type' => 'relation'],
        'subpage_type' => ['type' => 'text'],
        'hero_title' => ['type' => 'text'],
        'intro_text' => ['type' => 'textarea'],
        'main_content' => ['type' => 'wysiwyg'],
        'cta_text' => ['type' => 'text'],
        'cta_url' => ['type' => 'text'],
        'score_enabled' => ['type' => 'boolean'],
        'score_value' => ['type' => 'number'],
        'score_label' => ['type' => 'text'],
        'table_enabled' => ['type' => 'boolean'],
        'table_headers' => ['type' => 'repeater', 'subfields' => ['label' => __('Header', 'casino-compare-core')]],
        'table_rows' => ['type' => 'repeater', 'subfields' => [
            'col_1' => ['label' => __('Column 1', 'casino-compare-core'), 'type' => 'text'],
            'col_2' => ['label' => __('Column 2', 'casino-compare-core'), 'type' => 'text'],
            'col_3' => ['label' => __('Column 3', 'casino-compare-core'), 'type' => 'text'],
            'col_4' => ['label' => __('Column 4', 'casino-compare-core'), 'type' => 'text'],
            'col_5' => ['label' => __('Column 5', 'casino-compare-core'), 'type' => 'text'],
            'col_6' => ['label' => __('Column 6', 'casino-compare-core'), 'type' => 'text'],
        ]],
        'faq' => ['type' => 'repeater', 'subfields' => ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]],
        'seo_title' => ['type' => 'text'],
        'meta_description' => ['type' => 'textarea'],
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
    wp_nonce_field('ccc_save_subpage_fields', 'ccc_subpage_nonce');

    ccc_render_relation('parent_casino', __('Parent Casino', 'casino-compare-core'), 'casino', ccc_get_meta_value($post->ID, 'parent_casino'));

    $subpage_type = (string) ccc_get_meta_value($post->ID, 'subpage_type');
    echo '<p><label for="subpage_type"><strong>' . esc_html__('Subpage Type', 'casino-compare-core') . '</strong></label><br><select class="widefat" id="subpage_type" name="subpage_type">';
    echo '<option value="">' . esc_html__('Select type', 'casino-compare-core') . '</option>';
    foreach (ccc_get_subpage_types() as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($subpage_type, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></p>';

    ccc_render_text('hero_title', __('Hero Title', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'hero_title'));
    ccc_render_textarea('intro_text', __('Intro Text', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'intro_text'), ['rows' => 5]);
    ccc_render_wysiwyg('main_content', __('Main Content', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'main_content'));
    ccc_render_text('cta_text', __('CTA Text', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'cta_text'));
    ccc_render_text('cta_url', __('CTA URL', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'cta_url'));
}

function ccc_render_subpage_score_box(WP_Post $post): void
{
    ccc_render_boolean('score_enabled', __('Enable Score Block', 'casino-compare-core'), (bool) ccc_get_meta_value($post->ID, 'score_enabled'));
    echo '<div data-ccc-condition-field="score_enabled" data-ccc-condition-value="1">';
    ccc_render_number('score_value', __('Score Value', 'casino-compare-core'), ccc_get_meta_value($post->ID, 'score_value'), ['step' => '0.1']);
    ccc_render_text('score_label', __('Score Label', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'score_label'));
    echo '</div>';
}

function ccc_render_subpage_table_box(WP_Post $post): void
{
    ccc_render_boolean('table_enabled', __('Enable Table Block', 'casino-compare-core'), (bool) ccc_get_meta_value($post->ID, 'table_enabled'));
    echo '<div data-ccc-condition-field="table_enabled" data-ccc-condition-value="1">';
    ccc_render_repeater('table_headers', __('Table Headers', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'table_headers', []), ccc_get_subpage_field_map()['table_headers']['subfields']);
    ccc_render_repeater('table_rows', __('Table Rows', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'table_rows', []), ccc_get_subpage_field_map()['table_rows']['subfields']);
    echo '</div>';
}

function ccc_render_subpage_seo_box(WP_Post $post): void
{
    ccc_render_repeater('faq', __('FAQ', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'faq', []), ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]);
    ccc_render_text('seo_title', __('SEO Title', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'seo_title'));
    ccc_render_textarea('meta_description', __('Meta Description', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'meta_description'), ['rows' => 3]);
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
add_action('save_post', 'ccc_save_subpage_fields', 10, 2);

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
add_action('save_post', 'ccc_seed_phase_one_subpages', 20, 2);
