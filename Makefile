# Variables
DC=docker compose --file docker-compose.yml

.PHONY: help go go-hard up setup down sh test paratest test-coverage logs db-migrate db-seed db-rollback db-reset log clear phpstan

.DEFAULT_GOAL := help

help: ## Show this help
	@printf "\nUsage: make [target]\n\n"
	@printf "Targets:\n"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@printf "\n"

%:
	@:

go: ## Start containers, install deps, and run migrations
	make down
	make up
	sleep 2
	make setup
	make db-migrate

go-hard: ## Full reset: remove volume, rebuild, seed database
	make down
	docker volume rm -f guesthub-db-volume
	make up
	sleep 2
	make setup
	make db-migrate
	make db-seed

up: ## Start containers in detached mode with build
	$(DC) up -d --build

setup: ## Install composer dependencies
	$(DC) exec guesthub composer install

down: ## Stop and remove containers
	$(DC) down

sh: ## Open a bash shell in the app container
	$(DC) exec guesthub bash

test: ## Run tests with coverage
	docker exec -it guesthub php artisan test $(wordlist 2, $(words $(MAKECMDGOALS)), $(MAKECMDGOALS)) --coverage

paratest: ## Run tests in parallel (10 processes) with coverage
	$(DC) exec guesthub php artisan test --coverage --parallel --processes=10 $(wordlist 2, $(words $(MAKECMDGOALS)), $(MAKECMDGOALS))

test-coverage: ## Generate HTML coverage report
	$(DC) exec guesthub php artisan test --coverage-html=coverage $(wordlist 2, $(words $(MAKECMDGOALS)), $(MAKECMDGOALS))

db-migrate: ## Run database migrations
	$(DC) exec guesthub php artisan migrate

db-seed: ## Seed the database
	$(DC) exec guesthub php artisan db:seed

db-rollback: ## Rollback last database migration
	$(DC) exec guesthub php artisan migrate:rollback

db-reset: ## Rollback, migrate, and seed the database
	make db-rollback
	make db-migrate
	make db-seed

clear: ## Clear all Laravel caches
	$(DC) exec guesthub php artisan cache:clear
	$(DC) exec guesthub php artisan view:clear
	$(DC) exec guesthub php artisan route:clear
	$(DC) exec guesthub php artisan config:clear
	$(DC) exec guesthub php artisan optimize:clear

logs: ## Follow Docker container logs
	$(DC) logs -f -n 10

log: ## Follow Laravel application log
	$(DC) exec guesthub tail -f storage/logs/laravel.log -n 0

phpstan: ## Run PHPStan static analysis
	$(DC) exec guesthub ./vendor/bin/phpstan analyse --memory-limit=2G
