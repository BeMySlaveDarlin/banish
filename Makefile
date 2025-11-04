export UID=$(shell id -u)
export GID=$(shell id -g)

include .env

ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

all: clear-all var-preps build up composer-install db-migrate cleanup-updates
restart: down clear-all all
build:
	@echo "Building containers"
	@docker compose --env-file .env build
up:
	@echo "Starting containers"
	@docker compose --env-file .env up -d --remove-orphans
down:
	@echo "Stopping containers"
	@docker compose --env-file .env down
composer-install:
	@echo "Running composer install"
	@docker exec -it ${APP_NAME}.service.app composer install
composer-update:
	@echo "Running composer update"
	@docker exec -it ${APP_NAME}.service.app composer update
var-preps:
	@echo "Settings on var dir"
	@sudo mkdir -p var/cache
	@sudo mkdir -p var/log
	@sudo mkdir -p var/log/supervisor
	@sudo cp -n docker/dummy/ssl/* var/ssl/
	@sudo mkdir -p vendor
	@sudo chmod -R 777 var vendor

db-migrate:
	@echo "Running database migrations"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:migration:migrate
db-migration-generate:
	@echo "Running database migration generate"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:migration:generate
db-migration-rollback:
	@echo "Running database migration rollback"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction doctrine:migrations:migrate prev
refresh-partitions:
	@echo "Running refresh partitions"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction app:refresh-game-history-partitions
cleanup-updates:
	@echo "Running cleanup updates"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction app:telegram:clear-updates

frontend-build:
	@echo "Building frontend"
	@docker compose --env-file .env build frontend
	@docker compose --env-file .env up -d frontend

clear-all: clear-cache clear-logs
clear-cache:
	@echo "Clearing all cache"
	@rm -rf var/cache/*
clear-logs:
	@echo "Clearing all logs"
	@rm -rf var/log/*.log
	@rm -rf var/log/supervisor/*.log
	@rm -rf var/log/nginx/*.log

phpstan:
	@echo "Running PHPStan static analysis"
	@docker exec -it -u www-data ${APP_NAME}.service.app composer phpstan

cs-check:
	@echo "Running PHP Code Sniffer"
	@docker exec -it -u www-data ${APP_NAME}.service.app composer cs-check

cs-fix:
	@echo "Fixing PHP Code Style"
	@docker exec -it -u www-data ${APP_NAME}.service.app composer cs-fix

quality: phpstan cs-check
	@echo "✓ Code quality checks passed"
