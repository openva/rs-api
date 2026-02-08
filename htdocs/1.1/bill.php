<?php

declare(strict_types=1);

// Create Bill JSON
// PURPOSE: Accepts a year and bill number and emits JSON specs for that bill.
// NOTES: Not intended for browser viewing; JSON only.
// TODO: Add identical bills list and full status history.

// Includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

// Connect to the database.
$db = api_db();

// Localize variables.
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}$/']
]);
$bill = filter_input(INPUT_GET, 'bill', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[hsrbj]{1,3}\d{1,4}$/']
]);
if ($year === false || $bill === false || $year === null || $bill === null) {
    api_json_error(400, 'Invalid bill', 'Parameters year and bill must be provided.');
}

$bill2 = new Bill2();
$bill2->id = $bill2->getid($year, $bill);
if ($bill2->id === false) {
    api_json_error(404, 'Bill not found', 'No bill found for ' . $bill . ' in ' . $year . '.');
}

// Get basic data about this bill.
$bill = $bill2->info();
$bill['number'] = mb_strtolower((string) $bill['number']);
$bill['year'] = (string) $bill['year'];

// Get a list of changes.
$bill2->text = $bill['full_text'];
$changes = $bill2->list_changes();
if ($changes !== false) {
    $bill['changes'] = $changes;
}

// Get a narrative of the bill's status history.
$narrative = $bill2->statusNarrative();
if ($narrative !== false) {
    $bill['narrative'] = nl2p(htmlspecialchars($narrative, ENT_QUOTES, 'UTF-8'));
}

// Get all news articles about this bill.
$news = $bill2->news();
if ($news !== false) {
    $bill['news'] = $news;
}

// Create a new video object.
$video = new Video();

// Get a list of videos for this bill.
$video->bill_id = $bill['id'];
$bill['video'] = $video->by_bill();

api_cache_control_for_session((int) $bill['session_id']);

api_json_success($bill);
