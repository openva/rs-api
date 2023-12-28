# Richmond Sunlight API
The RESTful API for Richmond Sunlight.

[![Code Climate](https://codeclimate.com/github/openva/rs-api/badges/gpa.svg)](https://codeclimate.com/github/openva/rs-machine) [![Deploy Process](https://github.com/openva/rs-api/actions/workflows/deploy.yml/badge.svg)](https://github.com/openva/rs-api/actions/workflows/deploy.yml)

## Purpose
This is the API for [Richmond Sunlight](https://www.richmondsunlight.com/), used both by third parties and by [the site itself](/openva/richmondsunlight.com/). Only the bill and legislator pages consume the API, although those two pages comprise the great majority of the site traffic.

## History
This used to be part of the main repository, but was forked out on its own in late 2016. It was created in ~2009, and hasn’t much been touched since.

## Infrastructure
It lives on the same EC2 instance as [the front-end](/openva/richmondsunlight.com/), though it in a separate webroot, has its own TLS certificate, etc. There is no reason why it could not run on its own server, but that’s not necessary under the standard traffic load. Source updates are delivered via Travis CI -> AWS CodeDeploy. (Note that [the `includes/` directory is pulled from the `deploy` branch of `richmondsunlight.com` repository](https://github.com/openva/richmondsunlight.com/tree/deploy/htdocs/includes) on each build.)
