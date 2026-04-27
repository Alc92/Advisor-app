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
