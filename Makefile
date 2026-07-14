.PHONY: help execute start up down destroy build shell composer-install phpcs phpcbf clean-redis tests status

help: ## Shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

clean-redis: ## Clears the Redis database and the dummy storage log
	docker compose up -d redis
	docker compose exec redis redis-cli FLUSHALL
	rm -f app/var/saved_events.jsonl

start: destroy build composer-install execute ## Destroys env, builds, installs dependencies, and runs the loader

execute: clean-redis ## Runs the event loader via Symfony Messenger
	docker compose run --rm php php bin/console app:load-events -v
	docker compose run --rm php php bin/console messenger:consume async -vv

build: ## Builds Docker images
	docker compose build

up: ## Starts the containers in the background
	docker compose up -d

down: ## Stops and removes the containers
	docker compose down

destroy: ## Stops and removes containers, networks, and volumes (starts from 0)
	docker compose down -v --remove-orphans

shell: ## Opens an interactive shell in the PHP container
	docker compose run --rm php sh

composer-install: ## Installs project dependencies
	docker compose run --rm php composer install

phpcs: ## Checks code compliance with PSR-12 (phpcs)
	docker compose run --rm php vendor/bin/phpcs --standard=PSR12 src/

phpcbf: ## Automatically fixes PSR-12 formatting issues (phpcbf)
	docker compose run --rm php vendor/bin/phpcbf --standard=PSR12 src/

tests: ## Runs the PHPUnit tests
	docker compose run --rm php vendor/bin/phpunit tests/

status: ## Shows the status of fetched events
	docker compose run --rm php php bin/console app:status
