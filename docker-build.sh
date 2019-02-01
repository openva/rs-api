#!/bin/bash

git clone https://github.com/openva/richmondsunlight.com.git
mv richmondsunlight.com/htdocs/includes htdocs/includes/
rm -Rf richmondsunlight.com/

if [ ! -f .env ]; then
	echo ".env not found -- can't configure settings.inc.php"
fi

source .env

docker-compose build && docker-compose up
