.PHONY: up start stop cli phpcs phpcbf test-code

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

test-code: phpcs
	@echo "Code style check completed"
