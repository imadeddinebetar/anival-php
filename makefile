PROJECT_NAME = anival-php
PROJECT_COMPOSE_FILE = docker/docker-compose.yml
ENV_FILE = src/.env

up:
	docker compose --env-file $(ENV_FILE) -f $(PROJECT_COMPOSE_FILE) -p $(PROJECT_NAME) up -d

down:
	docker compose --env-file $(ENV_FILE) -f $(PROJECT_COMPOSE_FILE) -p $(PROJECT_NAME) down

clean:
	docker compose --env-file $(ENV_FILE) -f $(PROJECT_COMPOSE_FILE) -p $(PROJECT_NAME) stop
	docker compose --env-file $(ENV_FILE) -f $(PROJECT_COMPOSE_FILE) -p $(PROJECT_NAME) rm -f -v
	docker system prune -f --volumes

build:
	docker compose --env-file $(ENV_FILE) -f $(PROJECT_COMPOSE_FILE) -p $(PROJECT_NAME) build

bash:
	docker compose --env-file $(ENV_FILE) -f $(PROJECT_COMPOSE_FILE) -p $(PROJECT_NAME) exec app sh

log: 
	docker compose --env-file $(ENV_FILE) -f $(PROJECT_COMPOSE_FILE) -p $(PROJECT_NAME) logs app

restart: down up

test:
	docker compose --env-file $(ENV_FILE) -f $(PROJECT_COMPOSE_FILE) -p $(PROJECT_NAME) --profile test run --rm --build test

test-filter:
	docker compose --env-file $(ENV_FILE) -f $(PROJECT_COMPOSE_FILE) -p $(PROJECT_NAME) --profile test run --rm --build test --filter $(filter)


# mysql local service

MYSQL_PROJECT_NAME = mysql
MYSQL_PROJECT_COMPOSE_FILE = docker/mysql/docker-compose.yml

mysql_up:
	docker compose -f $(MYSQL_PROJECT_COMPOSE_FILE) -p $(MYSQL_PROJECT_NAME) up -d

mysql_down:
	docker compose -f $(MYSQL_PROJECT_COMPOSE_FILE) -p $(MYSQL_PROJECT_NAME) down

mysql_clean:
	docker compose -f $(MYSQL_PROJECT_COMPOSE_FILE) -p $(MYSQL_PROJECT_NAME) stop
	docker compose -f $(MYSQL_PROJECT_COMPOSE_FILE) -p $(MYSQL_PROJECT_NAME) rm -f -v
	docker system prune -f --volumes

mysql_build:
	docker compose -f $(MYSQL_PROJECT_COMPOSE_FILE) -p $(MYSQL_PROJECT_NAME) build

mysql_bash:
	docker compose -f $(MYSQL_PROJECT_COMPOSE_FILE) -p $(MYSQL_PROJECT_NAME) exec mysql sh

mysql_log: 
	docker compose -f $(MYSQL_PROJECT_COMPOSE_FILE) -p $(MYSQL_PROJECT_NAME) logs mysql

mysql_restart: mysql_down mysql_up