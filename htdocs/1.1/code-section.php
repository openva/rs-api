<?php

declare(strict_types=1);

// Code section JSON
// PURPOSE: Accepts a section of code, and responds with a listing of bills that addressed that section.

// Includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

// Connect
$db = api_db();

// Localize variables
$section = filter_input(
    INPUT_GET,
    'section',
    FILTER_VALIDATE_REGEXP,
    ['options' => ['regexp' => '/^[.0-9a-z-]{3,20}$/']]
);
if ($section === false || $section === null) {
    api_json_error(400, 'Invalid section', 'Parameter section must be 3-20 characters, a-z, 0-9, dots, or dashes.');
}
$stmt = $db->prepare(
    'SELECT sessions.year, bills.number, bills.catch_line, bills.summary, bills.outcome,
        bills.chamber, representatives.name_formatted AS legislator
        FROM bills
        LEFT JOIN bills_section_numbers
            ON bills.id = bills_section_numbers.bill_id
        LEFT JOIN sessions
            ON bills.session_id = sessions.id
        LEFT JOIN representatives
            ON bills.chief_patron_id = representatives.id
        WHERE bills_section_numbers.section_number = ?
        ORDER BY sessions.year ASC,
        bills.chamber DESC,
        SUBSTRING(bills.number FROM 1 FOR 2) ASC,
        CAST(LPAD(SUBSTRING(bills.number FROM 3), 4, "0") AS unsigned) ASC'
);
if ($stmt === false) {
    api_json_error(500, 'Database error');
}

$stmt->bind_param('s', $section);
$result = $stmt->execute();
if ($result === false) {
    api_json_error(500, 'Database error');
}

$result = $stmt->get_result();
if ($result === false || $result->num_rows == 0) {
    api_json_error(404, 'No Bills Found', 'No bills were found that cite section ' . $section . '.');
}

$bills = [];
while ($bill = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $bill['url'] = 'https://www.richmondsunlight.com/bill/' . $bill['year'] . '/' . $bill['number'] . '/';
    $bill['number'] = strtolower($bill['number']);
    $bill['year'] = (string) $bill['year'];
    $bill['chamber'] = strtolower($bill['chamber']);
    $bills[] = array_map('api_stripslashes', $bill);
}

api_cache_control_for_session(null);
api_json_success($bills);
