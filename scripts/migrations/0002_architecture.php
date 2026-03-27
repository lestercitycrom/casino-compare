<?php

/**
 * Migration 0002 — Architecture import.
 *
 * Создаёт:
 *   - Страницу /comparer/ с шаблоном compare-page.php
 *   - Базовые таксономические термины (лицензии, фичи, методы оплаты, типы игр)
 *   - Базовые landing-страницы (hub, trust)
 *
 * Безопасно запускать повторно: import-architecture.php пропускает существующие записи.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    $_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? parse_url((string) (getenv('WP_HOME') ?: 'http://casino-compare.local'), PHP_URL_HOST);
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_SERVER['REQUEST_URI']    = $_SERVER['REQUEST_URI'] ?? '/';
    require_once dirname(__DIR__, 2) . '/wp-load.php';
}

require_once dirname(__DIR__) . '/import-architecture.php';
