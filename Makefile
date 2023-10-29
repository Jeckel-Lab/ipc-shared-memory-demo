.PHONY: down up install

up:
	@docker-compose up

down:
	@docker-compose down -v

install:
	@docker-compose up -d rabbitmq
	@docker-compose exec rabbitmq rabbitmqctl import_definitions /scripts/definitions.json

load-messages:
	@docker-compose exec demo php pub/load-messages.php
