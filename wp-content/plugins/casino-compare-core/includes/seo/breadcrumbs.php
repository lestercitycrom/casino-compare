<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function ccc_get_breadcrumbs(?int $post_id = null): array
{
    $post_id = $post_id ?: get_queried_object_id();
    $crumbs = [[
        'label' => __('Home', 'casino-compare-core'),
        'url' => home_url('/'),
    ]];

    if ($post_id <= 0) {
        return $crumbs;
    }

    $post = get_post($post_id);

    if (!$post instanceof WP_Post) {
        return $crumbs;
    }

    if ($post->post_type === 'casino_subpage') {
        $parent_casino_id = (int) get_post_meta($post_id, 'parent_casino', true);

        if ($parent_casino_id > 0) {
            $crumbs[] = [
                'label' => get_the_title($parent_casino_id),
                'url' => get_permalink($parent_casino_id),
            ];
        }
    } elseif ($post->post_type === 'landing') {
        $ancestor_ids = array_reverse(get_post_ancestors($post_id));

        foreach ($ancestor_ids as $ancestor_id) {
            $crumbs[] = [
                'label' => get_the_title($ancestor_id),
                'url' => get_permalink($ancestor_id),
            ];
        }
    }

    $crumbs[] = [
        'label' => get_the_title($post_id),
        'url' => get_permalink($post_id),
    ];

    return array_values(array_filter($crumbs, static function (array $crumb): bool {
        return !empty($crumb['label']) && !empty($crumb['url']);
    }));
}
