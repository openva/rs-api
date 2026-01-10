#!/bin/bash
set -e

# Save the current directory, to return to at the end
CWD=$(pwd)

# Change to the directory that this script is in
cd "$(dirname "$0")" || exit

# Path to the main richmondsunlight.com repository
RS_MAIN_REPO="${RS_MAIN_REPO:-../richmondsunlight.com}"

# Check if the main repo exists
if [ ! -d "$RS_MAIN_REPO" ]; then
    echo "Error: Cannot find richmondsunlight.com repository at $RS_MAIN_REPO"
    echo "Please either:"
    echo "  1. Clone richmondsunlight.com alongside this repository, or"
    echo "  2. Set RS_MAIN_REPO to point to its location"
    exit 1
fi

# Concatenate the database dumps into a single file for MariaDB to load
echo "Building database.sql from richmondsunlight.com SQL files..."
cat "$RS_MAIN_REPO/deploy/mysql/structure.sql" \
    "$RS_MAIN_REPO/deploy/mysql/basic-contents.sql" \
    "$RS_MAIN_REPO/deploy/mysql/test-records.sql" \
    "$RS_MAIN_REPO/deploy/mysql/test-users.sql" \
    > deploy/database.sql

# Copy the includes directory from the main repo
echo "Copying includes from richmondsunlight.com..."
rm -rf htdocs/includes
cp -R "$RS_MAIN_REPO/htdocs/includes/" htdocs/includes/

# Copy the Docker settings file
echo "Setting up Docker configuration..."
cp deploy/settings-docker.inc.php htdocs/includes/settings.inc.php

# Check if required containers already exist (possibly from richmondsunlight.com)
DB_EXISTS=$(docker ps -a --format '{{.Names}}' | grep -c '^rs_db$' || true)
API_EXISTS=$(docker ps -a --format '{{.Names}}' | grep -c '^rs_api$' || true)
MEMCACHED_EXISTS=$(docker ps -a --format '{{.Names}}' | grep -c '^rs_memcached$' || true)

if [ "$DB_EXISTS" -gt 0 ] && [ "$API_EXISTS" -gt 0 ] && [ "$MEMCACHED_EXISTS" -gt 0 ]; then
    echo "Containers already exist (possibly from richmondsunlight.com), starting them..."
    docker start rs_db rs_memcached 2>/dev/null || true
    # Always recreate the API container to ensure correct bind mount for this repo
    echo "Recreating API container with this repo's htdocs..."
    docker rm -f rs_api 2>/dev/null || true
    docker compose up -d --build api
else
    if [ "$DB_EXISTS" -gt 0 ]; then
        echo "Reusing existing database container..."
        docker start rs_db 2>/dev/null || true
    else
        echo "Building and starting database container..."
        docker compose -f docker-compose.shared.yml up -d --build db
    fi

    if [ "$MEMCACHED_EXISTS" -gt 0 ]; then
        echo "Reusing existing memcached container..."
        docker start rs_memcached 2>/dev/null || true
    else
        echo "Building and starting memcached container..."
        docker compose -f docker-compose.shared.yml up -d --build memcached
    fi

    # Always recreate the API container to ensure correct bind mount for this repo
    echo "Building and starting API container..."
    docker rm -f rs_api 2>/dev/null || true
    docker compose up -d --build --no-deps api
fi

# Wait for MariaDB to be available
echo "Waiting for database to be ready..."
while ! nc -z localhost 3306; do
    sleep 1
done

# Give MariaDB a moment to finish initialization
sleep 3

# Run the API setup - inline commands that work whether container was built here or from richmondsunlight.com
API_ID=$(docker ps | grep rs_api | cut -d " " -f 1)
if [ -n "$API_ID" ]; then
    echo "Configuring API container..."
    docker exec "$API_ID" bash -c '
        cd /var/www/html
        # Set include path if not already present
        grep -q "include_path" .htaccess 2>/dev/null || echo "php_value include_path \".:/var/www/html/includes/\"" >> .htaccess
        # Enable error reporting for debugging
        grep -q "error_reporting" .htaccess 2>/dev/null || echo "php_value error_reporting 32767" >> .htaccess
        grep -q "display_errors" .htaccess 2>/dev/null || echo "php_flag display_errors On" >> .htaccess
    '
fi

# Return to the original directory
cd "$CWD" || exit

# Check if the API is running
API_URL="http://localhost:5001/1.1/legislators.json?year=2025"
if curl --output /dev/null --silent --head --fail "$API_URL"; then
    echo ""
    echo "API is up and running!"
    echo "  - API base URL: http://localhost:5001/"
    echo "  - Test endpoint: http://localhost:5001/1.1/legislators.json?year=2025"
    echo ""
    echo "To run tests:"
    echo "  API_BASE=http://localhost:5001/1.1 ./deploy/tests/run-tests.sh"
else
    echo ""
    echo "Warning: API may not be fully ready yet at http://localhost:5001/"
    echo "Check container logs with: docker compose logs api"
fi
