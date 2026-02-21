#!/bin/bash

# Ensure common binary locations are in PATH (needed for non-interactive shells)
# Includes ~/.docker/bin for newer Docker Desktop installations on macOS
export PATH="$HOME/.docker/bin:/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:$PATH"

if [ "$(docker container ps |grep -c rs_api)" -eq "0" ]; then
    echo "The API is not running in Docker, so cannot run tests"
    exit 1
fi

# Change to the script's directory
SCRIPT_DIR="$(dirname "$0")"
cd "$SCRIPT_DIR" || exit

# Skip if tests directory doesn't exist (e.g., incomplete checkout)
if [ ! -d tests ]; then
    echo "API tests directory not found, skipping"
    exit 0
fi

cd tests || exit