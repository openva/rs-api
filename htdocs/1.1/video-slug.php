<?php

declare(strict_types=1);

// Single video JSON, by slug
// PURPOSE: Accepts a video's slug (e.g., "k7qm") and emits everything about that video: the
// meeting it records, where to play it, the bills taken up and the legislators who spoke (as
// identified by the chyrons), its transcript, and the day's minutes and other videos. The work is
// done by Video::record_for_api(), in the includes that are copied from richmondsunlight.com.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

$slug = (string) filter_input(INPUT_GET, 'slug');
if (!Video::is_slug($slug)) {
    api_json_error(404, 'No Video Found', 'Video slugs are four characters long.');
}

api_db();

$video = Video::record_for_api($slug);
if ($video === null) {
    api_json_error(404, 'No Video Found', 'No video was found with the provided slug.');
}

api_json_success($video);
