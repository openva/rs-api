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
# * Add a list of identical bills.
# * Add the full status history, with each date and status update as individual items.
#
###

# INCLUDES
# Include any files or libraries that are necessary for this specific page to function.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once 'functions.inc.php';

header('Content-type: application/json');

# LOCALIZE VARIABLES
$year = mysql_escape_string($_REQUEST['year']);
$bill = mysql_escape_string(strtolower($_REQUEST['bill']));

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
