<?php

/**
 * Vote endpoint
 *
 * PURPOSE
 * Displays the outcome of a vote for a given year
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
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}$/']
]);
$lis_id = filter_input(INPUT_GET, 'lis_id', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{1,6}$/']
]);
if ($year === false || $lis_id === false) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}

$vote_info = new Vote();
$vote_info->lis_id = $lis_id;
$vote_info->session_year = $year;
$vote = $vote_info->get_aggregate();
if ($vote === false) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}

/*
 * Get detailed information about who voted how.
 */
$vote['legislators'] = $vote_info->get_detailed();


# Send the JSON.
echo json_encode($vote);
