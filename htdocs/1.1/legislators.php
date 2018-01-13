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
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/includes/functions.inc.php';
require_once 'functions.inc.php';

/*
 * DECLARATIVE FUNCTIONS
 * Run those functions that are necessary prior to loading this specific page.
 */ 
$database = new Database;
$database->connect_old();

/*
 * LOCALIZE VARIABLES
 */
if ( isset($_GET['year']) && strlen($_GET['year']) == 4 && is_numeric($_GET['year']) )
{
	$year = $_GET['year'];
}
if ( isset($_GET['callback']) && strlen($_GET['callback']) < 32 )
{
	$callback = $_GET['callback'];
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
			ON representatives.district_id=districts.id
		ORDER BY representatives.name ASC';

$result = mysql_query($sql);
if (mysql_num_rows($result) > 0)
{

	$legislators = array();

	while ($legislator = mysql_fetch_array($result, MYSQL_ASSOC))
	{

		$legislator = array_map('stripslashes', $legislator);
		
		/*
		 * Eliminate any useless data.
		 */  
		if ($legislator['date_started'] == '0000-00-00')
		{
			unset($legislator['date_started']);
		}
		if ($legislator['date_ended'] == '0000-00-00')
		{
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
else
{
	$legislators = 'Richmond Sunlight has no record of any legislators. Yes, we are also troubled by this.';
}

/*
 *  Send an HTTP header defining the content as JSON.
 */
header('Content-type: application/json');

/*
 * Send an HTTP header allowing CORS.
 */
header("Access-Control-Allow-Origin: *");

/*
 * Send the JSON. If a callback has been specified, prefix the JSON with that callback and wrap the
 * JSON in parentheses.
 */
if (isset($callback))
{
	echo $callback.' (';
}
echo json_encode($legislators);
if (isset($callback))
{
	echo ');';
}
