# LLM Instructions for rs-api

This document provides guidance for LLM-assisted development on the Richmond Sunlight API repository.

## Project Overview

**Purpose**: Public JSON API for accessing Virginia legislative data (bills, legislators, votes, video clips)

- **Production URL**: https://api.richmondsunlight.com/
- **Documentation**: https://api.richmondsunlight.com/docs/
- **License**: MIT
- **PHP Version**: 8.3+ (transitioning to 8.4)

This repository is part of the `richmondsunlight.com` collection:
- `rs-api` (this repo) - Public API
- `richmondsunlight.com` - Main website (source of `includes/` directory)
- `rs-machine` - Data ingestion and processing
- `rs-video-processor` - Video ingestion and processing

## Directory Structure

```
rs-api/
├── htdocs/                    # Web root
│   ├── 1.0/                   # Legacy API endpoints (do not add features here)
│   ├── 1.1/                   # Current API endpoints (target for new work)
│   ├── docs/                  # API documentation and Postman collection
│   ├── .htaccess              # Apache rewrite rules and caching
│   ├── 404.json               # Standard error response
│   └── openapi.yaml           # OpenAPI 3.0 specification
├── includes/                  # Pulled from richmondsunlight.com repo at build time
├── deploy/                    # Deployment scripts and Docker configuration
│   └── tests/                 # Integration tests
├── docs/                      # Development documentation
├── phpstan/                   # PHPStan configuration
├── rector.php                 # Code modernization configuration
└── appspec.yml                # AWS CodeDeploy configuration
```

## Coding Standards

### Style Guide

Follow **PSR-12** coding standards. The repository uses:
- `php-cs-fixer` via pre-commit hooks
- `rector` for code modernization
- `phpstan` for static analysis

### Comment Style

Use PSR-12 compliant comments:

```php
// Single-line comments use double slashes

/*
 * Multi-line comments use this format
 * for longer explanations.
 */

/**
 * DocBlocks for functions and classes.
 *
 * @param string $param Description
 * @return array Description
 */
```

### Variable and Function Naming

- Variables: `$snake_case`
- Functions: `snake_case()`
- Classes: `PascalCase`
- Constants: `UPPER_SNAKE_CASE`

## API Endpoint Structure

### File Organization

All endpoints follow this structure:

```php
<?php

/**
 * Endpoint description.
 */

// Includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

// Set response type
header('Content-type: application/json');

// Database connection
$database = new Database();
$db = $database->connect_mysqli();

// Input validation
$param = filter_input(INPUT_GET, 'param', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z0-9-]+$/']
]);

if ($param === false || $param === null) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}

// Database query (use prepared statements)
$stmt = $db->prepare('SELECT * FROM table WHERE column = ?');
$stmt->bind_param('s', $param);
$stmt->execute();
$result = $stmt->get_result();

// Handle empty results
if ($result->num_rows === 0) {
    $response = ['error' => ['message' => 'No records found']];
    echo json_encode($response);
    exit();
}

// Build response
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Output
echo json_encode($data);
```

### API Versioning

- **v1.0** (`htdocs/1.0/`): Legacy endpoints. Maintain but do not add new features.
- **v1.1** (`htdocs/1.1/`): Current version. All new features should target v1.1.

### URL Routing

Routing is handled via Apache `.htaccess` rewrites. URL patterns:

| Pattern | Example | Regex |
|---------|---------|-------|
| Bill numbers | `HB123`, `SB1234` | `[hsrbj]{1,3}\d{1,4}` |
| Code sections | `18.2-174` | `[.0-9a-z-]{3,20}` |
| Legislator shortnames | `rbbell` | `[a-z-]{3,30}` |
| Portfolio hashes | `abc123` | `[a-z0-9]{4,16}` |

## Database Patterns

### Connection

```php
$database = new Database();
$db = $database->connect_mysqli();
```

### Preferred: Prepared Statements

Always use prepared statements for new code and when modifying existing queries:

```php
// Good: Prepared statement
$stmt = $db->prepare('SELECT * FROM bills WHERE number = ? AND session_id = ?');
$stmt->bind_param('si', $bill_number, $session_id);
$stmt->execute();
$result = $stmt->get_result();

// Avoid: String concatenation (legacy pattern)
$sql = "SELECT * FROM bills WHERE number = '" . mysqli_real_escape_string($db, $bill_number) . "'";
```

### Result Handling

```php
// Check for errors and empty results
if ($result === false) {
    $response = ['error' => ['message' => 'Database error']];
    echo json_encode($response);
    exit();
}

if ($result->num_rows === 0) {
    $response = ['error' => ['message' => 'Not found']];
    echo json_encode($response);
    exit();
}

// Fetch data
while ($row = $result->fetch_assoc()) {
    // Process row
}
```

## Input Validation

Always validate input using `filter_input()`:

```php
// String with regex validation
$shortname = filter_input(INPUT_GET, 'shortname', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z-]{3,30}$/']
]);

// Integer validation
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 2000, 'max_range' => 2100]
]);

// Check for validation failure
if ($shortname === false || $shortname === null) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}
```

## Error Handling

### HTTP Status Codes

- `200`: Success
- `404`: Not found (use for invalid parameters and missing resources)

### Error Response Format

```php
// Standard 404 response
header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
exit();

// Custom error with details
$response = [
    'error' => [
        'message' => 'Bill not found',
        'details' => 'No bill matching HB9999 in the 2024 session'
    ]
];
echo json_encode($response);
exit();
```

### Security

- Never display PHP errors to users (`display_errors` is off)
- Validate all input with strict regex patterns
- Use prepared statements for all database queries
- Escape output where appropriate

## Includes Directory

The `includes/` directory is pulled from the `richmondsunlight.com` repository during CI/CD builds. It contains:

### Available Classes

- `Database` - Database connection management
- `Bill2` - Bill operations and text analysis
- `Video` - Video clip management
- `Legislator` - Legislator data operations
- `Statistics` - Activity statistics
- `Vote` - Vote information
- `Tags` - Tag management

### Available Functions

- `time_to_seconds()` - Convert time strings to seconds

### Important Note

When modifying code that relies on classes or functions from `includes/`, document the dependency. If changes to `includes/` are required, note that those changes must be made in the `richmondsunlight.com` repository.

## Caching

### HTTP Cache Headers

Set appropriate cache headers based on data freshness:

```php
// Data from previous sessions (unlikely to change): cache for 30 days
header('Cache-Control: max-age=2635200, public');

// Current session data (may change): no caching
header('Cache-Control: max-age=0, public');
```

The `SESSION_ID` constant indicates the current legislative session.

## Testing

### Test Structure

Tests are located in `deploy/tests/`:
- `api.sh` - API endpoint tests using `jq` for JSON validation
- `run-tests.sh` - Test runner script

The `richmondsunlight.com` repository's test suite calls `run-tests.sh` during integration testing.

### Running Tests

Tests require a running API instance. Set `API_BASE` to override the default:

```bash
API_BASE="http://localhost:8080/1.1" ./deploy/tests/run-tests.sh
```

### Test Pattern

Tests use the `check()` function to validate specific JSON fields:

```bash
check "/endpoint/param.json" ".field_name" '"expected_value"'
check "/endpoint/param.json" ".nested.field" '"value"'
check "/endpoint/param.json" ".array | length > 0" 'true'
check "/endpoint/param.json" "any(.items[]; .name == \"test\")" 'true'
```

### Adding Tests

When adding new endpoints or modifying existing ones, add tests to `deploy/tests/api.sh`:

```bash
# Test basic field values
check "/new-endpoint/test.json" ".required_field" '"expected"'

# Test nested objects
check "/new-endpoint/test.json" ".parent.child" '"value"'

# Test array contents
check "/new-endpoint/test.json" ".items | length" '5'

# Test conditional presence
check "/new-endpoint/test.json" "has(\"optional_field\")" 'true'
```

### Test Coverage Goals

Add tests for:
- All required response fields have expected values
- Nested objects and arrays are properly structured
- Edge cases (empty results, special characters)
- Error responses for invalid input

## OpenAPI Specification

The API is documented in `htdocs/openapi.yaml`. When adding or modifying endpoints:

1. Update the OpenAPI specification
2. Ensure response schemas match actual output
3. Document all parameters and their constraints

## Local Development with Docker

### Prerequisites

- Docker and Docker Compose
- The `richmondsunlight.com` repository cloned alongside this one (or set `RS_MAIN_REPO` to its location)

### Starting the Environment

```bash
./docker-run.sh
```

This will:
1. Copy SQL test data from `richmondsunlight.com`
2. Copy the `includes/` directory from `richmondsunlight.com`
3. Build and start the API, database, and Memcached containers
4. Configure the API for the Docker environment

### Stopping the Environment

```bash
./docker-stop.sh
```

To also remove the database volume (all data):
```bash
docker compose down -v
```

### Container Services

| Service | Port | Container | Purpose |
|---------|------|-----------|---------|
| API | 5001 | rs_api | PHP/Apache serving the API |
| Database | 3306 | rs_db | MariaDB with test data |
| Memcached | 11211 | rs_memcached | Session/cache storage |

### Running Tests Locally

With the Docker environment running:

```bash
API_BASE="http://localhost:5001/1.1" ./deploy/tests/run-tests.sh
```

### Troubleshooting

View container logs:
```bash
docker compose logs api
docker compose logs db
```

Rebuild containers after Dockerfile changes:
```bash
docker compose build --no-cache
```

Access the API container shell:
```bash
docker exec -it rs_api bash
```

## Production Deployment

- **Target**: AWS EC2 via CodeDeploy
- **CI/CD**: GitHub Actions (`.github/workflows/`)
- **Server**: Apache with mod_rewrite, HTTP/2, TLS via Certbot

### Environment Variables

Configuration uses these environment variables (set during deployment):
- `PDO_DSN`
- `PDO_SERVER`
- `PDO_USERNAME`
- `PDO_PASSWORD`
- `MYSQL_DATABASE`
- `API_URL`

## Common Patterns

### Building Response Arrays

```php
$bills = [];
while ($row = $result->fetch_assoc()) {
    $bill = [
        'number' => $row['number'],
        'title' => $row['title'],
        'summary' => strip_tags($row['summary'])
    ];
    $bills[] = $bill;
}
echo json_encode($bills);
```

### Handling Optional Fields

```php
// Only include field if it has a value
if (!empty($row['nickname'])) {
    $legislator['nickname'] = $row['nickname'];
}

// Handle MySQL zero dates
if ($row['date'] !== '0000-00-00') {
    $item['date'] = $row['date'];
}
```

### Removing Unnecessary Fields

```php
// Remove internal fields before output
unset($row['internal_id']);
unset($row['created_at']);
```

## Checklist for Changes

Before submitting changes:

- [ ] Follows PSR-12 coding standards
- [ ] Uses prepared statements for database queries
- [ ] Validates all input with `filter_input()`
- [ ] Returns appropriate HTTP status codes
- [ ] Updates `openapi.yaml` if endpoint behavior changed
- [ ] Adds or updates tests for modified functionality
- [ ] Documents any dependencies on `includes/` classes or functions
- [ ] New features target v1.1 only (not v1.0)
