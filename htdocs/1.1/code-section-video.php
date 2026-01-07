<?php

declare(strict_types=1);

// Code Section Video JSON
// PURPOSE: Accepts a section of code and responds with video clips that addressed that section.

// Includes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

// Connect
$db = api_db();

$section = filter_input(
    INPUT_GET,
    'section',
    FILTER_VALIDATE_REGEXP,
    ['options' => ['regexp' => '/^[.0-9a-z-]{3,20}$/']]
);
if ($section === false || $section === null) {
    api_json_error(400, 'Invalid section', 'Parameter section must be 3-20 characters, a-z, 0-9, dots, or dashes.');
}
$stmt = $db->prepare(
    'SELECT DISTINCT bills.number AS bill_number, sessions.year, files.date, files.chamber,
        video_clips.time_start, video_clips.time_end, video_clips.screenshot,
        files.path AS video_url
        FROM bills_section_numbers
        INNER JOIN video_clips
            ON bills_section_numbers.bill_id = video_clips.bill_id
        LEFT JOIN files
            ON video_clips.file_id = files.id
        LEFT JOIN bills
            ON bills_section_numbers.bill_id = bills.id
        LEFT JOIN sessions
            ON bills.session_id = sessions.id
        WHERE bills_section_numbers.section_number = ?
        ORDER BY files.date ASC, video_clips.time_start ASC'
);
if ($stmt === false) {
    api_json_error(500, 'Database error');
}

$stmt->bind_param('s', $section);
$result = $stmt->execute();
if ($result === false) {
    api_json_error(500, 'Database error');
}

$result = $stmt->get_result();
if ($result === false || $result->num_rows == 0) {
    api_json_error(404, 'No Video Found', 'No video was found that’s based on bills that cite section ' . $section . '.');
}

// Build up a list of all video clips
$clips = [];
while ($clip = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
    $clip['bill_url'] = 'https://www.richmondsunlight.com/bill/' . $clip['year'] . '/'
        . $clip['bill_number'] . '/';
    $clip['bill_number'] = strtolower($clip['bill_number']);
    $clip['year'] = (string) $clip['year'];
    $clip['screenshot'] = str_replace('/video/', 'https://s3.amazonaws.com/video.richmondsunlight.com/', $clip['screenshot']);
    if (strpos($clip['video_url'], 'archive.org') === false) {
        $clip['video_url'] = 'https://www.richmondsunlight.com' . $clip['video_url'];
    }
    $clips[] = array_map('api_stripslashes', $clip);
}

// Eliminate any clip that is a subset of another one. For example, we might have gotten a list of
// 10 clips about a given bill, 1 of which is the entire discussion, and 9 of which are individual
// clips of each legislator speaking about the bill. We only want that entire discussion here.
$clips_to_remove = [];
foreach ($clips as $key => $clip) {
    foreach ($clips as $candidate_key => $candidate) {
        if ($key === $candidate_key) {
            continue;
        }
        if ($candidate['video_url'] !== $clip['video_url']) {
            continue;
        }

        if (
            time_to_seconds($candidate['time_start']) <= time_to_seconds($clip['time_start'])
            && time_to_seconds($candidate['time_end']) >= time_to_seconds($clip['time_end'])
        ) {
            $clips_to_remove[$key] = true;
            break;
        }
    }
}
$clips = array_values(array_diff_key($clips, $clips_to_remove));

api_cache_control_for_session(null);
api_json_success($clips);
