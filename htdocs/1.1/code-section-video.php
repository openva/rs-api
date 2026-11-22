<?php

declare(strict_types=1);

// Code Section Video JSON
// PURPOSE: Accepts a section of code and responds with video clips that addressed that section:
// clips of the legislature discussing bills that cite it, each linked to the moment on its video's
// page on Richmond Sunlight.
//
// Each bill's clips come from Video::by_bill(), the same ones its bill page plays: worked out from
// the bill's chyrons in video_index, plus clips stored in video_clips (which hasn't been written
// since 2020) for videos with no chyrons for it. Depends on Video::by_bill() and
// seconds_to_time(), in the includes copied from richmondsunlight.com.

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

$no_video = 'No video was found that’s based on bills that cite section ' . $section . '.';

// The bills that cite this section.
$stmt = $db->prepare(
    'SELECT DISTINCT bills.id, bills.number, bills.catch_line, sessions.year
        FROM bills_section_numbers
        INNER JOIN bills
            ON bills_section_numbers.bill_id = bills.id
        LEFT JOIN sessions
            ON bills.session_id = sessions.id
        WHERE bills_section_numbers.section_number = ?'
);
if ($stmt === false) {
    api_json_error(500, 'Database error');
}

$stmt->bind_param('s', $section);
if ($stmt->execute() === false) {
    api_json_error(500, 'Database error');
}

$result = $stmt->get_result();
if ($result === false) {
    api_json_error(500, 'Database error');
}

// Each bill's clips.
$found = [];
while ($bill = $result->fetch_assoc()) {
    $video = new Video();
    $video->bill_id = (int) $bill['id'];
    $bill_clips = $video->by_bill();
    if ($bill_clips === false) {
        continue;
    }
    foreach ($bill_clips as $bill_clip) {
        $found[] = ['bill' => $bill, 'clip' => $bill_clip];
    }
}
if (count($found) === 0) {
    api_json_error(404, 'No Video Found', $no_video);
}

// The slug of each video, since a slug is needed to link to the video's page.
$file_ids = array_values(array_unique(array_map(fn($item) => (int) $item['clip']['file_id'], $found)));
$stmt = $db->prepare(
    'SELECT id, slug
        FROM files
        WHERE id IN (' . implode(',', array_fill(0, count($file_ids), '?')) . ')
            AND slug IS NOT NULL
            AND slug != ""'
);
if ($stmt === false) {
    api_json_error(500, 'Database error');
}

$stmt->bind_param(str_repeat('i', count($file_ids)), ...$file_ids);
if ($stmt->execute() === false) {
    api_json_error(500, 'Database error');
}

$result = $stmt->get_result();
if ($result === false) {
    api_json_error(500, 'Database error');
}

$slugs = [];
while ($file = $result->fetch_assoc()) {
    $slugs[(int) $file['id']] = (string) $file['slug'];
}

// Build up a list of all video clips, keeping each one's file ID (for grouping) to one side.
$clips = [];
foreach ($found as $item) {
    $bill = array_map('api_stripslashes', $item['bill']);
    $clip = $item['clip'];

    $file_id = (int) $clip['file_id'];
    if (!isset($slugs[$file_id])) {
        continue;
    }
    $slug = $slugs[$file_id];

    $bill_number = strtolower((string) $bill['number']);
    $year = (string) $bill['year'];

    // Chyron clips are padded, which can put a clip's start before the start of the video.
    $start = max(0, (int) $clip['start']);
    $end = max($start, (int) $clip['end']);

    $poster = !empty($clip['screenshot']) ? (string) $clip['screenshot'] : null;

    $video_url = (string) $clip['path'];
    if (strpos($video_url, '/') === 0) {
        $video_url = 'https://www.richmondsunlight.com' . $video_url;
    }

    $clips[] = [
        'file_id' => $file_id,
        'bill_number' => $bill_number,
        'year' => $year,
        'date' => $clip['date'],
        'chamber' => $clip['chamber'],
        'time_start' => seconds_to_time($start),
        'time_end' => seconds_to_time($end),
        'screenshot' => $poster ?? '',
        'video_url' => $video_url,
        'bill_url' => 'https://www.richmondsunlight.com/bill/' . $year . '/' . $bill_number . '/',
        'catch_line' => $bill['catch_line'],
        'slug' => $slug,
        'url' => 'https://www.richmondsunlight.com/video/' . $slug . '/?t=' . $start,
        'start' => $start,
        'end' => $end,
        'poster' => $poster,
    ];
}
if (count($clips) === 0) {
    api_json_error(404, 'No Video Found', $no_video);
}

// Oldest first: by the video's date, then where the clip starts.
usort($clips, fn($a, $b) => [$a['date'], $a['start'], $a['chamber'], $a['bill_number']]
    <=> [$b['date'], $b['start'], $b['chamber'], $b['bill_number']]);

// Eliminate any clip that is a subset of another one in the same video, such as when two bills
// that cite this section were taken up together. Of two identical clips, the first is kept.
$clips_to_remove = [];
foreach ($clips as $key => $clip) {
    foreach ($clips as $candidate_key => $candidate) {
        if ($key === $candidate_key || isset($clips_to_remove[$candidate_key])) {
            continue;
        }
        if ($candidate['file_id'] !== $clip['file_id']) {
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

// The file ID is only for grouping.
foreach ($clips as &$clip) {
    unset($clip['file_id']);
}
unset($clip);

api_json_success($clips);
