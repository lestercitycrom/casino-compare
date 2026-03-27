<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_register_compare_endpoint(): void
{
    register_rest_route('ccc/v1', '/compare', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ccc_handle_compare_endpoint',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'ccc_register_compare_endpoint');

function ccc_comparison_fields(): array
{
    return [
        'overall_rating'      => 'Note globale',
        'welcome_bonus_text'  => 'Bonus de bienvenue',
        'wagering'            => 'Condition de mise',
        'withdrawal_time_min' => 'Retrait min (h)',
        'withdrawal_time_max' => 'Retrait max (h)',
        'trustpilot_score'    => 'Score Trustpilot',
    ];
}

function ccc_handle_compare_endpoint(WP_REST_Request $request): WP_REST_Response
{
    $ids = array_slice(array_filter(array_map('absint', explode(',', (string) $request->get_param('ids')))), 0, 3);
    $fields = ccc_comparison_fields();
    $items = [];

    foreach ($ids as $casino_id) {
        if (get_post_type($casino_id) !== 'casino') {
            continue;
        }

        $item = [
            'id' => $casino_id,
            'title' => get_the_title($casino_id),
            'permalink' => get_permalink($casino_id),
        ];

        foreach (array_keys($fields) as $field_key) {
            $item[$field_key] = get_post_meta($casino_id, $field_key, true);
        }

        $items[] = $item;
    }

    return new WP_REST_Response([
        'fields' => $fields,
        'items' => $items,
    ], 200);
}
