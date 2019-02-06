#!/bin/bash

# Get the includes directory from the main repo, if we don't have it.
if [ ! -d "htdocs/includes/" ]; then

    # Download the includes from the main repo.
    curl -s -L -o richmondsunlight.zip https://github.com/openva/richmondsunlight.com/archive/deploy.zip
    if [ $? -ne 0 ]; then
        echo "Error: could not download main repository code. Quitting."
        exit 1;
    fi;
    
    unzip richmondsunlight.zip
    mv richmondsunlight.com-deploy/htdocs/includes htdocs/includes/
    
    # Remove artifacts.
    rm -Rf richmondsunlight.com-deploy/
    rm richmondsunlight.zip
fi

# Move over the settings file.
cp deploy/settings-docker.inc.php htdocs/includes/settings.inc.php

docker-compose build && docker-compose up && 
    CONTAINER_ID=$(docker-compose ps -q db) && \
    docker exec -i "$CONTAINER_ID" git clone https://github.com/openva/rs-machine.git /tmp/ && \
    docker exec -i "$CONTAINER_ID" mysql < /tmp/rs-machine/deploy/database.sql && \
    docker exec -i "$CONTAINER_ID" rm -Rf /tmp/rs-machine
