<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_casino_meta_boxes(): array
{
    return [
        'ccc_casino_brand' => [
            'title' => __('Brand', 'casino-compare-core'),
            'fields' => [
                ['key' => 'logo', 'label' => __('Logo', 'casino-compare-core'), 'type' => 'image', 'layout' => 'full'],
                ['key' => 'affiliate_link', 'label' => __('Affiliate Link', 'casino-compare-core'), 'type' => 'text', 'layout' => 'full'],
                ['key' => 'year_founded', 'label' => __('Year Founded', 'casino-compare-core'), 'type' => 'number', 'layout' => 'third'],
                ['key' => 'trustpilot_score', 'label' => __('Trustpilot Score', 'casino-compare-core'), 'type' => 'number', 'step' => '0.1', 'layout' => 'third'],
                ['key' => 'app_available', 'label' => __('App Available', 'casino-compare-core'), 'type' => 'boolean', 'layout' => 'third'],
                ['key' => 'last_updated', 'label' => __('Last Updated', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
                ['key' => 'author_name', 'label' => __('Author Name', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
            ],
        ],
        'ccc_casino_rating' => [
            'title' => __('Rating', 'casino-compare-core'),
            'fields' => [
                ['key' => 'overall_rating', 'label' => __('Overall Rating', 'casino-compare-core'), 'type' => 'number', 'step' => '0.1', 'layout' => 'third'],
                ['key' => 'rating_bonus', 'label' => __('Bonus Rating', 'casino-compare-core'), 'type' => 'number', 'step' => '0.1', 'layout' => 'third'],
                ['key' => 'rating_games', 'label' => __('Games Rating', 'casino-compare-core'), 'type' => 'number', 'step' => '0.1', 'layout' => 'third'],
                ['key' => 'rating_payments', 'label' => __('Payments Rating', 'casino-compare-core'), 'type' => 'number', 'step' => '0.1', 'layout' => 'third'],
                ['key' => 'rating_support', 'label' => __('Support Rating', 'casino-compare-core'), 'type' => 'number', 'step' => '0.1', 'layout' => 'third'],
                ['key' => 'rating_reliability', 'label' => __('Reliability Rating', 'casino-compare-core'), 'type' => 'number', 'step' => '0.1', 'layout' => 'third'],
            ],
        ],
        'ccc_casino_bonus' => [
            'title' => __('Bonus', 'casino-compare-core'),
            'fields' => [
                ['key' => 'welcome_bonus_text', 'label' => __('Welcome Bonus Text', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'welcome_bonus_amount', 'label' => __('Welcome Bonus Amount', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'wagering', 'label' => __('Wagering', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'min_deposit', 'label' => __('Minimum Deposit', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'no_deposit_bonus', 'label' => __('No Deposit Bonus', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'free_spins', 'label' => __('Free Spins', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'promo_code', 'label' => __('Promo Code', 'casino-compare-core'), 'type' => 'text'],
            ],
        ],
        'ccc_casino_technical' => [
            'title' => __('Technical', 'casino-compare-core'),
            'fields' => [
                ['key' => 'license', 'label' => __('License', 'casino-compare-core'), 'type' => 'text', 'layout' => 'third'],
                ['key' => 'license_number', 'label' => __('License Number', 'casino-compare-core'), 'type' => 'text', 'layout' => 'third'],
                ['key' => 'games_count', 'label' => __('Games Count', 'casino-compare-core'), 'type' => 'number', 'layout' => 'third'],
                ['key' => 'support_channels', 'label' => __('Support Channels', 'casino-compare-core'), 'type' => 'textarea', 'rows' => 3],
                ['key' => 'vip', 'label' => __('VIP Program', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
                ['key' => 'mobile_app', 'label' => __('Mobile App', 'casino-compare-core'), 'type' => 'text', 'layout' => 'half'],
                ['key' => 'withdrawal_time_min', 'label' => __('Withdrawal Time Min', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'withdrawal_time_max', 'label' => __('Withdrawal Time Max', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'providers', 'label' => __('Providers', 'casino-compare-core'), 'type' => 'repeater', 'layout' => 'full', 'subfields' => ['name' => __('Provider Name', 'casino-compare-core')]],
                ['key' => 'deposit_methods', 'label' => __('Deposit Methods', 'casino-compare-core'), 'type' => 'repeater', 'layout' => 'full', 'subfields' => ['name' => __('Method', 'casino-compare-core')]],
                ['key' => 'withdrawal_methods', 'label' => __('Withdrawal Methods', 'casino-compare-core'), 'type' => 'repeater', 'layout' => 'full', 'subfields' => ['name' => __('Method', 'casino-compare-core')]],
            ],
        ],
        'ccc_casino_content' => [
            'title' => __('Content', 'casino-compare-core'),
            'fields' => [
                ['key' => 'pros', 'label' => __('Pros', 'casino-compare-core'), 'type' => 'repeater', 'layout' => 'half', 'subfields' => ['text' => __('Pro', 'casino-compare-core')]],
                ['key' => 'cons', 'label' => __('Cons', 'casino-compare-core'), 'type' => 'repeater', 'layout' => 'half', 'subfields' => ['text' => __('Con', 'casino-compare-core')]],
                ['key' => 'intro_text', 'label' => __('Intro Text', 'casino-compare-core'), 'type' => 'textarea', 'rows' => 5],
                ['key' => 'summary_1_title', 'label' => __('Summary 1 Title', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'summary_1', 'label' => __('Summary 1', 'casino-compare-core'), 'type' => 'wysiwyg'],
                ['key' => 'summary_2_title', 'label' => __('Summary 2 Title', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'summary_2', 'label' => __('Summary 2', 'casino-compare-core'), 'type' => 'wysiwyg'],
                ['key' => 'summary_3_title', 'label' => __('Summary 3 Title', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'summary_3', 'label' => __('Summary 3', 'casino-compare-core'), 'type' => 'wysiwyg'],
                ['key' => 'summary_4_title', 'label' => __('Summary 4 Title', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'summary_4', 'label' => __('Summary 4', 'casino-compare-core'), 'type' => 'wysiwyg'],
                ['key' => 'summary_5_title', 'label' => __('Summary 5 Title', 'casino-compare-core'), 'type' => 'text'],
                ['key' => 'summary_5', 'label' => __('Summary 5', 'casino-compare-core'), 'type' => 'wysiwyg'],
                ['key' => 'final_verdict', 'label' => __('Final Verdict', 'casino-compare-core'), 'type' => 'wysiwyg'],
            ],
        ],
        'ccc_casino_relations_seo' => [
            'title' => __('Relations & SEO', 'casino-compare-core'),
            'fields' => [
                ['key' => 'alternative_casinos', 'label' => __('Alternative Casinos', 'casino-compare-core'), 'type' => 'relation', 'post_type' => 'casino', 'multiple' => true, 'max_items' => 5, 'layout' => 'half'],
                ['key' => 'money_page_links', 'label' => __('Money Pages', 'casino-compare-core'), 'type' => 'relation', 'post_type' => 'landing', 'multiple' => true, 'layout' => 'half'],
                ['key' => 'seo_title', 'label' => __('SEO Title', 'casino-compare-core'), 'type' => 'text', 'layout' => 'full'],
                ['key' => 'meta_description', 'label' => __('Meta Description', 'casino-compare-core'), 'type' => 'textarea', 'rows' => 3],
                ['key' => 'faq', 'label' => __('FAQ', 'casino-compare-core'), 'type' => 'repeater', 'subfields' => ['question' => __('Question', 'casino-compare-core'), 'answer' => __('Answer', 'casino-compare-core')]],
            ],
        ],
    ];
}

function ccc_register_casino_meta_boxes(): void
{
    foreach (ccc_get_casino_meta_boxes() as $box_id => $box) {
        add_meta_box(
            $box_id,
            $box['title'],
            'ccc_render_casino_meta_box',
            'casino',
            'normal',
            'default',
            ['box_id' => $box_id]
        );
    }
}
add_action('add_meta_boxes_casino', 'ccc_register_casino_meta_boxes');

function ccc_render_casino_meta_box(WP_Post $post, array $callback_args): void
{
    $box_id = (string) ($callback_args['args']['box_id'] ?? '');
    $definition = ccc_get_casino_meta_boxes()[$box_id] ?? null;

    if (!$definition) {
        return;
    }

    ccc_render_meta_box_nonce('ccc_save_casino_fields', 'ccc_casino_nonce');

    ccc_render_fields_collection($definition['fields'], $post->ID);
}

function ccc_save_casino_fields(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'casino') {
        return;
    }

    if (!isset($_POST['ccc_casino_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ccc_casino_nonce'])), 'ccc_save_casino_fields')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    foreach (ccc_get_casino_meta_boxes() as $box) {
        foreach ($box['fields'] as $field) {
            $key = $field['key'];
            $raw = $_POST[$key] ?? ($field['type'] === 'boolean' ? 0 : null);

            if ($raw === null) {
                delete_post_meta($post_id, $key);
                continue;
            }

            $type = $field['type'] === 'relation' ? 'relation' : $field['type'];
            update_post_meta($post_id, $key, ccc_sanitize_field($type, wp_unslash($raw), $field));
        }
    }
}
add_action('save_post_casino', 'ccc_save_casino_fields', 10, 2);
