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
        'landing_type' => ['type' => 'text'],
        'hero_title' => ['type' => 'text'],
        'intro_text' => ['type' => 'textarea'],
        'faq' => ['type' => 'repeater', 'subfields' => ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]],
        'seo_title' => ['type' => 'text'],
        'meta_description' => ['type' => 'textarea'],
        'casinos_tested_count' => ['type' => 'number'],
        'casino_cards' => ['type' => 'repeater', 'subfields' => [
            'casino_id' => ['label' => __('Casino', 'casino-compare-core'), 'type' => 'relation', 'post_type' => 'casino'],
            'rank' => ['label' => __('Rank', 'casino-compare-core'), 'type' => 'number', 'step' => '1'],
            'short_review' => ['label' => __('Short Review', 'casino-compare-core'), 'type' => 'textarea', 'rows' => 3],
        ]],
        'methodology_content' => ['type' => 'wysiwyg'],
        'bottom_content' => ['type' => 'wysiwyg'],
        'internal_link_pills' => ['type' => 'repeater', 'subfields' => ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]],
        'last_updated' => ['type' => 'text'],
        'author_name' => ['type' => 'text'],
        'subcategory_cards' => ['type' => 'repeater', 'subfields' => ['title' => __('Title', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core'), 'description' => __('Description', 'casino-compare-core'), 'icon' => __('Icon', 'casino-compare-core')]],
        'top_casino_list' => ['type' => 'relation', 'max_items' => 9],
        'educational_content' => ['type' => 'wysiwyg'],
        'howto_content' => ['type' => 'wysiwyg'],
        'cross_silo_links' => ['type' => 'repeater', 'subfields' => ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]],
        'page_content' => ['type' => 'wysiwyg'],
        'show_author' => ['type' => 'boolean'],
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
    wp_nonce_field('ccc_save_landing_fields', 'ccc_landing_nonce');
    $landing_type = (string) ccc_get_meta_value($post->ID, 'landing_type');

    echo '<p><label for="landing_type"><strong>' . esc_html__('Landing Type', 'casino-compare-core') . '</strong></label><br><select class="widefat" id="landing_type" name="landing_type">';
    echo '<option value="">' . esc_html__('Select type', 'casino-compare-core') . '</option>';
    foreach (ccc_get_landing_types() as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($landing_type, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></p>';
    echo '<p class="description">' . esc_html__('Nested landing URLs are controlled with Page Attributes -> Parent.', 'casino-compare-core') . '</p>';

    ccc_render_text('hero_title', __('Hero Title', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'hero_title'));
    ccc_render_textarea('intro_text', __('Intro Text', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'intro_text'), ['rows' => 4]);
    ccc_render_repeater('faq', __('FAQ', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'faq', []), ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]);
    ccc_render_text('seo_title', __('SEO Title', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'seo_title'));
    ccc_render_textarea('meta_description', __('Meta Description', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'meta_description'), ['rows' => 3]);
}

function ccc_render_landing_comparison_box(WP_Post $post): void
{
    echo '<div data-ccc-condition-field="landing_type" data-ccc-condition-value="comparison">';
    ccc_render_number('casinos_tested_count', __('Casinos Tested Count', 'casino-compare-core'), ccc_get_meta_value($post->ID, 'casinos_tested_count'));
    ccc_render_repeater('casino_cards', __('Casino Cards', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'casino_cards', []), ccc_get_landing_field_map()['casino_cards']['subfields']);
    ccc_render_wysiwyg('methodology_content', __('Methodology Content', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'methodology_content'));
    ccc_render_wysiwyg('bottom_content', __('Bottom Content', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'bottom_content'));
    ccc_render_repeater('internal_link_pills', __('Internal Link Pills', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'internal_link_pills', []), ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]);
    ccc_render_text('last_updated', __('Last Updated', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'last_updated'));
    ccc_render_text('author_name', __('Author Name', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'author_name'));
    echo '</div>';
}

function ccc_render_landing_hub_box(WP_Post $post): void
{
    echo '<div data-ccc-condition-field="landing_type" data-ccc-condition-value="hub">';
    ccc_render_repeater('subcategory_cards', __('Subcategory Cards', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'subcategory_cards', []), ['title' => __('Title', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core'), 'description' => __('Description', 'casino-compare-core'), 'icon' => __('Icon', 'casino-compare-core')]);
    ccc_render_relation('top_casino_list', __('Top Casino List', 'casino-compare-core'), 'casino', ccc_get_meta_value($post->ID, 'top_casino_list', []), ['multiple' => true, 'max_items' => 9]);
    ccc_render_wysiwyg('educational_content', __('Educational Content', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'educational_content'));
    ccc_render_wysiwyg('howto_content', __('How To Content', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'howto_content'));
    ccc_render_repeater('cross_silo_links', __('Cross Silo Links', 'casino-compare-core'), (array) ccc_get_meta_value($post->ID, 'cross_silo_links', []), ['label' => __('Label', 'casino-compare-core'), 'url' => __('URL', 'casino-compare-core')]);
    echo '</div>';
}

function ccc_render_landing_trust_box(WP_Post $post): void
{
    echo '<div data-ccc-condition-field="landing_type" data-ccc-condition-value="trust">';
    ccc_render_wysiwyg('page_content', __('Page Content', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'page_content'));
    ccc_render_boolean('show_author', __('Show Author Block', 'casino-compare-core'), (bool) ccc_get_meta_value($post->ID, 'show_author'));
    ccc_render_text('author_name', __('Author Name', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'author_name'));
    ccc_render_text('last_updated', __('Last Updated', 'casino-compare-core'), (string) ccc_get_meta_value($post->ID, 'last_updated'));
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
add_action('save_post', 'ccc_save_landing_fields', 10, 2);
