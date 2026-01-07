<?php

declare(strict_types=1);

/**
 * Vote endpoint
 *
 * PURPOSE
 * Displays the outcome of a vote for a given year
 **/

// Includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

// Connect
$db = api_db();

// Localize variables
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}$/']
]);
$lis_id = filter_input(INPUT_GET, 'lis_id', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{1,6}$/']
]);
if ($year === false || $lis_id === false || $year === null || $lis_id === null) {
    api_json_error(400, 'Invalid vote', 'Parameters year and lis_id must be provided.');
}

if ($session_result === false || $session_result->num_rows === 0) {
    api_json_error(404, 'Vote not found', 'No vote found for LIS ID ' . $lis_id . ' in ' . $year . '.');
}
}

$vote_info = new Vote();
$vote_info->lis_id = $lis_id;
$vote_info->session_year = $year;
$vote = $vote_info->get_aggregate();
}

$vote['legislators'] = $vote_info->get_detailed();
// Get detailed information about who voted how.


# Send the JSON.
echo json_encode($vote);
