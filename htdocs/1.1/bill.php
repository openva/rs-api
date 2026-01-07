<?php


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

$bill2 = new Bill2();
$bill2->id = $bill2->getid($year, $bill);
if ($bill2->id === false) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}

# Get basic data about this bill.
$bill = $bill2->info();

# Get a list of changes.
$bill2->text = $bill['full_text'];
$changes = $bill2->list_changes();
if ($changes !== false) {
    $bill['changes'] = $changes;
}

# Create a new video object.
$video = new Video();

# Get a list of videos for this bill.
$video->bill_id = $bill['id'];
$bill['video'] = $video->by_bill();

# If this is old data, we can cache it for up to a month.
if ($bill['session_id'] != SESSION_ID) {
    header('Cache-Control: max-age=' . (60 * 60 * 24 * 30.5) . ', public');
} else {
    header('Cache-Control: max-age=0, public');
}

# Send the JSON.
echo json_encode($bill);
