<?php

declare(strict_types=1);

// Video listing JSON
// PURPOSE: Emits a list of videos, oldest first, optionally limited to a year, a date, a chamber,
// and a committee (with its subcommittees). The work is done by Video::list_for_api(), in the
// includes that are copied from richmondsunlight.com.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

$filters = [];

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}$/']
]);
if ($year === false) {
    api_json_error(400, 'Invalid year', 'Parameter year must be a 4-digit year, if provided.');
}
if ($year !== null) {
    $filters['year'] = (int) $year;
}

$date = filter_input(INPUT_GET, 'date', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}-\d{2}-\d{2}$/']
]);
if (
    $date === false
    || ($date !== null && !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)))
) {
    api_json_error(400, 'Invalid date', 'Parameter date must be a date in the form YYYY-MM-DD, if provided.');
}
if ($date !== null) {
    $filters['date'] = $date;
}

$chamber = filter_input(INPUT_GET, 'chamber', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^(house|senate)$/']
]);
if ($chamber === false) {
    api_json_error(400, 'Invalid chamber', 'Parameter chamber must be "house" or "senate", if provided.');
}
if ($chamber !== null) {
    $filters['chamber'] = $chamber;
}

$committee = filter_input(INPUT_GET, 'committee', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z0-9-]{1,32}$/']
]);
if ($committee === false) {
    api_json_error(400, 'Invalid committee', 'Parameter committee must be a committee\'s shortname, if provided.');
}
if ($committee !== null) {
    if ($chamber === null) {
        api_json_error(400, 'Missing chamber', 'Parameter committee requires parameter chamber.');
    }
    $filters['committee'] = $committee;
}

api_db();

$videos = Video::list_for_api($filters);

// Filters that match nothing get an empty list, but no videos at all is an error.
if (count($videos) === 0 && count($filters) === 0) {
    api_json_error(404, 'No Video Found', 'Richmond Sunlight has no record of any videos.');
}

api_json_success($videos);
