<?php

declare(strict_types=1);

// Video listing JSON
// PURPOSE: Emits JSON with list of all videos; optional year filter.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

$db = api_db();

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^\d{4}$/']
]);
if ($year === false) {
    api_json_error(400, 'Invalid year', 'Parameter year must be a 4-digit year, if provided.');
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
            files.sponsor
        FROM files
        LEFT JOIN committees
            ON files.committee_id=committees.id
        WHERE
            files.type="video" ';
if ($year !== null) {
    $sql .= 'AND YEAR(files.date) = ' . $year . ' ';
}
$sql .= 'ORDER BY date ASC';

$result = mysqli_query($db, $sql);
if ($result !== false && mysqli_num_rows($result) > 0) {
    $videos = [];

    while ($video = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $video = array_map('api_stripslashes', $video);

        // Anything with a /video/ suffix is in the video S3 bucket
        if (substr($video['path'], 0, 7) == '/video/') {
            $video['path'] = str_replace('/video/', 'https://video.richmondsunlight.com/', $video['path']);
        }

        $videos[] = $video;
    }

    api_cache_control_for_session(null);
    api_json_success($videos);
}

api_json_error(404, 'No Video Found', 'Richmond Sunlight has no record of any videos.');
