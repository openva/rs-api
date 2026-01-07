<?php


require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';

header('Content-type: application/json');

$database = new Database();
$db = $database->connect_mysqli();

$section = filter_input(INPUT_GET, 'section', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[.0-9a-z-]{3,20}$/']
]);
}
$section_safe = mysqli_real_escape_string($db, $section);

# Select the bill data from the database.
$sql = 'SELECT DISTINCT bills.number AS bill_number, sessions.year, files.date, files.chamber,
		video_clips.time_start, video_clips.time_end, video_clips.screenshot,
		files.path AS video_url
		FROM bills_section_numbers
		INNER JOIN video_clips
			ON bills_section_numbers.bill_id = video_clips.bill_id
		LEFT JOIN files
			ON video_clips.file_id = files.id
		LEFT JOIN bills
			ON bills_section_numbers . bill_id = bills.id
		LEFT JOIN sessions
			ON bills.session_id = sessions.id
		WHERE bills_section_numbers.section_number = "' . $section_safe . '"
		ORDER BY files.date ASC, video_clips.time_start ASC ';
$result = mysqli_query($db, $sql);
if ($result === false || mysqli_num_rows($result) == 0) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
    $message = array('error' =>
        array('message' => 'No Video Found',
            'details' => 'No video was found that’s based on bills that cite section ' . $section . '.'));
    echo json_encode($message);
    exit;
}

$clips = array();
// Build up a list of all video clips
while ($clip = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $clip['bill_url'] = 'https://www.richmondsunlight.com/bill/' . $clip['year'] . '/'
        . $clip['bill_number'] . '/';
    $clip['bill_number'] = strtoupper($clip['bill_number']);
    $clip['screenshot'] = str_replace('/video/', 'https://s3.amazonaws.com/video.richmondsunlight.com/', $clip['screenshot']);
    if (strpos($clip['video_url'], 'archive.org') === false) {
        $clip['video_url'] = 'https://www.richmondsunlight.com' . $clip['video_url'];
    }
    $clips[] = array_map('stripslashes', $clip);
}

foreach ($clips as $key => &$clip) {
    foreach ($clips as $candidate) {
        if ($candidate['video_url'] == $clip['video_url']) {
            # If there is another clip that starts earlier than or when this one does, and ends
            # later than or when this one does, than delete this one.
            if (
                (time_to_seconds($candidate['time_start']) <= time_to_seconds($clip['time_start']))
                &&
                (time_to_seconds($candidate['time_end']) >= time_to_seconds($clip['time_end']))
            ) {
                unset($clips[$key]);
            }
// Eliminate any clip that is a subset of another one. For example, we might have gotten a list of
// 10 clips about a given bill, 1 of which is the entire discussion, and 9 of which are individual
// clips of each legislator speaking about the bill. We only want that entire discussion here.

            break(2);
        }
    }
}

$clips = array_values($clips);

# Make this an object.
$clips = (object) $clips;

# Send the JSON.
echo json_encode($clips);
