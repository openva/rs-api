#!/bin/bash -e

# Hit the API for a legislator, save the JSON to a temporary file.
curl -s -o /tmp/legislator.json http://api/1.1/legislator/rbbell.json

# Compare the API response to the known-good response.
cmp -s /tmp/legislator.json legislator.json > /dev/null

# If the response is different, show exactly how and error out.
if [ $? -eq 1 ]; then
    diff <(jq -S . temp.json) <(jq -S . legislator.json)
    rm /tmp/legislator.json
    exit 1
fi

# Delete our temporary file.
rm /tmp/legislator.json
