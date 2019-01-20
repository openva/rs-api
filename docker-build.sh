#!/bin/bash

git clone https://github.com/openva/richmondsunlight.com.git
mv richmondsunlight.com/htdocs/includes ./includes/
rm -Rf richmondsunlight.com/

docker-compose build && docker-compose up