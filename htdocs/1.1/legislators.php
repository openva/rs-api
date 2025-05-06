<?php

###
# Create Legislator JSON
#
# PURPOSE
# Accepts the shortname of a given legislator and spits out a JSON file providing
# the basic specs on that legislator.
#
# NOTES
# This is not intended to be viewed. It just spits out an JSON file and that's that.
#
###

/*
 * INCLUDES
 * Include any files or libraries that are necessary for this specific page to function.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once 'functions.inc.php';

header('Content-type: application/json');

/*
 * DECLARATIVE FUNCTIONS
 * Run those functions that are necessary prior to loading this specific page.
 */
$database = new Database();
$db = $database->connect_mysqli();

/*
 * LOCALIZE VARIABLES
 */
if (isset($_GET['year']) && strlen($_GET['year']) == 4 && is_numeric($_GET['year'])) {
    $year = $_GET['year'];
}

/*
 * Select basic legislator data from the database.
 */
$sql = 'SELECT representatives.id, representatives.shortname, representatives.name,
		representatives.name_formatted, representatives.place, representatives.chamber,
		representatives.party, representatives.date_started, representatives.date_ended,
		districts.number AS district
		FROM representatives
		LEFT JOIN districts
			ON representatives.district_id=districts.id ';
if (isset($year)) {
    $sql .= 'WHERE representatives.date_started <= ' . $year . '-01-01
		AND
			(representatives.date_ended >= ' . $year . '-01-01
			OR
			representatives.date_ended IS NULL';
}
$sql .= 'ORDER BY representatives.name ASC';

$result = mysqli_query($db, $sql);
if (mysqli_num_rows($result) > 0) {
    $legislators = array();

    while ($legislator = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $legislator = array_map('stripslashes', $legislator);

        /*
         * Eliminate any useless data.
         */
        if ($legislator['date_started'] == '0000-00-00') {
            unset($legislator['date_started']);
        }
        if ($legislator['date_ended'] == '0000-00-00') {
            unset($legislator['date_ended']);
        }

        /*
         * Generate the URL for this legislator on the site.
         */
        $legislator['site_url'] = 'https://www.richmondsunlight.com/legislator/'
            . $legislator['shortname'] . '/';

        /*
         * We publicly call the shortname the "ID," so swap them out.
         */
        $legislator['id'] = $legislator['shortname'];
        unset($legislator['shortname']);

        $legislators[] = $legislator;
    }
}

/*
 * If no legislators can be found.
 */
else {
    $legislators = 'Richmond Sunlight has no record of any legislators. Yes, we are also troubled by this.';
}

/*
 * Send the JSON.
 */
echo json_encode($legislators);
