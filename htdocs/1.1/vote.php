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
require_once 'functions.inc.php';

header('Content-type: application/json');

/*
 * Localize variables
 */
$year = mysql_escape_string($_REQUEST['year']);
$lis_id = mysql_escape_string($_REQUEST['lis_id']);

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
