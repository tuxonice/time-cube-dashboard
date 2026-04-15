.PHONY: help up start stop cli phpcs phpcbf test-code migrate

help:
	@echo "Available commands:"
	@echo "  make up       - Build and start Docker containers"
	@echo "  make start    - Start existing containers"
	@echo "  make stop     - Stop containers"
	@echo "  make cli      - Open bash shell inside the app container"
	@echo "  make migrate  - Run pending database migrations"
	@echo "  make phpcs    - Run PHP_CodeSniffer (PSR-12)"
	@echo "  make phpcbf   - Auto-fix code style issues"
	@echo "  make test-code - Run code style check"

.DEFAULT_GOAL := help

up:
	docker compose up -d --build

start:
	docker compose start

stop:
	docker compose stop

cli:
	docker compose exec -u sail app bash

phpcs:
	docker compose exec app php vendor/bin/phpcs

phpcbf:
	docker compose exec app php vendor/bin/phpcbf

migrate:
	docker compose exec app php bin/migrations migrate --no-interaction

test-code: phpcs
	@echo "Code style check completed"
