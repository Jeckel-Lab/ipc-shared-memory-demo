.PHONY: down up

up:
	@docker-compose up
down:
	@docker-compose down -v

install:
	@docker-compose exec rabbitmq rabbitmqctl import_definitions /scripts/definitions.json
