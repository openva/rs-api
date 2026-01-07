<?php

declare(strict_types=1);

// Legislator detail JSON
// PURPOSE: Accepts a legislator shortname and emits basic specs for that legislator.

// Includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

// Connect
$db = api_db();

$shortname = filter_input(INPUT_GET, 'shortname', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z-]{3,30}$/']
]);
// LOCALIZE VARIABLES
if ($shortname === false || $shortname === null) {
    api_json_error(404, 'Invalid legislator ID', 'Shortname must be 3-30 lowercase letters or dashes.');
}

// Create a new legislator object.
$leg = new Legislator();

// Get the ID for this shortname.
$leg_id = $leg->getid($shortname);
if ($leg_id === false) {
    api_json_error(404, 'Legislator not found', 'No legislator found with ID ' . $shortname . '.');
}

// Return the legislator's data as an array.
$legislator = $leg->info($leg_id);

$sql = 'SELECT bills.id, bills.number, bills.catch_line,
// Get this legislator's bills.
        DATE_FORMAT(bills.date_introduced, "%M %d, %Y") AS date_introduced,
        committees.name, sessions.year,
        (
            SELECT status
            FROM bills_status
            WHERE bill_id=bills.id
            ORDER BY date DESC, id DESC
            LIMIT 1
        ) AS status
        FROM bills
        LEFT JOIN sessions
            ON bills.session_id=sessions.id
        LEFT JOIN committees
            ON bills.last_committee_id = committees.id
        WHERE bills.chief_patron_id="' . $legislator['id'] . '"
        ORDER BY sessions.year DESC,
        SUBSTRING(bills.number FROM 1 FOR 2) ASC,
        CAST(LPAD(SUBSTRING(bills.number FROM 3), 4, "0") AS unsigned) ASC';
$result = mysqli_query($db, $sql);
if (mysqli_num_rows($result) > 0) {
    $legislator['bills'] = array();
    while ($bill = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $bill['url'] = 'https://www.richmondsunlight.com/bill/' . $bill['year'] . '/'
            . $bill['number'] . '/';
        $bill['number'] = strtoupper($bill['number']);
        $legislator['bills'][] = (array) $bill;
    }
}

// Create a new statistics object.
$stats = new Statistics();
$activity = $stats->legislator_activity($legislator['id']);
if ($activity !== false) {
    $legislator['activity'] = $activity;
}

// We publicly call the shortname the "ID," so swap them out.
$legislator['rs_id'] = $legislator['id'];
$legislator['id'] = $legislator['shortname'];

api_json_success($legislator);
