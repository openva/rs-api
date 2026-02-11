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
check "/videos.json" "any(.[]; .date == \"2024-01-09\")" 'true'
check "/videos.json" "all(.[]; .is_indexed | type == \"boolean\")" 'true'

# Video endpoint tests (single video)
check "/video/14569.json" ".date" '"2024-01-12"'
check "/video/14569.json" ".path" '"https://archive.org/details/rs-senate-20240112-senate-regular-session"'
check "/video/14569.json" ".chamber" '"senate"'
check "/video/14569.json" ".title" '"Senate Regular Session"'
check "/video/14569.json" ".width" '"640"'

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

if [ "$ERRORED" = true ]; then
    exit 1
fi

echo ""
echo "All API tests passed!"
