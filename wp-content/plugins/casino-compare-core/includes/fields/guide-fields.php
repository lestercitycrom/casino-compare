<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_guide_field_map(): array
{
    return [
        'category' => ['type' => 'text'],
        'reading_time' => ['type' => 'number'],
        'last_updated' => ['type' => 'text'],
        'author_name' => ['type' => 'text'],
        'intro_text' => ['type' => 'textarea'],
        'callout_text' => ['type' => 'textarea'],
        'main_content' => ['type' => 'wysiwyg'],
        'sidebar_takeaway' => ['type' => 'wysiwyg'],
        'sidebar_casino_list' => ['type' => 'relation', 'max_items' => 3],
        'related_guides' => ['type' => 'relation'],
        'money_page_links' => ['type' => 'repeater', 'subfields' => ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]],
        'faq' => ['type' => 'repeater', 'subfields' => ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]],
        'seo_title' => ['type' => 'text'],
        'meta_description' => ['type' => 'textarea'],
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
    wp_nonce_field('ccc_save_guide_fields', 'ccc_guide_nonce');
    ccc_render_text('category', __('Category', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'category'));
    ccc_render_number('reading_time', __('Reading Time', 'casino-compare-core'), ccc_get_meta_value($post->ID, 'reading_time'));
    ccc_render_text('last_updated', __('Last Updated', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'last_updated'));
    ccc_render_text('author_name', __('Author Name', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'author_name'));
    ccc_render_textarea('intro_text', __('Intro Text', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'intro_text'), ['rows' => 4]);
    ccc_render_textarea('callout_text', __('Callout Text', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'callout_text'), ['rows' => 4]);
    ccc_render_wysiwyg('main_content', __('Main Content', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'main_content'));
    ccc_render_wysiwyg('sidebar_takeaway', __('Sidebar Takeaway', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'sidebar_takeaway'));
}

function ccc_render_guide_sidebar_box(WP_Post $post): void
{
    ccc_render_relation('sidebar_casino_list', __('Sidebar Casino List', 'casino-compare-core'), 'casino', ccc_get_meta_value($post->ID, 'sidebar_casino_list', []), ['multiple' => true, 'max_items' => 3]);
    ccc_render_relation('related_guides', __('Related Guides', 'casino-compare-core'), 'guide', ccc_get_meta_value($post->ID, 'related_guides', []), ['multiple' => true]);
    ccc_render_repeater('money_page_links', __('Money Page Links', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'money_page_links', []), ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]);
    ccc_render_repeater('faq', __('FAQ', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'faq', []), ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]);
    ccc_render_text('seo_title', __('SEO Title', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'seo_title'));
    ccc_render_textarea('meta_description', __('Meta Description', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'meta_description'), ['rows' => 3]);
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
add_action('save_post', 'ccc_save_guide_fields', 10, 2);
