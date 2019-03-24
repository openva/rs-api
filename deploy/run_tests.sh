#!/bin/bash

if [ "$(docker container ps |grep -c rs_api)" -eq "0" ]; then
    echo "The API is not running in Docker, so cannot run tests"
    exit 1
fi

cd tests || exit

for f in *.sh; do
  bash "$f" -H
done

cd ..
