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
 * The absolute URL of a video screenshot, or null if there is none.
 *
 * Screenshots were stored as written at the time: a full URL, a protocol-relative one, an http://
 * one, or a path (with or without a /video/ prefix). Paths are on video.richmondsunlight.com. This
 * matches Video::archived_screenshot_url() and Video::normalize_screenshot_url(), in the includes
 * copied from richmondsunlight.com, which are private.
 */
function api_screenshot_url(?string $screenshot): ?string
{
    $screenshot = trim((string) $screenshot);
    if ($screenshot === '') {
        return null;
    }
    $screenshot = (string) preg_replace(['#^//#', '#^http://#'], 'https://', $screenshot);
    if (strpos($screenshot, 'https://') !== 0) {
        $path = ltrim((string) preg_replace('#^/video/#', '', $screenshot), '/');
        $screenshot = 'https://video.richmondsunlight.com/' . $path;
    }

    return $screenshot;
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
