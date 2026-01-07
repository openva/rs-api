<?php

declare(strict_types=1);

// Photosynthesis portfolio JSON
// PURPOSE: Accepts a portfolio hash and emits the contained bills and comments.

// Includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

// Connect
$db = api_db();

// Localize variables
$hash = filter_input(INPUT_GET, 'hash', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z0-9]{4,16}$/']
]);
if ($hash === false || $hash === null) {
    api_json_error(400, 'Invalid portfolio hash', 'Hash must be 4-16 lowercase alphanumerics.');
}

$stmt = $db->prepare(
    'SELECT dashboard_portfolios.id, dashboard_portfolios.hash, dashboard_portfolios.name,
        dashboard_portfolios.notes, users.name AS user_name, dashboard_user_data.organization,
        users.url
        FROM dashboard_portfolios
        LEFT JOIN users
            ON dashboard_portfolios.user_id = users.id
        LEFT JOIN dashboard_user_data
            ON users.id = dashboard_user_data.user_id
        WHERE dashboard_portfolios.public = "y" AND dashboard_portfolios.hash = ?'
);
if ($stmt === false) {
    api_json_error(500, 'Database error');
}

$stmt->bind_param('s', $hash);
$result = $stmt->execute();
if ($result === false) {
    api_json_error(500, 'Database error');
}

$result = $stmt->get_result();

// If this portfolio doesn't exist or isn't visible.
if ($result === false || $result->num_rows == 0) {
    api_json_error(404, 'No Portfolio Found', 'Portfolio ' . $hash . ' does not exist.');
}


$portfolio = mysqli_fetch_array($result, MYSQLI_ASSOC);
$portfolio = array_map('api_stripslashes', $portfolio);

// Make the user closer to anonymous.
$tmp = explode(' ', $portfolio['user_name']);
if (count($tmp) > 1) {
    $portfolio['user_name'] = $tmp[0] . ' ' . $tmp[1][0] . '.';
} else {
    $portfolio['user_name'] = $tmp[0];
}
if (isset($portfolio['organization'])) {
    unset($portfolio['user_name']);
}
unset($portfolio['id'], $portfolio['hash'], $portfolio['name'], $portfolio['notes']);

$stmt = $db->prepare(
    'SELECT bills.number, bills.catch_line, sessions.year, dashboard_bills.notes,
                
                (SELECT status
                FROM bills_status
                WHERE bill_id = bills.id
                ORDER BY date DESC, id DESC
                LIMIT 1) AS status,

                (SELECT date
                FROM bills_status
                WHERE bill_id = bills.id
                ORDER BY date DESC, id DESC
                LIMIT 1) AS date

            FROM dashboard_portfolios
            LEFT JOIN dashboard_user_data
                ON dashboard_portfolios.user_id = dashboard_user_data.user_id
            LEFT JOIN dashboard_bills
                ON dashboard_portfolios.id = dashboard_bills.portfolio_id
            LEFT JOIN bills
                ON dashboard_bills.bill_id = bills.id
            LEFT JOIN sessions
                ON bills.session_id = sessions.id
            WHERE dashboard_portfolios.hash = ?
            AND bills.session_id = ?
            ORDER BY bills.chamber DESC,
            SUBSTRING(bills.number FROM 1 FOR 2) ASC,
            CAST(LPAD(SUBSTRING(bills.number FROM 3), 4, "0") AS unsigned) ASC'
);
if ($stmt === false) {
    api_json_error(500, 'Database error');
}

$session_id = SESSION_ID;
$stmt->bind_param('si', $hash, $session_id);
$result = $stmt->execute();
if ($result === false) {
    api_json_error(500, 'Database error');
}

$result = $stmt->get_result();
if ($result === false || mysqli_num_rows($result) == 0) {
    api_json_error(404, 'No Bills Found', 'No bills were found in portfolio ' . $hash . '.');
}

// Build up a list of all bills.
$portfolio['bills'] = [];
while ($bill = mysqli_fetch_assoc($result)) {
    $bill['url'] = 'https://www.richmondsunlight.com/bill/' . $bill['year'] . '/' . $bill['number'] . '/';
    $bill['number'] = strtolower($bill['number']);
    $bill['year'] = (string) $bill['year'];
    $portfolio['bills'][] = array_map('api_stripslashes', $bill);
}

// Send the JSON.
api_cache_control_for_session(SESSION_ID);
api_json_success($portfolio);
