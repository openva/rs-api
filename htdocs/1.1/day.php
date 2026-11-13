<?php

declare(strict_types=1);

// A day in the General Assembly, JSON
// PURPOSE: Accepts a date (YYYY-MM-DD), or "latest" for the most recent day, and emits everything
// that happened that day: each chamber's floor session (its video, minutes, and bill actions), every
// committee and subcommittee meeting (its video, the actions it took, and the rest of its docket),
// and, on the most recent day, what's scheduled next. The work is done by Day::record_for_api(), in
// the includes that are copied from richmondsunlight.com.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

$date = (string) filter_input(INPUT_GET, 'date');
if ($date !== 'latest' && !Day::is_date($date)) {
    api_json_error(404, 'No Day Found', 'Dates are in the form YYYY-MM-DD.');
}

api_db();

if ($date === 'latest') {
    $date = Day::latest();
    if ($date === null) {
        api_json_error(404, 'No Day Found', 'Richmond Sunlight has no record of any days.');
    }
}

$day = Day::record_for_api($date);
if ($day === null) {
    api_json_error(404, 'No Day Found', 'Nothing is recorded for that date, or it hasn\'t happened yet.');
}

api_json_success($day);
