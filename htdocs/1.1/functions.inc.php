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

/**
 * Apply cache headers based on session age.
 */
function api_cache_control_for_session(?int $session_id): void
{
    if ($session_id === null) {
        header('Cache-Control: max-age=0, public');

        return;
    }

    if ($session_id !== SESSION_ID) {
        header('Cache-Control: max-age=' . (int) (60 * 60 * 24 * 30.5) . ', public');

        return;
    }

    header('Cache-Control: max-age=0, public');
}

