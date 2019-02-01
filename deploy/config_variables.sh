#!/bin/bash
#==================================================================================
# Uses environment variables to populate includes/settings.inc.php. This allows
# secrets (e.g., API keys) to be stored in e.g. Travis, while the settings file is
# stored on GitHub.
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

# Copy the settings file template to the final path.
cp htdocs/includes/settings-default.inc.php "$SETTINGS_FILE"

# Iterate over the variables and make sure that they're all populated.
for i in "${variables[@]}"
do
	if [ -z "${!i}" ]; then
		echo "There is no value set for $i -- aborting"
		exit 1
	fi
done

# Now iterate over again and perform the replacement.
for i in "${variables[@]}"
do
	sed -i -e "s|define('$i', '')|define('$i', '${!i}')|g" htdocs/includes/settings.inc.php
done
