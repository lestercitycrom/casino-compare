<?php

/**
 * Casino Compare — Migration runner.
 *
 * Аналог `php artisan migrate` для WordPress.
 * Запуск: php scripts/migrate.php
 *
 * Логика:
 *   1. Загружает WordPress.
 *   2. Читает scripts/migrations/*.php в алфавитном порядке.
 *   3. Сверяется с wp_options('ccc_migrations_log') — какие уже выполнены.
 *   4. Запускает только новые миграции, записывает результат.
 *   5. При ошибке останавливается, не помечает миграцию как выполненную.
 *
 * Каждый файл миграции должен:
 *   - При успехе: завершиться без exit() / без uncaught exception.
 *   - При ошибке: вызвать exit(1) или бросить исключение.
 */

declare(strict_types=1);

$_SERVER['HTTP_HOST']       = $_SERVER['HTTP_HOST'] ?? parse_url((string) (getenv('WP_HOME') ?: 'http://casino-compare.local'), PHP_URL_HOST);
$_SERVER['REQUEST_METHOD']  = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['REQUEST_URI']     = $_SERVER['REQUEST_URI'] ?? '/';

require_once dirname(__DIR__) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function migrate_log(string $level, string $msg): void
{
    $prefix = match ($level) {
        'pass'  => "\e[32m[PASS]\e[0m",
        'skip'  => "\e[33m[SKIP]\e[0m",
        'run'   => "\e[36m[RUN] \e[0m",
        'fail'  => "\e[31m[FAIL]\e[0m",
        'info'  => "\e[34m[INFO]\e[0m",
        default => "[    ]",
    };
    echo $prefix . ' ' . $msg . "\n";
}

// ─── Main ─────────────────────────────────────────────────────────────────────

$log_key    = 'ccc_migrations_log';
$ran        = (array) get_option($log_key, []);
$dir        = __DIR__ . '/migrations/';
$files      = glob($dir . '*.php');

if ($files === false || $files === []) {
    migrate_log('info', 'No migration files found in ' . $dir);
    exit(0);
}

sort($files);

$pending = 0;
$failed  = 0;

migrate_log('info', 'Casino Compare Migration Runner');
migrate_log('info', 'Migrations directory: ' . $dir);
migrate_log('info', sprintf('%d total / %d already ran', count($files), count($ran)));
echo "\n";

foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $ran, true)) {
        migrate_log('skip', $name);
        continue;
    }

    migrate_log('run', $name);
    echo str_repeat('─', 60) . "\n";

    $pending++;
    $error = null;

    set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) use (&$error): bool {
        $error = sprintf('%s in %s:%d', $errstr, $errfile, $errline);
        return true;
    });

    try {
        include $file;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }

    restore_error_handler();

    echo str_repeat('─', 60) . "\n";

    if ($error !== null) {
        migrate_log('fail', $name . ' — ' . $error);
        $failed++;
        echo "\n";
        echo "\e[31mMigration failed. Stopping.\e[0m\n";
        exit(1);
    }

    $ran[] = $name;
    update_option($log_key, $ran);
    migrate_log('pass', $name);
    echo "\n";
}

echo str_repeat('═', 60) . "\n";

if ($pending === 0) {
    migrate_log('info', 'Nothing to run — all migrations already applied.');
} else {
    migrate_log('info', sprintf('%d migration(s) applied successfully.', $pending));
}

exit(0);
