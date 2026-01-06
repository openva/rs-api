#!/bin/bash

# Stop and remove all rs-api Docker containers

cd "$(dirname "$0")" || exit

echo "Stopping Docker containers..."
docker compose down

echo "Containers stopped."
echo ""
echo "To also remove the database volume (all data), run:"
echo "  docker compose down -v"
