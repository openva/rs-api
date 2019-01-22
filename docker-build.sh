#!/bin/bash

git clone https://github.com/openva/richmondsunlight.com.git
mv richmondsunlight.com/htdocs/includes htdocs/includes/
rm -Rf richmondsunlight.com/

# Use the Docker settings file
cp deploy/settings-docker.inc.php htdocs/includes/settings.inc.php

docker-compose build && docker-compose up
