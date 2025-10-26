<?php

###
# Create Photosynthesis JSON
#
# PURPOSE
# Accepts a Photosynthesis portfolio hash, and responds with a listing of bills contained within
# that portfolio, along with any associated comments.
###

# INCLUDES
# Include any files or libraries that are necessary for this specific
# page to function.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

# DECLARATIVE FUNCTIONS
# Run those functions that are necessary prior to loading this specific
# page.
$database = new Database();
$db = $database->connect_mysqli();

# LOCALIZE VARIABLES
$hash = filter_input(INPUT_GET, 'hash', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z]{4,16}$/']
]);
if ($hash === false) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}
$hash_safe = mysqli_real_escape_string($db, $hash);

# Get this portfolio's basic data.
$sql = 'SELECT dashboard_portfolios.id, dashboard_portfolios.hash, dashboard_portfolios.name,
		dashboard_portfolios.notes, users.name AS user_name, dashboard_user_data.organization,
		users.url
		FROM dashboard_portfolios
		LEFT JOIN users
			ON dashboard_portfolios.user_id = users.id
		LEFT JOIN dashboard_user_data
			ON users.id = dashboard_user_data.user_id
		WHERE dashboard_portfolios.public = "y" AND dashboard_portfolios.hash="' . $hash_safe . '"';
$result = mysqli_query($db, $sql);

# If this portfolio doesn't exist or isn't visible.
if ($result === false || mysqli_num_rows($result) == 0) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    $message = array('error' =>
        array('message' => 'No Portfolio Found',
            'details' => 'Portfolio ' . $hash . ' does not exist.'));
    echo json_encode($message);
    exit;
}


$portfolio = mysqli_fetch_array($result, MYSQLI_ASSOC);
$portfolio = array_map('stripslashes', $portfolio);

# Make the user closer to anonymous.
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

# Select the bill data from the database.
$sql = 'SELECT bills.number, bills.catch_line, sessions.year, dashboard_bills.notes,
			
			(SELECT status
			FROM bills_status
			WHERE bill_id=bills.id
			ORDER BY date DESC, id DESC
			LIMIT 1) AS status,

			(SELECT date
			FROM bills_status
			WHERE bill_id=bills.id
			ORDER BY date DESC, id DESC
			LIMIT 1) AS date

		FROM dashboard_portfolios
		LEFT JOIN dashboard_user_data
			ON dashboard_portfolios.user_id=dashboard_user_data.user_id
		LEFT JOIN dashboard_bills
			ON dashboard_portfolios.id=dashboard_bills.portfolio_id
		LEFT JOIN bills
			ON dashboard_bills.bill_id=bills.id
		LEFT JOIN sessions
			ON bills.session_id=sessions.id
		WHERE dashboard_portfolios.hash="' . $hash_safe . '"
		AND bills.session_id=' . SESSION_ID . '
		ORDER BY bills.chamber DESC,
		SUBSTRING(bills.number FROM 1 FOR 2) ASC,
		CAST(LPAD(SUBSTRING(bills.number FROM 3), 4, "0") AS unsigned) ASC';
$result = mysqli_query($db, $sql);
if ($result === false || mysqli_num_rows($result) == 0) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    $message = array('error' =>
        array('message' => 'No Bills Found',
            'details' => 'No bills were found in portfolio ' . $hash . '.'));
    echo json_encode($message);
    exit;
}

# Build up a list of all bills.
$portfolio['bills'] = array();
while ($bill = mysqli_fetch_assoc($result)) {
    $bill['url'] = 'https://www.richmondsunlight.com/bill/' . $bill['year'] . '/' . $bill['number'] . '/';
    $bill['number'] = strtoupper($bill['number']);
    $portfolio['bills'][] = array_map('stripslashes', $bill);
}

# Send the JSON.
echo json_encode($portfolio);
