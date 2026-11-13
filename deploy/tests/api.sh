#!/usr/bin/env bash

set -euo pipefail

echo "Running API tests..."

# Prefer in-cluster hostname if available; otherwise fall back to published port
if getent hosts rs_api >/dev/null 2>&1; then
    API_BASE="${API_BASE:-http://rs_api/1.1}"
else
    API_BASE="${API_BASE:-http://localhost:5001/1.1}"
fi

ERRORED=false

check() {
    local path="$1"
    local jq_expr="$2"
    local expected="$3"
    local url="${API_BASE}${path}"

    response="$(curl --silent --show-error --fail "$url")" || {
        echo "❌: $url request failed"
        response="$(curl --silent "$url")"
        echo "$response"
        ERRORED=true
        return
    }

    output="$(printf '%s' "$response" | jq "$jq_expr")"
    if [ "$output" != "$expected" ]; then
        echo "❌: $url (${jq_expr}) expected $expected, got \"$output\""
        echo "$response"
        ERRORED=true
    else
        echo "✅: $url (${jq_expr}) matches expected"
    fi
}

# Bill endpoint tests
check "/bill/2025/hb41.json" ".catch_line" '"Standards of Learning; programs of instruction, civics education on local government."'
check "/bill/2025/hb41.json" ".patron_shortname" '"wcgreen"'
check "/bill/2025/hb41.json" ".status" '"failed committee"'
check "/bill/2025/hb41.json" ".chamber" '"house"'
check "/bill/2025/hb41.json" ".year" '"2025"'
check "/bill/2025/hb41.json" ".related | length > 0" 'true'
check "/bill/2025/hb41.json" ".text[0].number" '"HB41"'
check "/bill/2025/hb41.json" "any(.status_history[].translation; contains(\"failed committee\"))" 'true'
check "/bill/2025/hb41.json" "any(.tags[]?; . == \"high school\")" 'true'
check "/bill/2025/hb41.json" ".full_text | contains(\"develop the skills\")" 'true'

# Legislator endpoint tests (single legislator)
check "/legislator/rcdeeds.json" ".name_formatted" '"Sen. Creigh Deeds (D-Charlottesville)"'
check "/legislator/rcdeeds.json" ".shortname" '"rcdeeds"'
check "/legislator/rcdeeds.json" ".chamber" '"senate"'
check "/legislator/rcdeeds.json" ".district" '"11"'
check "/legislator/rcdeeds.json" ".email" '"senatordeeds@senate.virginia.gov"'

# Bills list endpoint tests
check "/bills/2025.json" "type" '"array"'
check "/bills/2025.json" "length > 0" 'true'
check "/bills/2025.json" ".[0] | has(\"number\")" 'true'
check "/bills/2025.json" ".[0] | has(\"chamber\")" 'true'
check "/bills/2025.json" ".[0] | has(\"title\")" 'true'
check "/bills/2025.json" ".[0] | has(\"status\")" 'true'
check "/bills/2025.json" "any(.[]; .number == \"sb839\")" 'true'
check "/bills/2025.json" "any(.[]; .number == \"hb1591\")" 'true'
check "/bills/2025.json" "all(.[]; .number | test(\"^[a-z]+[0-9]+$\"))" 'true'
check "/bills/2025.json" "all(.[]; .chamber | test(\"^(house|senate)$\"))" 'true'

# Legislators list endpoint tests
check "/legislators.json?year=2025" "type" '"array"'
check "/legislators.json?year=2025" "length > 0" 'true'
check "/legislators.json?year=2025" ".[0] | has(\"id\")" 'true'
check "/legislators.json?year=2025" ".[0] | has(\"name\")" 'true'
check "/legislators.json?year=2025" ".[0] | has(\"chamber\")" 'true'
check "/legislators.json?year=2025" ".[0] | has(\"party\")" 'true'
check "/legislators.json?year=2025" ".[0] | has(\"site_url\")" 'true'
check "/legislators.json?year=2025" "any(.[]; .id == \"rcdeeds\")" 'true'
check "/legislators.json?year=2025" "all(.[]; .district | type == \"string\")" 'true'

# Code section endpoint tests (bills citing a section)
check "/bysection/22.1-277.json" "type" '"array"'
check "/bysection/22.1-277.json" "length > 0" 'true'
check "/bysection/22.1-277.json" ".[0] | has(\"number\")" 'true'
check "/bysection/22.1-277.json" ".[0] | has(\"year\")" 'true'
check "/bysection/22.1-277.json" ".[0] | has(\"catch_line\")" 'true'
check "/bysection/22.1-277.json" ".[0] | has(\"url\")" 'true'
check "/bysection/22.1-277.json" "any(.[]; .number == \"sb738\")" 'true'
check "/bysection/22.1-277.json" "all(.[]; .number | test(\"^[a-z]+[0-9]+$\"))" 'true'

# Tag suggest endpoint tests
# Note: The htaccess rewrite has been fixed, but Tags::get_suggestions() returns false
# for the test data.
# TODO: Investigate Tags class behavior, then enable these tests:
# check "/tag-suggest?term=cell" "type" '"array"'
# check "/tag-suggest?term=cell" "length > 0" 'true'
# check "/tag-suggest?term=cell" "any(.[]; contains(\"cell\"))" 'true'

# Additional bill tests with test data
check "/bill/2025/sb839.json" ".chamber" '"senate"'
check "/bill/2025/sb839.json" ".catch_line" '"Zoning; by-right multifamily development in areas zoned for commercial use."'
check "/bill/2025/sb839.json" ".status" '"introduced"'
check "/bill/2025/sb738.json" ".chamber" '"senate"'
check "/bill/2025/sb738.json" ".outcome" '"passed"'
check "/bill/2025/sb738.json" ".status" '"enacted"'
check "/bill/2025/hb1591.json" ".chamber" '"house"'
check "/bill/2025/hb1591.json" ".catch_line" '"Tax credit; veterinary care for retired police canines."'
check "/bill/2025/hb1910.json" ".status" '"signed by governor"'
check "/bill/2025/hb1910.json" ".outcome" '"passed"'
check "/bill/2025/hb1894.json" ".status" '"vetoed by governor"'
check "/bill/2025/hb1894.json" ".outcome" '"failed"'

# Video list tests
check "/videos.json" "type" '"array"'
check "/videos.json" "length > 0" 'true'
check "/videos.json" ".[0] | has(\"path\")" 'true'
check "/videos.json" ".[0] | has(\"title\")" 'true'
check "/videos.json" ".[0] | has(\"date\")" 'true'
check "/videos.json" ".[0] | has(\"has_transcript\")" 'true'
check "/videos.json" ".[0] | has(\"id\")" 'true'
check "/videos.json" "any(.[]; .date == \"2024-01-12\")" 'true'
check "/videos.json" "all(.[]; .is_indexed | type == \"boolean\")" 'true'

# Video endpoint tests (single video)
check "/video/14569.json" ".date" '"2024-01-12"'
check "/video/14569.json" ".path" '"https://archive.org/download/rs-senate-20240112-senate-regular-session/ia_video_gd4q3acocj0o6ZvtGqF.mp4"'
check "/video/14569.json" ".chamber" '"senate"'
check "/video/14569.json" ".title" '"Senate Session"'
check "/video/14569.json" ".width" '"640"'

# Video endpoint tests (single video, by slug). The videos are in richmondsunlight.com's fixtures
# (deploy/mysql/video-minutes-fixtures.sql and video-page-fixtures.sql), and their slugs are set by
# zz-video-slugs.sql: b334 is a House floor session, fcmk a committee meeting, fcmm a subcommittee
# meeting, and fcmn a video that's still being processed, all on January 26, 2026.
check "/video/15141.json" ".slug" '"b334"'
check "/video/15141.json" ".url" '"https://www.richmondsunlight.com/video/b334/2026-01-26-house-floor/"'
check "/video/b334.json" ".status" '"available"'
check "/video/b334.json" ".meeting.type" '"floor"'
check "/video/b334.json" ".meeting.label" '"House floor session"'
check "/video/b334.json" ".url" '"https://www.richmondsunlight.com/video/b334/2026-01-26-house-floor/"'
check "/video/b334.json" ".media.mp4 | test(\"^https://\")" 'true'
check "/video/b334.json" ".bills | length" '14'
check "/video/b334.json" ".bills[0].number" '"hb1384"'
check "/video/b334.json" "any(.speakers[]; .label == \"Del. Jessica Anderson (D-Williamsburg)\")" 'true'
check "/video/b334.json" ".transcript | length > 0" 'true'
check "/video/b334.json" ".captions" '"https://api.richmondsunlight.com/1.1/video/b334.vtt"'
check "/video/b334.json" ".day" '"https://www.richmondsunlight.com/day/2026-01-26/"'
check "/video/b334.json" ".minutes" '"https://www.richmondsunlight.com/day/2026-01-26/#minutes-house"'
check "/video/b334.json" "[.same_day[].slug] | join(\",\")" '"fcmk,fcmm,fcmn"'
check "/video/fcmk.json" ".meeting.type" '"committee"'
check "/video/fcmk.json" ".meeting.committee.url" '"https://www.richmondsunlight.com/committee/house/appropriations/"'
check "/video/fcmk.json" ".pending | join(\",\")" '"transcript"'
check "/video/fcmm.json" ".meeting.type" '"subcommittee"'
check "/video/fcmm.json" ".meeting.parent_committee.shortname" '"appropriations"'
check "/video/fcmm.json" ".url" '"https://www.richmondsunlight.com/video/fcmm/2026-01-26-house-appropriations-capital-outlay/"'
check "/video/fcmn.json" ".status" '"processing"'

# Day endpoint tests. The day's actions, dockets, and meetings are in richmondsunlight.com's
# deploy/mysql/day-page-fixtures.sql, and its videos and minutes in the fixtures named above.
check "/day/2026-01-26.json" ".url" '"https://www.richmondsunlight.com/day/2026-01-26/"'
check "/day/2026-01-26.json" ".in_session" 'true'
check "/day/2026-01-26.json" "[.chambers[].chamber] | join(\",\")" '"house,senate"'
check "/day/2026-01-26.json" ".chambers[0].videos[0].slug" '"b334"'
check "/day/2026-01-26.json" ".chambers[0].minutes | startswith(\"Called to order\")" 'true'
check "/day/2026-01-26.json" "[.chambers[0].actions[] | select(.bill.number == \"hb176\")][0].vote.url" '"https://www.richmondsunlight.com/bill/2026/hb176/hv0126/"'
check "/day/2026-01-26.json" "[.chambers[0].actions[] | select(.bill.number == \"hb341\")][0] | [.type, .vote.close]" '[
  "failed",
  true
]'
check "/day/2026-01-26.json" "any(.chambers[].actions[]; .bill.number == \"hb414\")" 'false'
check "/day/2026-01-26.json" "[.meetings[] | select(.label == \"House Appropriations: Capital Outlay Subcommittee\")][0] | [.time, .actions[0].bill.number, .docket[0].number]" '[
  "07:30",
  "hb206",
  "hb210"
]'
check "/day/2026-01-26.json" "[.meetings[] | select(.label == \"House Rules Committee\")][0].actions[0].vote.voice" 'true'
check "/day/2026-01-26.json" "any(.meetings[]; .label | test(\"Caucus\"))" 'false'
check "/day/2026-01-26.json" ".coming_up" 'null'
check "/day/latest.json" ".coming_up.meetings[0].label" '"Joint Subcommittee on Day Page Fixtures"'
check "/days.json?year=2026" "[.[] | select(.date == \"2026-01-26\")][0].floor | join(\",\")" '"house,senate"'
check "/days.json?year=2026" "any(.[]; .date == \"2026-01-25\")" 'false'
check "/video/fcmn.json" ".media.mp4" 'null'

# Video list filters
check "/videos.json?date=2026-01-26" "length" '4'
check "/videos.json?date=2026-01-26&chamber=senate" "length" '0'
check "/videos.json?chamber=house&committee=appropriations" "[.[].slug] | join(\",\")" '"fcmk,fcmm"'
check "/videos.json?year=2026" "all(.[]; .date | startswith(\"2026\"))" 'true'
check "/videos.json" "all(.[]; .slug | test(\"^[bcdfghjkmnpqrstvwxz2-9]{4}$\"))" 'true'

# Video captions
captions_url="${API_BASE}/video/b334.vtt"
captions="$(curl --silent --fail "$captions_url" || true)"
if [[ "$captions" == WEBVTT* ]]; then
    echo "✅: $captions_url is WebVTT"
else
    echo "❌: $captions_url is not WebVTT"
    ERRORED=true
fi

# Error handling tests - expect 404 for invalid inputs
check_404() {
    local path="$1"
    local url="${API_BASE}${path}"

    http_code="$(curl --silent --output /dev/null --write-out '%{http_code}' "$url")"
    if [ "$http_code" = "404" ]; then
        echo "✅: $url returns 404 as expected"
    else
        echo "❌: $url expected 404, got $http_code"
        ERRORED=true
    fi
}

check_status() {
    local path="$1"
    local expected_status="$2"
    local expected_substr="${3:-}"
    local url="${API_BASE}${path}"

    raw="$(curl --silent --show-error --location --write-out 'HTTPSTATUS:%{http_code}' "$url")" || true
    status="${raw##*HTTPSTATUS:}"
    body="${raw%HTTPSTATUS:*}"

    if [ "$status" != "$expected_status" ]; then
        echo "❌: $url expected HTTP $expected_status, got $status"
        echo "$body"
        ERRORED=true
        return
    fi

    if [ -n "$expected_substr" ] && ! printf '%s' "$body" | grep -q "$expected_substr"; then
        echo "❌: $url expected body to contain \"$expected_substr\""
        echo "$body"
        ERRORED=true
        return
    fi

    echo "✅: $url returns $expected_status as expected"
}

# Invalid bill numbers should return 400 when the pattern is invalid; well-formed but missing should be 404
check_status "/bill/2025/invalid.json" 400
check_status "/bill/2025/xx999.json" 400
check_status "/bill/9999/hb1.json" 404

# Invalid legislator should return 404
check_404 "/legislator/nonexistent.json"
# Invalid legislator year should return 400
check_status "/legislators.json?year=20ab" 400

# Invalid code section format should return 400
check_status "/bysection/invalid!section.json" 400

# Non-existent code section should return 404
check_404 "/bysection/99.9-999.json"

# Additional endpoints should return errors for missing data
check_404 "/section-video/22.1-277.json"
check_404 "/photosynthesis/pwt01.json"
check "/tag-suggest?term=cell" "type" '"array"'
check "/tag-suggest?term=cell" "length > 0" 'true'
check_status "/vote/2025/999999.json" 404

# Videos: a slug that doesn't exist, malformed slugs, a video with no captions, and bad filters
check_404 "/video/bbbb.json"
check_404 "/video/abcd.json"
check_404 "/video/b33.json"
check_404 "/video/fcmn.vtt"
check_status "/videos.json?committee=appropriations" 400
check_status "/videos.json?date=2026-02-30" 400
check_status "/videos.json?chamber=assembly" 400

# Days: a day with nothing recorded, one that hasn't happened, one that doesn't exist, and a
# missing year
check_404 "/day/2026-01-25.json"
check_404 "/day/2098-01-05.json"
check_404 "/day/2026-02-30.json"
check_status "/days.json" 400

# =============================================================================
# Field value validation tests
# =============================================================================

# Chamber values must be 'house' or 'senate'
check "/bill/2025/sb738.json" ".chamber | test(\"^(house|senate)$\")" 'true'
check "/bill/2025/hb1591.json" ".chamber | test(\"^(house|senate)$\")" 'true'
check "/legislator/rcdeeds.json" ".chamber | test(\"^(house|senate)$\")" 'true'

# Party values must be valid (D, R, or I)
check "/legislators.json?year=2025" "all(.[]; .party | test(\"^(D|R|I)$\"))" 'true'

# URLs should be properly formatted with https
check "/bysection/22.1-277.json" ".[0].url | test(\"^https://\")" 'true'
check "/legislators.json?year=2025" ".[0].site_url | test(\"^https://\")" 'true'

# Bill numbers format validation (lowercase across all endpoints)
check "/bill/2025/sb738.json" ".number | test(\"^[a-z]+[0-9]+$\")" 'true'
check "/bysection/22.1-277.json" ".[0].number | test(\"^[a-z]+[0-9]+$\")" 'true'

# Year should be a 4-digit string
check "/bill/2025/sb738.json" ".year | test(\"^[0-9]{4}$\")" 'true'
check "/bysection/22.1-277.json" ".[0].year | test(\"^[0-9]{4}$\")" 'true'

# =============================================================================
# Edge case tests
# =============================================================================

# Different bill prefixes (House and Senate)
# Note: bill detail endpoint returns lowercase bill numbers
check "/bill/2025/hb1910.json" ".number" '"hb1910"'
check "/bill/2025/sb738.json" ".number" '"sb738"'

# Code sections with various formats (dots, hyphens, numbers)
check "/bysection/22.1-277.json" "length > 0" 'true'
check "/bysection/15.2-2286.2.json" "type" '"array"'

# Bills list has expected fields (patron field was removed in this endpoint)
check "/bills/2025.json" ".[0] | has(\"date_introduced\")" 'true'
check "/bills/2025.json" ".[0] | has(\"outcome\")" 'true'

# =============================================================================
# HTTP header tests
# =============================================================================

check_content_type() {
    local path="$1"
    local url="${API_BASE}${path}"

    content_type="$(curl -sI "$url" | grep -i '^content-type:' | tr -d '\r\n' | cut -d: -f2 | xargs)"
    if [[ "$content_type" == *"application/json"* ]]; then
        echo "✅: $url has Content-Type: application/json"
    else
        echo "❌: $url expected Content-Type application/json, got: $content_type"
        ERRORED=true
    fi
}

check_cors_header() {
    local path="$1"
    local url="${API_BASE}${path}"

    cors="$(curl -sI "$url" | grep -i '^access-control-allow-origin:' | tr -d '\r\n')"
    if [[ -n "$cors" ]]; then
        echo "✅: $url has CORS header"
    else
        echo "❌: $url missing Access-Control-Allow-Origin header"
        ERRORED=true
    fi
}

# Verify Content-Type headers
check_content_type "/bill/2025/sb738.json"
check_content_type "/legislators.json?year=2025"
check_content_type "/bills/2025.json"
check_content_type "/bysection/22.1-277.json"

# Verify CORS headers
check_cors_header "/bill/2025/sb738.json"
check_cors_header "/legislators.json?year=2025"

# Verify Cache-Control headers. They're set in Apache (htdocs/1.1/.htaccess), not in PHP, so there's
# exactly one per successful response, and none on an error. The value is either the header's whole
# text, or "none".
check_cache_control() {
    local path="$1"
    local expected="$2"
    local url="${API_BASE}${path}"

    headers="$(curl --silent --dump-header - --output /dev/null "$url" | tr -d '\r')"
    count="$(printf '%s\n' "$headers" | grep -ic '^cache-control:' || true)"
    value="$(printf '%s\n' "$headers" | grep -i '^cache-control:' | head -1 | sed 's/^[^:]*: *//' || true)"
    expires="$(printf '%s\n' "$headers" | grep -ic '^expires:' || true)"

    if [ "$expected" = "none" ]; then
        if [ "$count" != "0" ]; then
            echo "❌: $url should have no Cache-Control header, but has: $value"
            ERRORED=true
        else
            echo "✅: $url has no Cache-Control header"
        fi
    elif [ "$count" != "1" ]; then
        echo "❌: $url has $count Cache-Control headers, and should have exactly 1: $(printf '%s\n' "$headers" | grep -i '^cache-control:' | tr '\n' ' ')"
        ERRORED=true
    elif [ "$value" != "$expected" ]; then
        echo "❌: $url has Cache-Control \"$value\", expected \"$expected\""
        ERRORED=true
    elif [ "$expires" != "0" ]; then
        echo "❌: $url has an Expires header, which only duplicates Cache-Control"
        ERRORED=true
    else
        echo "✅: $url has Cache-Control: $value"
    fi
}

# A bill from a past session is cached for 30.5 days. One from the current year is cached for an hour.
if [ "2025" = "$(date +%Y)" ]; then
    check_cache_control "/bill/2025/hb41.json" "max-age=3600, public"
else
    check_cache_control "/bill/2025/hb41.json" "max-age=2635200, public"
fi
check_cache_control "/bill/2024/hb1.json" "max-age=2635200, public"

# Everything else isn't cached.
check_cache_control "/legislators.json?year=2025" "max-age=0, public"
check_cache_control "/bills/2025.json" "max-age=0, public"
check_cache_control "/legislator/rcdeeds.json" "max-age=0, public"

# Errors aren't cached: a bad request, and a bill that doesn't exist.
check_cache_control "/bill/2025/hb99999.json" "none"
check_cache_control "/bill/2025/hb9999.json" "none"

if [ "$ERRORED" = true ]; then
    exit 1
fi

echo ""
echo "All API tests passed!"
