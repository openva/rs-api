<?php

declare(strict_types=1);

// Bills listing JSON
// PURPOSE: Accepts a year and emits JSON listing bills introduced that year.
// TODO: Cache the output.

// Includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

$database = new Database();
$db = $database->connect_mysqli();

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}$/']
]);
// Connect
// Localize variables
}

$sql = 'SELECT bills.number, bills.chamber, bills.date_introduced, bills.status, bills.outcome,
		bills.catch_line AS title, representatives.name_formatted AS patron,
		representatives.shortname AS patron_id
		FROM bills
		LEFT JOIN representatives
			ON bills.chief_patron_id=representatives.id
		LEFT JOIN sessions
			ON bills.session_id=sessions.id
		WHERE sessions.year=' . $year . '
		ORDER BY bills.chamber DESC,
		SUBSTRING(bills.number FROM 1 FOR 2) ASC,
		CAST(LPAD(SUBSTRING(bills.number FROM 3), 4, "0") AS unsigned) ASC';
$result = mysqli_query($db, $sql);
if (mysqli_num_rows($result) == 0) {
    // send this as a JSON-formatted error!
    die('Richmond Sunlight has no record of bills for ' . $year . '.');
}

$bills = array();

while ($bill = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $bill = array_map('stripslashes', $bill);

    $bill['patron'] = array(
        'name' => $bill['patron'],
        'id' => $bill['patron_id'],
    );

    unset($bill['patron'], $bill['patron_id']);

    $bills[] = $bill;
}

echo json_encode($bills);
// Send the JSON.
