<?php

/**
 * Tag suggestions
 *
 * PURPOSE
 * Displays an autocomplete list of tags, based on the text entered so far.
 **/


/*
 * Includes
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once 'functions.inc.php';

/*
 * Localize variables
 */
$fragment = mysql_escape_string($_REQUEST['term']);
if (isset($_REQUEST['callback']))
{
    $callback = $_REQUEST['callback'];
}

/*
 * Send an HTTP header defining the content as JSON.
 */
header('Content-type: application/json');

/*
 * Send an HTTP header allowing CORS.
 */
header("Access-Control-Allow-Origin: *");

$tags = new Tags;
$tags->fragment = $fragment;
$suggestions = $tags->get_suggestions();
if ($suggestions === FALSE)
{
    header('HTTP/1.0 404 Not Found');
    exit();
}

# Send the JSON. If a callback has been specified, prefix the JSON with that callback and wrap the
# JSON in parentheses.
if (isset($callback))
{
    echo $callback . ' (';
}
echo json_encode($suggestions);
if (isset($callback))
{
    echo ');';
}
