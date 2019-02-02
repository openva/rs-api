#!/bin/bash

# Get the includes directory from the main repo, if we don't have it.
if [ ! -d "htdocs/includes/" ]; then
    git clone https://github.com/openva/richmondsunlight.com.git
    mv richmondsunlight.com/htdocs/includes htdocs/includes/
    rm -Rf richmondsunlight.com/
fi

# Use the Docker environment variables to create the settings file.
if [ ! -f deploy/.docker.env ]; then
	echo "deploy/.docker.env not found -- can't configure settings.inc.php"
fi

source deploy/.docker.env
deploy/config_variables.sh

docker-compose build && docker-compose up && 
    CONTAINER_ID=$(docker-compose ps -q db) && \
    docker exec -i "$CONTAINER_ID" git clone https://github.com/openva/rs-machine.git /tmp/ && \
    docker exec -i "$CONTAINER_ID" mysql < /tmp/rs-machine/deploy/database.sql && \
    docker exec -i "$CONTAINER_ID" rm -Rf /tmp/rs-machine
