#!/bin/bash

ln -s html htdocs

# Get the includes directory from the main repo, if we don't have it.
if [ ! -d "includes/" ]; then

    # Download the includes from the main repo.
    echo "Downloading dependencies..."
    curl -s -L -o richmondsunlight.zip https://github.com/openva/richmondsunlight.com/archive/deploy.zip
    if [ $? -ne 0 ]; then
        echo "Error: could not download main repository code. Quitting."
        exit 1;
    fi;
    
    unzip richmondsunlight.zip

    # Install Composer dependencies
    cd richmondsunlight.com-deploy/ && composer install && cd ..

    # Move over the dependencies
    mv richmondsunlight.com-deploy/htdocs/includes htdocs/includes/
    
    # Remove artifacts.
    rm -Rf richmondsunlight.com-deploy/
    rm richmondsunlight.zip
fi

# Move over the settings file.
cp deploy/settings-docker.inc.php htdocs/includes/settings.inc.php
