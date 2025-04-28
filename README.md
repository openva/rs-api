# Richmond Sunlight API

Public API for accessing Virginia legislative data including bills, legislators, and votes.

## Purpose
This is the API for [Richmond Sunlight](https://www.richmondsunlight.com/), used both by third parties and by [the site itself](/openva/richmondsunlight.com/). Only the bill and legislator pages consume the API, although those two pages comprise the great majority of the site traffic.

## Documentation

Browse the API documentation at https://api.richmondsunlight.com/docs/

The API is documented using OpenAPI 3.0. You can:
- Try endpoints directly in your browser via [the documentation](https://api.richmondsunlight.com/docs/)
- View the raw OpenAPI specification in [openapi.yaml](openapi.yaml)

## History
This used to be part of the main repository, but was forked out on its own in late 2016. It was created in ~2009, and hasn’t much been touched since.

## Infrastructure
It lives on the same EC2 instance as [the front-end](/openva/richmondsunlight.com/), though it in a separate webroot, has its own TLS certificate, etc. There is no reason why it could not run on its own server, but that’s not necessary under the standard traffic load. Source updates are delivered via GitHub Pages -> AWS CodeDeploy. (Note that [the `includes/` directory is pulled from the `deploy` branch of `richmondsunlight.com` repository](https://github.com/openva/richmondsunlight.com/tree/deploy/htdocs/includes) on each build.)

## Example Usage

```bash
# Get all bills from 2024
curl https://api.richmondsunlight.com/1.1/bills/2024.json

# Get details for a specific legislator
curl https://api.richmondsunlight.com/1.1/legislator/rcdeeds.json
```

## Versioning

- v1.1: Current version with all endpoints
- v1.0: Legacy version with limited endpoints
