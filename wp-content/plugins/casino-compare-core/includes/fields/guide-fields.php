<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_guide_field_map(): array
{
    return [
        'category' => ['label' => __('Category', 'casino-compare-core'), 'type' => 'text', 'layout' => 'third'],
        'reading_time' => ['label' => __('Reading Time', 'casino-compare-core'), 'type' => 'number', 'layout' => 'third'],
        'last_updated' => ['label' => __('Last Updated', 'casino-compare-core'), 'type' => 'text', 'layout' => 'third'],
        'author_name' => ['label' => __('Author Name', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
        'intro_text' => ['label' => __('Intro Text', 'casino-compare-core'), 'type' => 'textarea'],
        'callout_enabled' => ['label' => __('Enable Callout', 'casino-compare-core'), 'type' => 'boolean', 'layout' => 'full'],
        'callout_title' => ['label' => __('Callout Title', 'casino-compare-core'), 'type' => 'text', 'layout' => 'full'],
        'callout_text' => ['label' => __('Callout Text', 'casino-compare-core'), 'type' => 'textarea'],
        'main_content' => ['label' => __('Main Content', 'casino-compare-core'), 'type' => 'wysiwyg'],
        'sidebar_top_title' => ['label' => __('Sidebar Top Title', 'casino-compare-core'), 'type' => 'text', 'layout' => 'full'],
        'sidebar_takeaway' => ['label' => __('Sidebar Takeaway', 'casino-compare-core'), 'type' => 'wysiwyg'],
        'sidebar_casino_list' => ['label' => __('Sidebar Casino List', 'casino-compare-core'), 'type' => 'relation', 'max_items' => 3, 'post_type' => 'casino', 'multiple' => true],
        'sidebar_comparison_link' => ['label' => __('Sidebar Comparison Link', 'casino-compare-core'), 'type' => 'text', 'layout' => 'full'],
        'sidebar_related_guides' => ['label' => __('Sidebar Related Guides', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]],
        'related_guides' => ['label' => __('Related Guides', 'casino-compare-core'), 'type' => 'relation', 'post_type' => 'guide', 'multiple' => true],
        'money_page_links' => ['label' => __('Money Page Links', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]],
        'faq' => ['label' => __('FAQ', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]],
        'seo_title' => ['label' => __('SEO Title', 'casino-compare-core'), 'type' => 'text'],
        'meta_description' => ['label' => __('Meta Description', 'casino-compare-core'), 'type' => 'textarea'],
    ];
}

function ccc_register_guide_meta_boxes(): void
{
    add_meta_box('ccc_guide_content', __('Guide Content', 'casino-compare-core'), 'ccc_render_guide_content_box', 'guide', 'normal', 'default');
    add_meta_box('ccc_guide_sidebar', __('Guide Sidebar & SEO', 'casino-compare-core'), 'ccc_render_guide_sidebar_box', 'guide', 'side', 'default');
}
add_action('add_meta_boxes_guide', 'ccc_register_guide_meta_boxes');

function ccc_render_guide_content_box(WP_Post $post): void
{
    ccc_render_meta_box_nonce('ccc_save_guide_fields', 'ccc_guide_nonce');
    ccc_render_fields_collection(ccc_expand_field_map([
        'category' => ccc_get_guide_field_map()['category'],
        'reading_time' => ccc_get_guide_field_map()['reading_time'],
        'last_updated' => ccc_get_guide_field_map()['last_updated'],
        'author_name' => ccc_get_guide_field_map()['author_name'],
        'intro_text' => ccc_get_guide_field_map()['intro_text'],
        'callout_enabled' => ccc_get_guide_field_map()['callout_enabled'],
        'callout_title' => ccc_get_guide_field_map()['callout_title'],
        'callout_text' => ccc_get_guide_field_map()['callout_text'],
        'main_content' => ccc_get_guide_field_map()['main_content'],
        'sidebar_top_title' => ccc_get_guide_field_map()['sidebar_top_title'],
        'sidebar_takeaway' => ccc_get_guide_field_map()['sidebar_takeaway'],
    ]), $post->ID);
}

function ccc_render_guide_sidebar_box(WP_Post $post): void
{
    ccc_render_fields_collection(ccc_expand_field_map([
        'sidebar_casino_list' => ccc_get_guide_field_map()['sidebar_casino_list'],
        'sidebar_comparison_link' => ccc_get_guide_field_map()['sidebar_comparison_link'],
        'sidebar_related_guides' => ccc_get_guide_field_map()['sidebar_related_guides'],
        'related_guides' => ccc_get_guide_field_map()['related_guides'],
        'money_page_links' => ccc_get_guide_field_map()['money_page_links'],
        'faq' => ccc_get_guide_field_map()['faq'],
        'seo_title' => ccc_get_guide_field_map()['seo_title'],
        'meta_description' => ccc_get_guide_field_map()['meta_description'],
    ]), $post->ID);
}

function ccc_save_guide_fields(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'guide') {
        return;
    }

    if (!isset($_POST['ccc_guide_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ccc_guide_nonce'])), 'ccc_save_guide_fields')) {
        return;
    }

    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (ccc_get_guide_field_map() as $key => $field) {
        $type = $field['type'];
        $raw = $_POST[$key] ?? null;
        if ($raw === null) {
            delete_post_meta($post_id, $key);
            continue;
        }
        update_post_meta($post_id, $key, ccc_sanitize_field($type, wp_unslash($raw), $field));
    }
}
add_action('save_post_guide', 'ccc_save_guide_fields', 10, 2);
