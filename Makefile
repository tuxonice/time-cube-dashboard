.PHONY: up start stop cli

up:
	docker compose up -d --build

start:
	docker compose start

stop:
	docker compose stop

cli:
	docker compose exec app bash
