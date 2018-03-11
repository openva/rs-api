#!/bin/bash
#==================================================================================
# Uses environment variables within Travis CI to populate includes/settings.inc.php
# prior to deployment. This allows secrets (e.g., API keys) to be stored in Travis,
# while the settings file is stored on GitHub.
#==================================================================================

# Define the list of environmental variables that we need to populate during deployment.
variables=(
	PDO_DSN
	PDO_SERVER
	PDO_USERNAME
	PDO_PASSWORD
	MYSQL_DATABASE
	MEMCACHED_SERVER
)

SETTINGS_FILE=htdocs/includes/settings.inc.php

# If the settings file doesn't exist, abort
if [ ! -e "$SETTINGS_FILE" ]; then
	echo Aborting because "$SETTINGS_FILE" does not exist at "$(pwd)"
	exit 1
fi

# Iterate over the variables and make sure that they're all populated.
for i in "${variables[@]}"
do
	if [ -z "${!i}" ]; then
		echo "Travis CI has no value set for $i -- aborting"
		exit 1
	fi
done

# Now iterate over again and perform the replacement.
cp htdocs/includes/settings-default.inc.php htdocs/includes/settings.inc.php
for i in "${variables[@]}"
do
	sed -i -e "s|define('$i', '')|define('$i', '${!i}')|g" htdocs/includes/settings.inc.php
done
