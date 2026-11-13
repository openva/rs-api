<?php

declare(strict_types=1);

// Days listing JSON
// PURPOSE: Emits every day in a year that has a page on Richmond Sunlight (a day with video,
// minutes, a meeting, or a decision on a bill), oldest first, with how much happened on each. The
// work is done by Day::list_for_api(), in the includes that are copied from richmondsunlight.com.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}$/']
]);
if ($year === null || $year === false) {
    api_json_error(400, 'Invalid year', 'Parameter year is required, and must be a 4-digit year.');
}

api_db();

api_json_success(Day::list_for_api((int) $year));
