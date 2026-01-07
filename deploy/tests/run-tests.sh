#!/usr/bin/env bash

# Run all rs-api tests
# This script is called by richmondsunlight.com's test runner

set -euo pipefail

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "$DIR" || exit

ERRORED=false

# Validate OpenAPI YAML syntax
if ! ruby -ryaml -e 'YAML.load_file("../../htdocs/openapi.yaml")' >/dev/null 2>&1; then
    echo "OpenAPI YAML validation failed"
    exit 1
fi

# Run API endpoint tests
if ! ./api.sh; then
    ERRORED=true
fi

if [ "$ERRORED" = true ]; then
    echo "API tests failed"
    exit 1
fi

echo "All API tests passed"
