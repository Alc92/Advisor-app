DOCKER_COMPOSE = docker compose
APP = $(DOCKER_COMPOSE) exec app

.PHONY: build up down restart shell install update test test-unit test-integration test-acceptance smoke console cs-clear

build:
	$(DOCKER_COMPOSE) build

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

restart: down up

shell:
	$(APP) sh

install:
	$(APP) composer install

update:
	$(APP) composer update

console:
	$(APP) php bin/console

cs-clear:
	$(APP) php bin/console cache:clear

test:
	$(APP) composer test

test-unit:
	$(APP) composer test:unit

test-integration:
	$(APP) composer test:integration

test-acceptance:
	$(APP) composer test:acceptance

smoke:
	$(APP) composer smoke

.PHONY: cs-check cs-fix quality grumphp grumphp-git-hooks

cs-check:
	$(APP) composer cs:check

cs-fix:
	$(APP) composer cs:fix

quality:
	$(APP) composer quality

.PHONY: precommit install-git-hooks

precommit:
	$(APP) composer validate --no-check-publish
	$(APP) composer cs:check
	$(APP) composer test:unit

install-git-hooks:
	@printf '%s\n' '#!/bin/sh' '' 'set -e' '' 'echo "Running pre-commit checks inside Docker..."' '' 'docker compose exec -T app composer validate --no-check-publish' 'docker compose exec -T app composer cs:check' 'docker compose exec -T app composer test:unit' '' 'echo "Pre-commit checks passed."' > .git/hooks/pre-commit
	@chmod +x .git/hooks/pre-commit
	@echo "Installed Docker-based pre-commit hook."
