<?php

declare(strict_types=1);

/**
 * Tag suggestions
 *
 * PURPOSE
 * Displays an autocomplete list of tags, based on the text entered so far.
 **/

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

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

}

if (empty($suggestions)) {
    api_json_error(404, 'No tags found', 'No tags match fragment ' . $fragment . '.');
}

# Send the JSON.
echo json_encode($suggestions);
api_cache_control_for_session(null);
api_json_success($suggestions);
