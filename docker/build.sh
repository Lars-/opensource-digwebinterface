#!/bin/bash
# Build and run script for Docker deployment

echo "Building Docker images..."
docker compose build

echo "Starting services..."
docker compose up -d

echo "Waiting for services to be ready..."
sleep 5

echo "Testing dig command in container..."
docker compose exec php dig google.com +short

echo ""
echo "Services are running!"
echo "Access the application at: http://localhost:8080"
echo ""
echo "To stop services: docker compose down"
echo "To view logs: docker compose logs -f"