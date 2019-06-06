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
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/functions.inc.php';
require_once 'functions.inc.php';

/*
 * Localize variables
 */
$year = mysql_escape_string($_REQUEST['year']);
$lis_id = mysql_escape_string($_REQUEST['lis_id']);

/*
 * Send an HTTP header defining the content as JSON.
 */
header('Content-type: application/json');

/*
 * Send an HTTP header allowing CORS.
 */
header("Access-Control-Allow-Origin: *");

$vote_info = new Vote;
$vote_info->lis_id = $lis_id;
$vote_info->session_year = $year;
$vote = $vote_info->get_aggregate();
if ($vote === FALSE)
{
    header('HTTP/1.0 404 Not Found');
    exit();
}

/*
 * Get detailed information about who voted how.
 */
$vote['legislators'] = $vote_info->get_detailed();


# Send the JSON.
echo json_encode($vote);
