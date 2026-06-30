COLOR_RESET = \033[0m
COLOR_INFO = \033[32m
COLOR_COMMENT = \033[33m
COLOR_HELP = \033[1;34m
COLOR_BOLD = \033[1m

CONTAINER_APP_NAME = php
CONTAINER_TEST_NAME = php-test

PROJECT_NAME = Challenge
PROJECT_DESCRIPTION = Challenge

SHELL := /bin/bash
CWD := $(shell cd -P -- '$(shell dirname -- "$0")' && pwd -P)
AWS_PROFILE := default
AWS_REPOSITORY := 946241444896.dkr.ecr.eu-west-1.amazonaws.com
UID := $(shell id -u)
GID := $(shell id -g)

.DEFAULT_GOAL := help

##@ Helpers

.PHONY: help
help: ## Display help
	@awk 'BEGIN {FS = ":.*##"; printf "${COLOR_HELP}${PROJECT_NAME}${COLOR_RESET}\n${PROJECT_DESCRIPTION}\n\nUsage:\n make ${COLOR_HELP}<target>${COLOR_RESET}\n"} /^[a-zA-Z_-]+:.*?##/ { printf " ${COLOR_HELP}%-30s${COLOR_RESET} %s\n", $$1, $$2 } /^##@/ { printf "\n${COLOR_BOLD}%s${COLOR_RESET}\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

.PHONY: build
build: ## Initialize this project
	docker compose build --build-arg UID=$(UID) --build-arg GID=$(GID) --no-cache

.PHONY: start
start: ## Start this project
	docker compose up -d --wait

.PHONY: down
down: ## Stop this project
	docker compose down --remove-orphans

.PHONY: bash
bash: ## Takes you inside the container
	docker compose exec $(CONTAINER_APP_NAME) bash

.PHONY: cache-clear
cache-clear: ## Clean application cache
	docker compose exec $(CONTAINER_APP_NAME) ./bin/console cache:clear

.PHONY: restart-worker
restart-worker: ## Restart worker
	docker compose exec $(CONTAINER_APP_NAME) curl -X POST http://localhost:2019/frankenphp/workers/restart

##@ Packages

.PHONY: composer-install
composer-install: ## Install Composer dependencies
	docker compose exec -e "COMPOSER_MEMORY_LIMIT=-1" $(CONTAINER_APP_NAME) composer install

.PHONY: composer-update
composer-update: ## Update Composer dependencies
	docker compose exec -e "COMPOSER_MEMORY_LIMIT=-1" $(CONTAINER_APP_NAME) composer update

.PHONY: composer-validate
composer-validate: ## Validate composer.json and composer.lock
	docker compose exec -e "COMPOSER_MEMORY_LIMIT=-1" $(CONTAINER_APP_NAME) composer validate --no-check-lock --strict composer.json

##@ Database

.PHONY: migrate
migrate: ## Run database migrations
	docker compose exec $(CONTAINER_APP_NAME) ./bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

.PHONY: migration
migration: ## Generate a migration from mapping changes (diff)
	docker compose exec $(CONTAINER_APP_NAME) ./bin/console doctrine:migrations:diff

##@ Code analysis

.PHONY: lint
lint: phpstan rector ecs ## Analyze code and show errors (PHPStan, Rector, ECS)

.PHONY: lint-fix
lint-fix: rector-fix ecs-fix ## Analyze code and fix errors (Rector, ECS)

.PHONY: phpstan
phpstan: ## Run PHPStan and show errors
	docker compose exec $(CONTAINER_APP_NAME) vendor/bin/phpstan analyse -c phpstan.dist.neon --memory-limit=-1

.PHONY: phpstan-baseline
phpstan-baseline: ## Generate PHPStan baseline
	docker compose exec $(CONTAINER_APP_NAME) vendor/bin/phpstan analyse -c phpstan.dist.neon --generate-baseline --memory-limit=-1

.PHONY: phpstan-cc
phpstan-cc: ## Clear PHPStan cache
	docker compose exec $(CONTAINER_APP_NAME) rm -rf var/cache/phpstan

.PHONY: ecs
ecs: ## Run Easy Coding Standard (ECS) and show errors
	docker compose exec $(CONTAINER_APP_NAME) vendor/bin/ecs check --memory-limit=-1

.PHONY: ecs-fix
ecs-fix: ## Run Easy Coding Standard (ECS) and fix errors
	docker compose exec $(CONTAINER_APP_NAME) vendor/bin/ecs check --fix --memory-limit=-1

.PHONY: ecs-list
ecs-list: ## List Easy Coding Standard (ECS) used rules
	docker compose exec $(CONTAINER_APP_NAME) vendor/bin/ecs list-checkers

.PHONY: rector
rector: ## Run Rector and show errors
	docker compose exec $(CONTAINER_APP_NAME) vendor/bin/rector process --dry-run

.PHONY: rector-fix
rector-fix: ## Run Rector and fix errors
	docker compose exec $(CONTAINER_APP_NAME) vendor/bin/rector process

##@ Test

TEST_FILTER :=

# Drop the compiled test container first so tests always reflect current code.
# PHPUnit runs with APP_DEBUG=0 (phpunit.xml.dist), which skips the kernel's
# freshness check — a stale container cache would otherwise survive code changes.
.PHONY: test-cache-clear
test-cache-clear:
	@docker compose exec -T $(CONTAINER_TEST_NAME) rm -rf var/cache/test

# Ensure the test database (app_test) exists and is migrated. Idempotent, so it
# is safe to run before every DB-touching suite (CI and a fresh local checkout).
.PHONY: test-prepare
test-prepare:
	@docker compose exec -T $(CONTAINER_TEST_NAME) php bin/console doctrine:database:create --if-not-exists
	@docker compose exec -T $(CONTAINER_TEST_NAME) php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

.PHONY: test
test: test-cache-clear test-prepare ## Execute all tests
	docker compose exec $(CONTAINER_TEST_NAME) vendor/bin/phpunit --testdox $(TEST_FILTER)

.PHONY: unit-test
unit-test: test-cache-clear ## Execute unit tests
	docker compose exec $(CONTAINER_TEST_NAME) vendor/bin/phpunit --testsuite=unit --testdox $(TEST_FILTER)

.PHONY: func-test
func-test: test-cache-clear test-prepare ## Execute functional tests
	docker compose exec $(CONTAINER_TEST_NAME) vendor/bin/phpunit --testsuite=functional --testdox $(TEST_FILTER)

.PHONY: infection
infection: test-cache-clear test-prepare ## Run Infection mutation testing
	docker compose exec $(CONTAINER_TEST_NAME) vendor/bin/infection --threads=max --no-progress

