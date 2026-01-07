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
 * Emit a JSON error response and exit.
 */
function api_json_error(int $status, string $message, ?string $details = null): void
{
    http_response_code($status);
    header('Content-type: application/json');

    $payload = [
        'error' => [
            'message' => $message,
        ],
    ];

    if ($details !== null) {
        $payload['error']['details'] = $details;
    }

    api_json_output($payload);
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

/**
 * json_encode with sane defaults, and an error fallback.
 */
function api_json_output(mixed $payload): void
{
    try {
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        http_response_code(500);
        echo '{"error":{"message":"JSON encoding error"}}';
    }

    exit();
}

/**
 * Safe stripslashes that tolerates nulls and non-string values.
 */
function api_stripslashes(mixed $value): mixed
{
    if ($value === null) {
        return null;
    }

    return is_string($value) ? stripslashes($value) : $value;
}
