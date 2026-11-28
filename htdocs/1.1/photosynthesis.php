<?php

declare(strict_types=1);

// Photosynthesis portfolio JSON
// PURPOSE: Accepts a public portfolio's hash (e.g., "pwtp1") and emits the portfolio: its name,
// notes, type ("normal" or "smart"), a smart portfolio's criteria, its owner, and its bills of the
// current session (but those hidden from it). An empty portfolio has "bills": []. A private
// portfolio isn't found. The work is done by Portfolio::record_for_api(), in the includes that are
// copied from richmondsunlight.com.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/settings.inc.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.inc.php';
require_once __DIR__ . '/functions.inc.php';

header('Content-type: application/json');

$hash = (string) filter_input(INPUT_GET, 'hash');
if (!Portfolio::is_hash($hash)) {
    api_json_error(404, 'No Portfolio Found', 'Portfolio hashes are five lowercase letters and numbers.');
}

api_db();

$portfolio = Portfolio::record_for_api($hash);
if ($portfolio === null) {
    api_json_error(404, 'No Portfolio Found', 'Portfolio ' . $hash . ' does not exist.');
}

api_json_success($portfolio);
