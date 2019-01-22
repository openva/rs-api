#!/bin/bash

# Get the includes directory from the main repo, if we don't have it.
if [ ! -d "htdocs/includes/" ]; then
    git clone https://github.com/openva/richmondsunlight.com.git
    mv richmondsunlight.com/htdocs/includes htdocs/includes/
    rm -Rf richmondsunlight.com/
fi

# Use the Docker settings file
cp deploy/settings-docker.inc.php htdocs/includes/settings.inc.php

docker-compose build && docker-compose up
