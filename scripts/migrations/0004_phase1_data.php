<?php

/**
 * Migration 0004 — Phase-1 full dataset.
 *
 * Создаёт/дополняет:
 *   - 55 landing-страниц (hub / comparison / trust)
 *   - 12 guide-статей
 *   - Полные данные казино (рейтинги, бонусы, FAQ, pros/cons)
 *   - Опубликованные сабпейджи bonus/retrait для ключевых казино
 *   - Внутренние ссылки между CPT-сущностями
 *
 * Безопасно запускать повторно: все операции используют [EXISTS]/[SKIP] логику.
 * Время выполнения: 1–3 минуты.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    $_SERVER['HTTP_HOST']      = $_SERVER['HTTP_HOST'] ?? parse_url((string) (getenv('WP_HOME') ?: 'http://casino-compare.local'), PHP_URL_HOST);
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_SERVER['REQUEST_URI']    = $_SERVER['REQUEST_URI'] ?? '/';
    require_once dirname(__DIR__, 2) . '/wp-load.php';
}

require_once dirname(__DIR__) . '/import-phase1-data.php';
