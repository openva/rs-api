#!/bin/bash

# Set permissions properly, since appspec.yml gets this wrong.
chown -R ubuntu:ubuntu /var/www/api.richmondsunlight.com/
chmod -R g+w /var/www/api.richmondsunlight.com/
