<?php

declare(strict_types=1);

// Legislators listing JSON
// PURPOSE: Emits JSON with legislator basics; optional year filters incumbents.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

$db = api_db();

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}$/']
]);
if ($year === false) {
    api_json_error(400, 'Invalid year', 'Parameter year must be a 4-digit year if provided.');
}

$sql = 'SELECT representatives.id, representatives.shortname, representatives.name,
		representatives.name_formatted, representatives.place, representatives.chamber,
		representatives.party, representatives.date_started, representatives.date_ended,
		districts.number AS district
		FROM representatives
		LEFT JOIN districts
			ON representatives.district_id=districts.id ';
if ($year !== null) {
    $sql .= 'WHERE representatives.date_started <= "' . $year . '-01-01"
        AND (
            representatives.date_ended >= "' . $year . '-01-01"
            OR representatives.date_ended IS NULL
        ) ';
}
$sql .= 'ORDER BY representatives.name ASC';

$result = mysqli_query($db, $sql);
if ($result !== false && mysqli_num_rows($result) > 0) {
    $legislators = [];

    while ($legislator = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $legislator = array_map('api_stripslashes', $legislator);

        if ($legislator['date_started'] == '0000-00-00') {
            unset($legislator['date_started']);
        }
        if ($legislator['date_ended'] == '0000-00-00') {
            unset($legislator['date_ended']);
        }

        $legislator['site_url'] = 'https://www.richmondsunlight.com/legislator/'
            . $legislator['shortname'] . '/';

        $legislator['id'] = $legislator['shortname'];
        unset($legislator['shortname']);

        $legislator['district'] = (string) $legislator['district'];
        $legislator['chamber'] = strtolower($legislator['chamber']);
        $legislator['party'] = strtoupper($legislator['party']);

        $legislators[] = $legislator;
    }

    api_cache_control_for_session(null);
    api_json_success($legislators);
}

api_json_error(404, 'No Legislators Found', 'Richmond Sunlight has no record of any legislators.');
