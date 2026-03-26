<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_landing_types(): array
{
    return ['comparison' => 'comparison', 'hub' => 'hub', 'trust' => 'trust'];
}

function ccc_get_landing_field_map(): array
{
    return [
        'landing_type' => ['label' => __('Landing Type', 'casino-compare-core'), 'type' => 'select', 'layout' => 'half', 'options' => ccc_get_landing_types(), 'placeholder' => __('Select type', 'casino-compare-core')],
        'hero_title' => ['label' => __('Hero Title', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
        'intro_text' => ['label' => __('Intro Text', 'casino-compare-core'), 'type' => 'textarea'],
        'faq' => ['label' => __('FAQ', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]],
        'seo_title' => ['label' => __('SEO Title', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
        'meta_description' => ['label' => __('Meta Description', 'casino-compare-core'), 'type' => 'textarea'],
        'casinos_tested_count' => ['label' => __('Casinos Tested Count', 'casino-compare-core'), 'type' => 'number', 'layout' => 'third'],
        'casino_cards' => ['label' => __('Casino Cards', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => [
            'casino_id' => ['label' => __('Casino', 'casino-compare-core'), 'type' => 'relation', 'post_type' => 'casino'],
            'rank' => ['label' => __('Rank', 'casino-compare-core'), 'type' => 'number', 'step' => '1', 'layout' => 'third'],
            'short_review' => ['label' => __('Short Review', 'casino-compare-core'), 'type' => 'textarea', 'rows' => 3],
        ]],
        'methodology_content' => ['label' => __('Methodology Content', 'casino-compare-core'), 'type' => 'wysiwyg'],
        'bottom_content' => ['label' => __('Bottom Content', 'casino-compare-core'), 'type' => 'wysiwyg'],
        'internal_link_pills' => ['label' => __('Internal Link Pills', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]],
        'last_updated' => ['label' => __('Last Updated', 'casino-compare-core'), 'type' => 'text', 'layout' => 'third'],
        'author_name' => ['label' => __('Author Name', 'casino-compare-core'), 'type' => 'text', 'layout' => 'third'],
        'trust_last_updated' => ['label' => __('Trust Last Updated', 'casino-compare-core'), 'type' => 'text', 'layout' => 'third'],
        'trust_author_name' => ['label' => __('Trust Author Name', 'casino-compare-core'), 'type' => 'text', 'layout' => 'third'],
        'subcategory_cards' => ['label' => __('Subcategory Cards', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['title' => __('Title', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core'), 'description' => __('Description', 'casino-compare-core'), 'icon' => __('Icon', 'casino-compare-core')]],
        'top_casino_list' => ['label' => __('Top Casino List', 'casino-compare-core'), 'type' => 'relation', 'max_items' => 9, 'post_type' => 'casino', 'multiple' => true, 'layout' => 'full'],
        'educational_content' => ['label' => __('Educational Content', 'casino-compare-core'), 'type' => 'wysiwyg'],
        'comparison_table_title' => ['label' => __('Comparison Table Title', 'casino-compare-core'), 'type' => 'text', 'layout' => 'full'],
        'comparison_table_headers' => ['label' => __('Comparison Table Headers', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['label' => __('Header', 'casino-compare-core')]],
        'comparison_table_rows' => ['label' => __('Comparison Table Rows', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => [
            'col_1' => ['label' => __('Column 1', 'casino-compare-core'), 'type' => 'text'],
            'col_2' => ['label' => __('Column 2', 'casino-compare-core'), 'type' => 'text'],
            'col_3' => ['label' => __('Column 3', 'casino-compare-core'), 'type' => 'text'],
            'col_4' => ['label' => __('Column 4', 'casino-compare-core'), 'type' => 'text'],
            'col_5' => ['label' => __('Column 5', 'casino-compare-core'), 'type' => 'text'],
            'col_6' => ['label' => __('Column 6', 'casino-compare-core'), 'type' => 'text'],
        ]],
        'howto_title' => ['label' => __('How To Title', 'casino-compare-core'), 'type' => 'text', 'layout' => 'full'],
        'howto_content' => ['label' => __('How To Content', 'casino-compare-core'), 'type' => 'wysiwyg'],
        'cross_silo_links' => ['label' => __('Cross Silo Links', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]],
        'page_content' => ['label' => __('Page Content', 'casino-compare-core'), 'type' => 'wysiwyg'],
        'show_author' => ['label' => __('Show Author Block', 'casino-compare-core'), 'type' => 'boolean', 'layout' => 'third'],
    ];
}

function ccc_register_landing_meta_boxes(): void
{
    add_meta_box('ccc_landing_common', __('Landing Common', 'casino-compare-core'), 'ccc_render_landing_common_box', 'landing', 'normal', 'default');
    add_meta_box('ccc_landing_comparison', __('Comparison Fields', 'casino-compare-core'), 'ccc_render_landing_comparison_box', 'landing', 'normal', 'default');
    add_meta_box('ccc_landing_hub', __('Hub Fields', 'casino-compare-core'), 'ccc_render_landing_hub_box', 'landing', 'normal', 'default');
    add_meta_box('ccc_landing_trust', __('Trust Fields', 'casino-compare-core'), 'ccc_render_landing_trust_box', 'landing', 'normal', 'default');
}
add_action('add_meta_boxes_landing', 'ccc_register_landing_meta_boxes');

function ccc_render_landing_common_box(WP_Post $post): void
{
    ccc_render_meta_box_nonce('ccc_save_landing_fields', 'ccc_landing_nonce');
    $fields = ccc_get_landing_field_map();
    $fields['landing_type']['description'] = __('Nested landing URLs are controlled with Page Attributes -> Parent.', 'casino-compare-core');

    ccc_render_fields_collection(ccc_expand_field_map([
        'landing_type' => $fields['landing_type'],
        'hero_title' => $fields['hero_title'],
        'intro_text' => $fields['intro_text'],
        'faq' => $fields['faq'],
        'seo_title' => $fields['seo_title'],
        'meta_description' => $fields['meta_description'],
    ]), $post->ID);
}

function ccc_render_landing_comparison_box(WP_Post $post): void
{
    echo '<div data-ccc-condition-field="landing_type" data-ccc-condition-value="comparison">';
    ccc_render_fields_collection(ccc_expand_field_map([
        'casinos_tested_count' => ccc_get_landing_field_map()['casinos_tested_count'],
        'last_updated' => ccc_get_landing_field_map()['last_updated'],
        'author_name' => ccc_get_landing_field_map()['author_name'],
        'casino_cards' => ccc_get_landing_field_map()['casino_cards'],
        'methodology_content' => ccc_get_landing_field_map()['methodology_content'],
        'bottom_content' => ccc_get_landing_field_map()['bottom_content'],
        'internal_link_pills' => ccc_get_landing_field_map()['internal_link_pills'],
    ]), $post->ID);
    echo '</div>';
}

function ccc_render_landing_hub_box(WP_Post $post): void
{
    echo '<div data-ccc-condition-field="landing_type" data-ccc-condition-value="hub">';
    ccc_render_fields_collection(ccc_expand_field_map([
        'last_updated' => ccc_get_landing_field_map()['last_updated'],
        'subcategory_cards' => ccc_get_landing_field_map()['subcategory_cards'],
        'top_casino_list' => ccc_get_landing_field_map()['top_casino_list'],
        'educational_content' => ccc_get_landing_field_map()['educational_content'],
        'comparison_table_title' => ccc_get_landing_field_map()['comparison_table_title'],
        'comparison_table_headers' => ccc_get_landing_field_map()['comparison_table_headers'],
        'comparison_table_rows' => ccc_get_landing_field_map()['comparison_table_rows'],
        'howto_title' => ccc_get_landing_field_map()['howto_title'],
        'howto_content' => ccc_get_landing_field_map()['howto_content'],
        'cross_silo_links' => ccc_get_landing_field_map()['cross_silo_links'],
    ]), $post->ID);
    echo '</div>';
}

function ccc_render_landing_trust_box(WP_Post $post): void
{
    echo '<div data-ccc-condition-field="landing_type" data-ccc-condition-value="trust">';
    ccc_render_fields_collection(ccc_expand_field_map([
        'show_author' => ccc_get_landing_field_map()['show_author'],
        'trust_author_name' => ccc_get_landing_field_map()['trust_author_name'],
        'trust_last_updated' => ccc_get_landing_field_map()['trust_last_updated'],
        'page_content' => ccc_get_landing_field_map()['page_content'],
    ]), $post->ID);
    echo '</div>';
}

function ccc_save_landing_fields(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'landing') {
        return;
    }

    if (!isset($_POST['ccc_landing_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ccc_landing_nonce'])), 'ccc_save_landing_fields')) {
        return;
    }

    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (ccc_get_landing_field_map() as $key => $field) {
        $type = $field['type'];
        $raw = $_POST[$key] ?? ($type === 'boolean' ? 0 : null);
        if ($raw === null) {
            delete_post_meta($post_id, $key);
            continue;
        }
        update_post_meta($post_id, $key, ccc_sanitize_field($type, wp_unslash($raw), $field));
    }
}
add_action('save_post_landing', 'ccc_save_landing_fields', 10, 2);
