<?php

declare(strict_types=1);

// Single video JSON
// PURPOSE: Accepts a video ID and emits JSON for that video.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

$db = api_db();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    api_json_error(400, 'Invalid ID', 'Parameter ID must be a numeric video ID.');
}

$sql = 'SELECT
            files.id,
            files.date,
            files.chamber,
            committees.name AS committee,
            files.title,
            files.path,
            files.description,
            files.width,
            files.height,
            files.sponsor,
            (files.transcript IS NOT NULL) AS has_transcript,
            EXISTS(
                SELECT 1 FROM video_index
                WHERE video_index.file_id = files.id
                AND video_index.linked_id IS NOT NULL
            ) AS is_indexed
        FROM files
        LEFT JOIN committees
            ON files.committee_id=committees.id
        WHERE
            files.type="video"
            AND files.id = ' . $id;

$result = mysqli_query($db, $sql);
if ($result !== false && mysqli_num_rows($result) > 0) {
    $video = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $video = array_map('api_stripslashes', $video);

    if (isset($video['path']) && substr($video['path'], 0, 7) == '/video/') {
        $video['path'] = str_replace('/video/', 'https://video.richmondsunlight.com/', $video['path']);
    }
    
    $video['has_transcript'] = (bool) $video['has_transcript'];
    if ($video['has_transcript']) {
        $transcript_sql = 'SELECT text, time_start, time_end, new_speaker, legislator_id
            FROM video_transcript
            WHERE file_id = ' . $id . '
            ORDER BY time_start ASC';
        $transcript_result = mysqli_query($db, $transcript_sql);
        if ($transcript_result !== false) {
            $video['transcript'] = [];
            while ($row = mysqli_fetch_array($transcript_result, MYSQLI_ASSOC)) {
                $video['transcript'][] = $row;
            }
        }
    }

    // get the index, if it exists
    $video['is_indexed'] = (bool) $video['is_indexed'];
    if ($video['is_indexed']) {
        $index_sql = 'SELECT time, screenshot, raw_text, type, linked_id
            FROM video_index
            WHERE file_id = ' . $id . '
            AND linked_id IS NOT NULL
            ORDER BY time ASC';
        $index_result = mysqli_query($db, $index_sql);
        if ($index_result !== false) {
            $video['index'] = [];
            while ($row = mysqli_fetch_array($index_result, MYSQLI_ASSOC)) {
                $video['index'][] = $row;
            }
        }
    }

    api_cache_control_for_session(null);
    api_json_success($video);
}

api_json_error(404, 'No Video Found', 'No video was found with the provided ID.');
