<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

$database = new Database();
$db = $database->connect_mysqli();

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
    $legislators = array();

    while ($legislator = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $legislator = array_map('stripslashes', $legislator);

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

        $legislators[] = $legislator;
    }
}

else {
    $legislators = 'Richmond Sunlight has no record of any legislators. Yes, we are also troubled by this.';
}

echo json_encode($legislators);
api_json_error(404, 'No Legislators Found', 'Richmond Sunlight has no record of any legislators.');
