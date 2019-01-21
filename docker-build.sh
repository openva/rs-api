#!/bin/bash

git clone https://github.com/openva/richmondsunlight.com.git
mv richmondsunlight.com/htdocs/includes htdocs/includes/
rm -Rf richmondsunlight.com/

// set up settings.inc.php
// at LEAST rename the damn thing

docker-compose build && docker-compose up
