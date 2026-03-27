<?php

/**
 * Migration 0001 — System setup.
 *
 * - Активирует плагин casino-compare-core
 * - Активирует тему casino-compare-v2
 * - Устанавливает структуру URL /%postname%/
 * - Сбрасывает rewrite rules
 *
 * Безопасно запускать повторно: все операции идемпотентны.
 */

declare(strict_types=1);

// Standalone run guard
if (!defined('ABSPATH')) {
    $_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? parse_url((string) (getenv('WP_HOME') ?: 'http://casino-compare.local'), PHP_URL_HOST);
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_SERVER['REQUEST_URI']    = $_SERVER['REQUEST_URI'] ?? '/';
    require_once dirname(__DIR__, 2) . '/wp-load.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// ─── Плагин ───────────────────────────────────────────────────────────────────

$plugin = 'casino-compare-core/plugin.php';

if (!is_plugin_active($plugin)) {
    $result = activate_plugin($plugin);
    if ($result instanceof WP_Error) {
        throw new RuntimeException('Plugin activation failed: ' . $result->get_error_message());
    }
    echo "  [+] Plugin activated: {$plugin}\n";
} else {
    echo "  [=] Plugin already active: {$plugin}\n";
}

// ─── Тема ─────────────────────────────────────────────────────────────────────

$theme = 'casino-compare-v2';

if (get_stylesheet() !== $theme) {
    switch_theme($theme);
    echo "  [+] Theme switched to: {$theme}\n";
} else {
    echo "  [=] Theme already active: {$theme}\n";
}

// ─── Permalink structure ──────────────────────────────────────────────────────

$current_structure = get_option('permalink_structure');
$target_structure  = '/%postname%/';

if ($current_structure !== $target_structure) {
    update_option('permalink_structure', $target_structure);
    echo "  [+] Permalink structure set to: {$target_structure}\n";
} else {
    echo "  [=] Permalink structure already correct\n";
}

// Flush rewrite rules (always safe to run)
flush_rewrite_rules(true);
echo "  [+] Rewrite rules flushed\n";
