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

# INCLUDES
# Include any files or libraries that are necessary for this specific page to function.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once 'functions.inc.php';

header('Content-type: application/json');

# DECLARATIVE FUNCTIONS
# Run those functions that are necessary prior to loading this specific
# page.
$database = new Database();
$db = $database->connect_mysqli();

# LOCALIZE VARIABLES
$shortname = filter_input(INPUT_GET, 'shortname', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z-]{3,30}$/']
]);
if ($shortname === false) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}

# Create a new legislator object.
$leg = new Legislator();

# Get the ID for this shortname.
$leg_id = $leg->getid($shortname);
if ($leg_id === false) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    readfile($_SERVER['DOCUMENT_ROOT'] . '/404.json');
    exit();
}

# Return the legislator's data as an array.
$legislator = $leg->info($leg_id);

# Get this legislator's bills.
$sql = 'SELECT bills.id, bills.number, bills.catch_line,
        DATE_FORMAT(bills.date_introduced, "%M %d, %Y") AS date_introduced,
        committees.name, sessions.year,
        (
            SELECT status
            FROM bills_status
            WHERE bill_id=bills.id
            ORDER BY date DESC, id DESC
            LIMIT 1
        ) AS status
        FROM bills
        LEFT JOIN sessions
            ON bills.session_id=sessions.id
        LEFT JOIN committees
            ON bills.last_committee_id = committees.id
        WHERE bills.chief_patron_id="' . $legislator['id'] . '"
        ORDER BY sessions.year DESC,
        SUBSTRING(bills.number FROM 1 FOR 2) ASC,
        CAST(LPAD(SUBSTRING(bills.number FROM 3), 4, "0") AS unsigned) ASC';
$result = mysqli_query($db, $sql);
if (mysqli_num_rows($result) > 0) {
    $legislator['bills'] = array();
    while ($bill = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $bill['url'] = 'https://www.richmondsunlight.com/bill/' . $bill['year'] . '/'
            . $bill['number'] . '/';
        $bill['number'] = strtoupper($bill['number']);
        $legislator['bills'][] = (array) $bill;
    }
}

# Create a new statistics object.
$stats = new Statistics();
$activity = $stats->legislator_activity($legislator['id']);
if ($activity !== false) {
    $legislator['activity'] = $activity;
}

# We publicly call the shortname the "ID," so swap them out.
$legislator['rs_id'] = $legislator['id'];
$legislator['id'] = $legislator['shortname'];

# Send the JSON.
echo json_encode($legislator);
