.PHONY: help build up down shell artisan npm logs test clean

# Default target
help:
	@echo "Available commands:"
	@echo "  build     - Build the Docker image"
	@echo "  up        - Start the application"
	@echo "  down      - Stop the application"
	@echo "  shell     - Access the application shell"
	@echo "  artisan   - Run Artisan commands"
	@echo "  npm       - Run npm commands"
	@echo "  logs      - Show application logs"
	@echo "  test      - Run tests"
	@echo "  clean     - Clean up Docker resources"

# Build the Docker image
build:
	docker build -t coamifee-shop .

# Start the application
up:
	docker-compose up -d

# Start with hot reloading (development)
up-dev:
	docker-compose --profile dev up -d

# Stop the application
down:
	docker-compose down

# Access the application shell
shell:
	docker-compose exec app sh

# Run Artisan commands
artisan:
	@read -p "Enter Artisan command: " cmd; \
	docker-compose exec app php artisan $$cmd

# Run npm commands
npm:
	@read -p "Enter npm command: " cmd; \
	docker-compose exec app npm $$cmd

# Show logs
logs:
	docker-compose logs -f app

# Show logs for all services
logs-all:
	docker-compose logs -f

# Run tests
test:
	docker-compose exec app php artisan test

# Run linting
lint:
	docker-compose exec app vendor/bin/pint
	docker-compose exec app npm run lint

# Install dependencies
install:
	docker-compose exec app composer install
	docker-compose exec app npm install

# Migrate database
migrate:
	docker-compose exec app php artisan migrate

# Seed database
seed:
	docker-compose exec app php artisan db:seed

# Clear caches
clear:
	docker-compose exec app php artisan optimize:clear

# Optimize for production
optimize:
	docker-compose exec app php artisan optimize

# Clean up Docker resources
clean:
	docker-compose down -v
	docker system prune -f

# Build for production
build-prod:
	docker build --target production -t coamifee-shop:latest .