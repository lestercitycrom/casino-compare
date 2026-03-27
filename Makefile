# Casino Compare — удобные команды
# Использование: make <команда>
#
# Требования: PHP в PATH, Node.js, npm
# Запускать из корня проекта (там где wp-load.php)

.PHONY: help setup migrate smoke seed test flush status

# ─── По умолчанию — показать справку ─────────────────────────────────────────

help:
	@echo ""
	@echo "  Casino Compare — доступные команды:"
	@echo ""
	@echo "  make setup      — полная начальная установка (migrate + seed QA)"
	@echo "  make migrate    — запустить все новые миграции"
	@echo "  make smoke      — базовая проверка (smoke-test.php, 60 assertions)"
	@echo "  make seed       — создать PW-тестовые данные для Playwright"
	@echo "  make test       — smoke + Playwright e2e (68 тестов)"
	@echo "  make flush      — сбросить WP кеш и transients"
	@echo "  make status     — статус применённых миграций"
	@echo ""

# ─── Начальная установка ──────────────────────────────────────────────────────

setup: migrate
	@echo ""
	@echo "  Установка завершена."
	@echo "  Запусти 'make seed' для создания QA-данных,"
	@echo "  или 'make test' для полной проверки."
	@echo ""

# ─── Миграции ─────────────────────────────────────────────────────────────────

migrate:
	php scripts/migrate.php

# ─── Статус миграций (что уже применено) ─────────────────────────────────────

status:
	@php -r " \
		\$$_SERVER['HTTP_HOST'] = 'casino-compare.local'; \
		\$$_SERVER['REQUEST_METHOD'] = 'GET'; \
		require 'wp-load.php'; \
		\$$ran = (array) get_option('ccc_migrations_log', []); \
		\$$files = glob('scripts/migrations/*.php'); \
		sort(\$$files); \
		echo \"\n  Migrations status:\n\n\"; \
		foreach (\$$files as \$$f) { \
			\$$name = basename(\$$f); \
			\$$done = in_array(\$$name, \$$ran) ? '\e[32m[DONE]\e[0m' : '\e[33m[PEND]\e[0m'; \
			echo \"  \$$done \$$name\n\"; \
		} \
		echo \"\n\"; \
	"

# ─── Smoke test ───────────────────────────────────────────────────────────────

smoke:
	php scripts/smoke-test.php

# ─── QA seed (PW-тестовые данные для Playwright) ─────────────────────────────

seed:
	npm run qa:seed

# ─── Полный тест: smoke + Playwright ─────────────────────────────────────────

test: smoke
	npm run qa:e2e

# ─── Сброс кеша ──────────────────────────────────────────────────────────────

flush:
	php scripts/cache-flush.php
