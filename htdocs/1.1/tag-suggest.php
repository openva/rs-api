<?php

/**
 * Tag suggestions
 *
 * PURPOSE
 * Displays an autocomplete list of tags, based on the text entered so far.
 **/

/*
 * Includes
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

# DECLARATIVE FUNCTIONS
# Run those functions that are necessary prior to loading this specific page.
$database = new Database();
$db = $database->connect_mysqli();

/*
 * Localize variables
 */
$fragment = filter_input(INPUT_GET, 'term', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z]{3,15}$/']
]);
if ($fragment === false) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}

$tags = new Tags();
$tags->fragment = $fragment;
$suggestions = $tags->get_suggestions();
if ($suggestions === false) {
    header('HTTP/1.1 404 Not Found');
    exit();
}

# Send the JSON.
echo json_encode($suggestions);
