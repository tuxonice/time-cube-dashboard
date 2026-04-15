.PHONY: up start stop cli phpcs phpcbf test-code migrate

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
