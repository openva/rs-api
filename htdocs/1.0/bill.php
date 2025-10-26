<?php

###
# Create Bill JSON
#
# PURPOSE
# Accepts a year and a bill number and spits out a JSON file providing the basic specs on that
# bill.
#
# NOTES
# This is not intended to be viewed. It just spits out an JSON file and that's that.
#
# TODO
# * Cache the output.
# * Add a listing of identical bills.
# * Add the full status history, with each date and status update as individual items.
#
###

# INCLUDES
# Include any files or libraries that are necessary for this specific page to function.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

# DECLARATIVE FUNCTIONS
# Run those functions that are necessary prior to loading this specific page.
$database = new Database();
$db = $database->connect_mysqli();

# LOCALIZE VARIABLES
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}$/']
]);
$bill = filter_input(INPUT_GET, 'bill', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[hsrbj]{1,3}\d{1,4}$/']
]);
if ($year === false || $bill === false) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}

$bill_safe = mysqli_real_escape_string($db, $bill);
$year_safe = (int) $year;

# Select the bill data from the database.
$sql = 'SELECT bills.id, bills.number, bills.current_chamber, bills.status, bills.date_introduced,
		bills.outcome, bills.catch_line AS title, bills.summary, bills.full_text AS text,
		representatives.shortname, representatives.name_formatted AS name
		FROM bills
		LEFT JOIN representatives
			ON bills.chief_patron_id=representatives.id
		LEFT JOIN districts
			ON representatives.district_id=districts.id
		LEFT JOIN sessions
			ON bills.session_id=sessions.id
		WHERE bills.number = "' . $bill_safe . '" AND sessions.year=' . $year_safe;
$result = mysqli_query($db, $sql);
if ($result === false || mysqli_num_rows($result) === 0) {
    json_error('Richmond Sunlight has no record of bill ' . strtoupper($bill) . ' in ' . $year_safe . '.');
    exit();
}
# The MYSQL_ASSOC variable indicates that we want just the associated array, not both associated
# and indexed arrays.
$bill = mysqli_fetch_array($result, MYSQLI_ASSOC);
$bill = array_map('stripslashes', $bill);

# Select tags from the database.
$sql = 'SELECT tag
		FROM tags
		WHERE bill_id=' . $bill['id'] . '
		ORDER BY tag ASC';
$result = mysqli_query($db, $sql);
if ($result !== false && mysqli_num_rows($result) > 0) {
    while ($tag = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $bill['tags'][] = $tag;
    }
}

# Remove the HTML and the newlines from the bill summary.
$bill['summary'] = strip_tags($bill['summary']);
$bill['summary'] = str_replace("\n", ' ', $bill['summary']);

# Remove the newlines from the bill text.
$bill['text'] = str_replace("\r", '', $bill['text']);

# Assign the patron data to a subelement.
$bill['patron']['name'] = $bill['name'];
$bill['patron']['id'] = $bill['shortname'];

# Eliminate the fields we no longer need.
unset($bill['name'], $bill['shortname'], $bill['party'], $bill['id']);

# Send the JSON.
echo json_encode($bill);
