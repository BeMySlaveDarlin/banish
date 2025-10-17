include .env

ROOT_DIR:=$(shell dirname $(realpath $(firstword $(MAKEFILE_LIST))))

all: clear-cache-all build up composer-install db-migrate var-preps cleanup-updates
restart: clear-all down up
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
	@sudo touch /var/supervisor.pid
	@sudo chmod -R 777 var/*

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
	@echo "Running refresh partitions"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction app:telegram:clear-updates

clear-cache:
	@echo "Clearing global cache"
	@docker exec -it -u www-data  ${APP_NAME}.service.app php bin/console --no-interaction cache:pool:clear cache.global_clearer
clear-all: clear-cache-all clear-logs-all
clear-cache-all:
	@echo "Clearing all cache"
	@rm -rf var/cache/*
clear-logs-all:
	@echo "Clearing all logs"
	@rm -rf var/log/*

# New Architecture Commands
debug-container:
	@echo "Checking service container compilation"
	@docker exec -it -u www-data ${APP_NAME}.service.app php bin/console debug:container --show-arguments

debug-routes:
	@echo "Checking routes"
	@docker exec -it -u www-data ${APP_NAME}.service.app php bin/console debug:router

debug-messenger:
	@echo "Checking messenger configuration"
	@docker exec -it -u www-data ${APP_NAME}.service.app php bin/console debug:messenger

test-new-arch:
	@echo "Testing new architecture compilation"
	@docker exec -it -u www-data ${APP_NAME}.service.app php bin/console cache:clear
	@docker exec -it -u www-data ${APP_NAME}.service.app php bin/console debug:container App\\Domain\\Telegram
	@docker exec -it -u www-data ${APP_NAME}.service.app php bin/console debug:container App\\Infrastructure\\Telegram
