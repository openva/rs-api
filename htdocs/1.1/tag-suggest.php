<?php

declare(strict_types=1);

/**
 * Tag suggestions
 *
 * PURPOSE
 * Displays an autocomplete list of tags, based on the text entered so far.
 **/

// Includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');
header('X-Api-Endpoint: tag-suggest');

// DECLARATIVE FUNCTIONS
// Run those functions that are necessary prior to loading this specific page.
$db = api_db();

/*
 * Localize variables
 */
$fragment = filter_input(INPUT_GET, 'term', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z]{3,15}$/']
]);
if ($fragment === false || $fragment === null) {
    api_json_error(400, 'Invalid term', 'Parameter term must be 3-15 lowercase letters.');
}

$suggestions = [];
$stmt = $db->prepare(
    'SELECT tag FROM tags WHERE tag LIKE CONCAT(?, "%") ORDER BY tag ASC LIMIT 10'
);
if ($stmt === false) {
    api_json_error(500, 'Database error');
}
$stmt->bind_param('s', $fragment);
$result = $stmt->execute();
if ($result === false) {
    api_json_error(500, 'Database error');
}
$result = $stmt->get_result();
if ($result !== false) {
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['tag'];
    }
}

if (empty($suggestions)) {
    api_json_error(404, 'No tags found', 'No tags match fragment ' . $fragment . '.');
}

// Send the JSON.
api_cache_control_for_session(null);
api_json_success($suggestions);
