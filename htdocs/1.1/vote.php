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
require_once __DIR__ . '/functions.inc.php';

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

// Look up the session ID for the provided year.
$session_stmt = $db->prepare('SELECT id FROM sessions WHERE year = ? LIMIT 1');
if ($session_stmt === false) {
    api_json_error(500, 'Database error');
}
$session_stmt->bind_param('i', $year);
$session_result = $session_stmt->execute();
if ($session_result === false) {
    api_json_error(500, 'Database error');
}
$session_result = $session_stmt->get_result();
if ($session_result === false || $session_result->num_rows === 0) {
    api_json_error(404, 'Vote not found', 'No vote found for LIS ID ' . $lis_id . ' in ' . $year . '.');
}
$session_row = $session_result->fetch_assoc();
$session_id = (int) $session_row['id'];

// Ensure the vote exists before invoking legacy Vote helpers.
$vote_exists_stmt = $db->prepare('SELECT id FROM votes WHERE lis_id = ? AND session_id = ? LIMIT 1');
if ($vote_exists_stmt === false) {
    api_json_error(500, 'Database error');
}
$vote_exists_stmt->bind_param('si', $lis_id, $session_id);
$vote_exists_result = $vote_exists_stmt->execute();
if ($vote_exists_result === false) {
    api_json_error(500, 'Database error');
}
$vote_exists_result = $vote_exists_stmt->get_result();
if ($vote_exists_result === false || $vote_exists_result->num_rows === 0) {
    api_json_error(404, 'Vote not found', 'No vote found for LIS ID ' . $lis_id . ' in ' . $year . '.');
}
$vote_row = $vote_exists_result->fetch_assoc();
$vote_id = (int) $vote_row['id'];

// Aggregate vote info
$aggregate_stmt = $db->prepare(
    'SELECT chamber, outcome, tally FROM votes WHERE id = ? AND lis_id = ? AND session_id = ? LIMIT 1'
);
if ($aggregate_stmt === false) {
    api_json_error(500, 'Database error');
}
$aggregate_stmt->bind_param('isi', $vote_id, $lis_id, $session_id);
$aggregate_result = $aggregate_stmt->execute();
if ($aggregate_result === false) {
    api_json_error(500, 'Database error');
}
$aggregate_result = $aggregate_stmt->get_result();
if ($aggregate_result === false || $aggregate_result->num_rows === 0) {
    api_json_error(404, 'Vote not found', 'No vote found for LIS ID ' . $lis_id . ' in ' . $year . '.');
}
$vote = array_map('api_stripslashes', $aggregate_result->fetch_assoc());
$vote['chamber'] = strtolower($vote['chamber']);

// Get detailed information about who voted how.
$detail_stmt = $db->prepare(
    'SELECT representatives.name, representatives.shortname, representatives_votes.vote, representatives.party,
        representatives.chamber, representatives.address_district AS address,
        DATE_FORMAT(representatives.date_started, "%Y") AS started,
        districts.number AS district
     FROM representatives_votes
     LEFT JOIN representatives
        ON representatives_votes.representative_id = representatives.id
     LEFT JOIN districts
        ON representatives.district_id = districts.id
     WHERE representatives_votes.vote_id = ?
     ORDER BY vote ASC, name ASC'
);
if ($detail_stmt === false) {
    api_json_error(500, 'Database error');
}
$detail_stmt->bind_param('i', $vote_id);
$detail_result = $detail_stmt->execute();
if ($detail_result === false) {
    api_json_error(500, 'Database error');
}
$detail_result = $detail_stmt->get_result();
$legislators = [];
if ($detail_result !== false) {
    while ($legislator = $detail_result->fetch_assoc()) {
        $legislator = array_map('api_stripslashes', $legislator);
        $legislator['chamber'] = strtolower($legislator['chamber']);
        $legislator['party'] = strtoupper($legislator['party']);
        $legislator['district'] = (string) $legislator['district'];
        $legislators[] = $legislator;
    }
}
$vote['legislators'] = $legislators;


# Send the JSON.
api_cache_control_for_session(null);
api_json_success($vote);
