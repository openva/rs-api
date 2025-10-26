# PHP 8.4 Modernization Plan

## Goals & Constraints
- [ ] Keep today’s API surface working while making the minimal code changes required for PHP 8.4 compatibility.
- [ ] Avoid modifying `~/Documents/Git/richmondsunlight.com`; capture needed follow-up for manual action.
- [ ] Validate revisions with PHP’s built-in linter (`php -l`); no new automated tests.
- [ ] Prefer pragmatic fixes over large refactors, without introducing security regressions.
- [ ] Preserve the existing JSON payloads emitted by every API endpoint.

## Baseline & Tooling
- [x] Confirm local PHP CLI: `php -v` → 8.3.20 (closest available to PHP 8.4).
- [x] Keep syntax check command ready: `find htdocs -name '*.php' -print0 | xargs -0 -n1 php -l`.
- [x] Use `rg "mysql_" htdocs` and targeted reviews to identify deprecated syntax.

## Remediation Inventory (this repository)
- [ ] Legacy `mysql_*` API usage  
  - [x] `htdocs/1.0/bill.php:34-66`
  - [x] `htdocs/1.0/bills.php:31-67`
  - [x] `htdocs/1.0/code-section.php:30-63`
  - [x] `htdocs/1.0/photosynthesis.php:27-66`
  - [x] `htdocs/1.0/legislator.php:26-114`
- [ ] Error suppression and unsanitized request access  
  - [x] `htdocs/1.0/legislator.php:26-52`
  - [x] `htdocs/1.0/photosynthesis.php:27-66`
- [ ] MySQLi connection handling bugs  
  - [x] `htdocs/1.1/photosynthesis.php:46` connection argument and `mysql_fetch_assoc`.
  - [x] `htdocs/1.1/code-section-video.php:27-55` connection handle lifecycle.
  - [x] `htdocs/1.1/legislators.php:42-58` WHERE clause balancing.
- [x] Array/object misuse in clip deduplication (`htdocs/1.1/code-section-video.php:79-95`)
- [x] Uninitialized accumulators  
  - [x] `htdocs/1.1/code-section.php:41-63`
  - [x] `htdocs/1.1/code-section-video.php:64-95`
- [ ] Tooling configured for PHP 5 (`rector.php` downgrade sets)

## Remediation Plan (this repository)
- [x] Align tooling and guardrails  
  - [x] Update or replace `rector.php` so automated refactors stop downgrading syntax; verify no PHP 5-only scripts remain.
  - [x] Document linting expectations (PHP 8.4 target using `php -l`, with PHP 8.3 locally until 8.4 CLI is available).
- [x] Modernize the 1.0 endpoints  
  - [x] Swap `connect_to_db()` for `Database::connect_mysqli()` and persist `$db`.
  - [x] Replace every `mysql_*` call with `mysqli_*` equivalents.
  - [x] Replace legacy escaping with `mysqli_real_escape_string` or prepared statements.
  - [x] Remove `@` suppression and add input validation via `filter_input` or regex.
  - [x] Initialize collections before population and harmonize JSON error responses.
- [x] Stabilize the 1.1 endpoints  
  - [x] Ensure `mysqli_query` always receives `$db` and eliminate lingering `mysql_*`.
  - [x] Initialize accumulators and make clip deduplication array-safe.
  - [x] Repair `htdocs/1.1/legislators.php` WHERE clause and retain connection handles.
  - [x] Ensure `code-section-video.php` maintains the `$db` handle for queries.
- [x] Consistency cleanup  
  - [x] Standardize manual HTTP status headers to rely on the server protocol helper.
  - [x] Remove unused includes and initialize any leftover accumulators to avoid notices.
- [ ] Validation & regression checks  
  - [x] Re-run `php -l` across `htdocs` after each batch of changes.
- [ ] Spot-check key endpoints with read-only requests against staging data (pending staging access).
- [ ] Run optional tooling (phpstan, etc.) in PHP 8 mode once syntax issues are fixed (manual follow-up).

## Remediation Plan (richmondsunlight.com includes) — for manual follow-up
- [ ] Re-run PHP 8.4 linting on `~/Documents/Git/richmondsunlight.com/htdocs/includes` once this repository is updated.

## Validation Strategy
- [x] Run `find htdocs -name '*.php' -print0 | xargs -0 -n1 php -l` under PHP 8.4 (use 8.3 locally until the 8.4 CLI is available).
- [ ] Invoke representative endpoints (bill, legislator, photosynthesis, code-section, vote) against staging data to confirm headers and JSON structure.
- [ ] Monitor PHP error logs post-deployment for legacy notices once error suppression is removed.
