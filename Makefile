.PHONY: down up

up:
	@docker-compose up
down:
	@docker-compose down -v
