<?php

declare(strict_types=1);

// Video captions
// PURPOSE: Accepts a video's slug and emits its captions, as WebVTT, from its transcript.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

$slug = (string) filter_input(INPUT_GET, 'slug');
if (!Video::is_slug($slug)) {
    api_json_error(404, 'No Video Found', 'Video slugs are four characters long.');
}

api_db();

$captions = Video::captions_for_api($slug);
if ($captions === null) {
    api_json_error(404, 'No Captions Found', 'There is no video with this slug, or it has no transcript.');
}

header('Content-Type: text/vtt; charset=utf-8');
echo $captions;
