<?php

declare(strict_types=1);

// Code Section Video JSON
// PURPOSE: Accepts a section of code and responds with video clips that addressed that section:
// clips of the legislature discussing bills that cite it, each linked to the moment on its video's
// page on Richmond Sunlight.
//
// Depends on time_to_seconds(), from includes/functions.inc.php (copied from richmondsunlight.com).

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

// Only clips in videos that have a slug, since a slug is needed to link to the video's page.
$stmt = $db->prepare(
    'SELECT DISTINCT bills.number AS bill_number, bills.catch_line, sessions.year, files.id AS file_id,
        files.slug, files.date, files.chamber, video_clips.time_start, video_clips.time_end,
        video_clips.screenshot, files.path AS video_url
        FROM bills_section_numbers
        INNER JOIN video_clips
            ON bills_section_numbers.bill_id = video_clips.bill_id
        INNER JOIN files
            ON video_clips.file_id = files.id
        LEFT JOIN bills
            ON bills_section_numbers.bill_id = bills.id
        LEFT JOIN sessions
            ON bills.session_id = sessions.id
        WHERE bills_section_numbers.section_number = ?
            AND files.slug IS NOT NULL
            AND files.slug != ""
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

// Build up a list of all video clips, keeping each one's file ID (for grouping) to one side.
$clips = [];
$file_ids = [];
while ($row = $result->fetch_assoc()) {
    $row = array_map('api_stripslashes', $row);

    $bill_number = (string) $row['bill_number'];
    $year = (string) $row['year'];
    $slug = (string) $row['slug'];
    $start = (int) time_to_seconds($row['time_start']);
    $end = (int) time_to_seconds($row['time_end']);
    $poster = api_screenshot_url((string) $row['screenshot']);

    $video_url = (string) $row['video_url'];
    if (strpos($video_url, 'archive.org') === false) {
        $video_url = 'https://www.richmondsunlight.com' . $video_url;
    }

    $file_ids[] = (int) $row['file_id'];
    $clips[] = [
        'bill_number' => strtolower($bill_number),
        'year' => $year,
        'date' => $row['date'],
        'chamber' => $row['chamber'],
        'time_start' => $row['time_start'],
        'time_end' => $row['time_end'],
        'screenshot' => $poster ?? '',
        'video_url' => $video_url,
        'bill_url' => 'https://www.richmondsunlight.com/bill/' . $year . '/' . $bill_number . '/',
        'catch_line' => $row['catch_line'],
        'slug' => $slug,
        'url' => 'https://www.richmondsunlight.com/video/' . $slug . '/?t=' . $start,
        'start' => $start,
        'end' => $end,
        'poster' => $poster,
    ];
}

// Eliminate any clip that is a subset of another one in the same video. For example, we might
// have gotten a list of 10 clips about a given bill, 1 of which is the entire discussion, and 9 of
// which are individual clips of each legislator speaking about the bill. We only want that entire
// discussion here. Of two identical clips, the first is kept.
$clips_to_remove = [];
foreach ($clips as $key => $clip) {
    foreach ($clips as $candidate_key => $candidate) {
        if ($key === $candidate_key || isset($clips_to_remove[$candidate_key])) {
            continue;
        }
        if ($file_ids[$candidate_key] !== $file_ids[$key]) {
            continue;
        }
        if ($candidate['start'] > $clip['start'] || $candidate['end'] < $clip['end']) {
            continue;
        }

        $identical = $candidate['start'] === $clip['start'] && $candidate['end'] === $clip['end'];
        if (!$identical || $candidate_key < $key) {
            $clips_to_remove[$key] = true;
            break;
        }
    }
}
$clips = array_values(array_diff_key($clips, $clips_to_remove));

api_json_success($clips);
