<?php
/**
 * Cleanup script: remove duplicate PW- casino records and flush rewrite rules.
 * Run from repo root: php scripts/cleanup-pw-duplicates.php
 */

define('ABSPATH_OVERRIDE', true);
$_SERVER['HTTP_HOST'] = 'casino-compare.local';
$_SERVER['REQUEST_URI'] = '/';

require_once __DIR__ . '/../wp-load.php';

echo "=== PW Duplicate Cleanup ===\n\n";

// ── 1. Find and delete duplicate PW posts (keep first by ID, delete rest) ────

$pw_types = ['casino', 'casino_subpage', 'landing', 'guide'];

foreach ($pw_types as $post_type) {
    $posts = get_posts([
        'post_type'      => $post_type,
        'post_status'    => 'any',
        'posts_per_page' => 200,
        's'              => 'PW ',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ]);

    // Filter only posts whose title starts with "PW "
    $pw_posts = array_filter($posts, fn($p) => str_starts_with($p->post_title, 'PW '));

    // Group by normalized title (trim)
    $by_title = [];
    foreach ($pw_posts as $post) {
        $by_title[$post->post_title][] = $post;
    }

    foreach ($by_title as $title => $group) {
        if (count($group) <= 1) continue;

        // Keep lowest ID (original), delete the rest
        $keep = $group[0];
        $dupes = array_slice($group, 1);

        foreach ($dupes as $dupe) {
            $result = wp_delete_post($dupe->ID, true); // true = force delete (bypass trash)
            if ($result) {
                echo "[{$post_type}] Deleted duplicate: \"{$dupe->post_title}\" (ID {$dupe->ID})\n";
            } else {
                echo "[{$post_type}] FAILED to delete: \"{$dupe->post_title}\" (ID {$dupe->ID})\n";
            }
        }

        echo "[{$post_type}] Kept original: \"{$keep->post_title}\" (ID {$keep->ID})\n";
    }
}

echo "\n";

// ── 2. Flush rewrite rules ────────────────────────────────────────────────────

flush_rewrite_rules(true);
echo "Rewrite rules flushed (hard flush).\n";

// ── 3. Report remaining PW posts ─────────────────────────────────────────────

echo "\n=== Remaining PW posts after cleanup ===\n\n";

foreach ($pw_types as $post_type) {
    $posts = get_posts([
        'post_type'      => $post_type,
        'post_status'    => 'any',
        'posts_per_page' => 50,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ]);

    $pw_posts = array_filter($posts, fn($p) => str_starts_with($p->post_title, 'PW '));

    foreach ($pw_posts as $p) {
        $parent = ($post_type === 'casino_subpage')
            ? ' [parent_casino=' . get_post_meta($p->ID, 'parent_casino', true) . ', type=' . get_post_meta($p->ID, 'subpage_type', true) . ']'
            : '';
        echo sprintf("  [%s] ID=%-5d status=%-10s \"%s\"%s\n",
            $post_type, $p->ID, $p->post_status, $p->post_title, $parent);
    }
}

echo "\nDone.\n";
