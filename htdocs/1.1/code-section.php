<?php

###
# Code Section JSON
#
# PURPOSE
# Accepts a section of code, and responds with a listing of bills that addressed that section.
#
# NOTES
# This is not intended to be viewed. It just spits out a JSON file and that's that.
#
###

# INCLUDES
# Include any files or libraries that are necessary for this specific
# page to function.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once 'functions.inc.php';

header('Content-type: application/json');

# DECLARATIVE FUNCTIONS
# Run those functions that are necessary prior to loading this specific
# page.
$database = new Database();
$database->connect_old();

# LOCALIZE VARIABLES
$section = mysql_escape_string(urldecode($_REQUEST['section']));

# Select the bill data from the database.
// Use proper bill number sorting
$sql = 'SELECT sessions.year, bills.number, bills.catch_line, bills.summary, bills.outcome,
		representatives.name_formatted AS legislator
		FROM bills
		LEFT JOIN bills_section_numbers
			ON bills.id = bills_section_numbers.bill_id
		LEFT JOIN sessions
			ON bills.session_id = sessions.id
		LEFT JOIN representatives
			ON bills.chief_patron_id = representatives.id
		WHERE bills_section_numbers.section_number =  "' . $section . '"
		ORDER BY year ASC, bills.number ASC';
$result = mysql_query($sql);
if (mysql_num_rows($result) == 0) {
    // What error SHOULD this return?
    header("Status: 404 Not Found");
    $message = array('error' =>
        array('message' => 'No Bills Found',
            'details' => 'No bills were found that cite section ' . $section . '.'));
    echo json_encode($message);
    exit;
}
# The MYSQL_ASSOC variable indicates that we want just the associated array, not both associated
# and indexed arrays.
$bill = mysql_fetch_array($result, MYSQL_ASSOC);

# Build up a listing of all bills.
# The MYSQL_ASSOC variable indicates that we want just the associated array, not both associated
# and indexed arrays.
while ($bill = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $bill['url'] = 'https://www.richmondsunlight.com/bill/' . $bill['year'] . '/' . $bill['number'] . '/';
    $bill['number'] = strtoupper($bill['number']);
    $bills[] = array_map('stripslashes', $bill);
}

# Send the JSON.
echo json_encode($bills);
