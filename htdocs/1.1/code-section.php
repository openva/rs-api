<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

// Connect
$db = api_db();

// Localize variables

$section = filter_input(INPUT_GET, 'section', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[.0-9a-z-]{3,20}$/']
]);
if ($section === false) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}
$section_safe = mysqli_real_escape_string($db, $section);

$sql = 'SELECT sessions.year, bills.number, bills.catch_line, bills.summary, bills.outcome,
		representatives.name_formatted AS legislator
		FROM bills
		LEFT JOIN bills_section_numbers
			ON bills.id = bills_section_numbers.bill_id
		LEFT JOIN sessions
			ON bills.session_id = sessions.id
		LEFT JOIN representatives
			ON bills.chief_patron_id = representatives.id
		WHERE bills_section_numbers.section_number =  "' . $section_safe . '"
		ORDER BY year ASC, bills.number ASC';
$result = mysqli_query($db, $sql);
if ($result === false || mysqli_num_rows($result) == 0) {
    // What error SHOULD this return?
}

$bills = array();
while ($bill = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $bill['url'] = 'https://www.richmondsunlight.com/bill/' . $bill['year'] . '/' . $bill['number'] . '/';
    $bill['number'] = strtolower($bill['number']);
    $bills[] = array_map('stripslashes', $bill);
}

api_cache_control_for_session(null);
api_json_success($bills);
