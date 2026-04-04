.PHONY: up down build reset reset-full logs shell db-shell

## Start the lab (first run builds the image)
up:
	docker compose up -d --build

## Stop all containers (data is preserved)
down:
	docker compose down

## Rebuild images from scratch
build:
	docker compose build --no-cache

## Reset database to clean state (app stays up)
reset:
	./docker/reset-db.sh

## Full teardown: wipe everything and start fresh
reset-full:
	./docker/reset-db.sh --full

## Follow application logs
logs:
	docker compose logs -f app

## Open a shell inside the app container
shell:
	docker compose exec app bash

## Open a MySQL shell
db-shell:
	docker compose exec db mysql -u hackazon -phackazon hackazon
