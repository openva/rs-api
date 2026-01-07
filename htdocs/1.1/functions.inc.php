<?php

declare(strict_types=1);

/**
 * Returns a mysqli connection using the shared Database helper.
 */
function api_db(): mysqli
{
    $database = new Database();

    return $database->connect_mysqli();
}

/**
 * Emit a JSON success response and exit.
 */
function api_json_success(mixed $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-type: application/json');

    api_json_output($payload);
}

