<?php

/**
 * Сбрасывает WordPress object cache и transients.
 * Запуск: php scripts/cache-flush.php
 */

declare(strict_types=1);

$_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? parse_url((string) (getenv('WP_HOME') ?: 'http://casino-compare.local'), PHP_URL_HOST);
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['REQUEST_URI']    = $_SERVER['REQUEST_URI'] ?? '/';

require_once dirname(__DIR__) . '/wp-load.php';

global $wpdb;

wp_cache_flush();

$deleted = (int) $wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'"
);

echo "[PASS] Object cache flushed\n";
echo "[PASS] {$deleted} transient(s) deleted\n";
