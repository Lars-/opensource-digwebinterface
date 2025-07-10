.PHONY: help build up down restart logs shell test clean

# Default target
help:
	@echo "Available commands:"
	@echo "  make build    - Build Docker images"
	@echo "  make up       - Start containers"
	@echo "  make down     - Stop containers"
	@echo "  make restart  - Restart containers"
	@echo "  make logs     - View logs"
	@echo "  make shell    - Access PHP container shell"
	@echo "  make test     - Test dig command"
	@echo "  make clean    - Remove containers and images"

# Build Docker images
build:
	docker compose build

# Start containers
up:
	docker compose up -d
	@echo "Application is running at http://localhost:8080"

# Stop containers
down:
	docker compose down

# Restart containers
restart: down up

# View logs
logs:
	docker compose logs -f

# Access PHP container shell
shell:
	docker compose exec php sh

# Test dig command
test:
	docker compose exec php dig google.com +short

# Clean up everything
clean:
	docker compose down -v
	docker image rm opensource-digwebinterface-php || true