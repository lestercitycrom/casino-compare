<?php

/**
 * Migration 0003 — Casino import.
 *
 * Создаёт:
 *   - 15 казино (Casino CPT) с полным набором мета-полей
 *   - 8 сабпейджей для каждого казино в статусе draft
 *   - Таксономии: лицензии, фичи, методы оплаты, типы игр
 *
 * Безопасно запускать повторно: записи с таким же slug пропускаются.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    $_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? parse_url((string) (getenv('WP_HOME') ?: 'http://casino-compare.local'), PHP_URL_HOST);
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_SERVER['REQUEST_URI']    = $_SERVER['REQUEST_URI'] ?? '/';
    require_once dirname(__DIR__, 2) . '/wp-load.php';
}

require_once dirname(__DIR__) . '/import-casinos.php';
