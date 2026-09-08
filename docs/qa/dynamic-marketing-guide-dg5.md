# DG5 — Dynamic Marketing Guide QA

Date: 2026-09-08. Branch: `test/dynamic-marketing-guide-final-qa`.
Base: `4a2dd27` (DG4). Status: **not ready for merge / production sign-off**
until the outstanding verification below is resolved.

## Confirmed defects and fixes

1. A previously loaded admin could edit after their stored role changed to user.
   The editor and content mutation service now read the actor's current role
   from the database. Eight regression cases cover draft creation/reuse,
   section/block edits, ordering, addition/removal, and the Livewire entry point.
2. Reordering blocks with positions starting at 1001 collided with the fixed
   temporary offset and raised a unique-key exception. Temporary positions
   now start beyond the parent's actual maximum. The regression also checks
   final ordering and preservation of block data.
3. Disabling the hero section left the logo pointing to a missing anchor.
   The logo now points to the first active section, verified in preview and public.
4. Accepted CTA anchors such as `#missing]` and `#123` caused selector errors.
   Anchor lookup now uses the literal element ID instead of a CSS selector.
5. Guides shorter than the viewport produced a `NaN%` progress value.
   Progress now remains finite and within 0–100%.

The regression cases were run against the pre-fix implementation and reproduced
the defects before the fixes were applied. No packages, migrations, generated
assets, or unrelated application behavior were changed.

## Coverage

| Area | Automated evidence |
| --- | --- |
| Admin editor | Admin access; cloning/reusing the latest published version; section/block editing, activation and ordering; nested content; locked block types; no raw JSON textarea; stale actor and forged published child IDs |
| Preview | Admin-only active draft; no clone on missing draft; unchanged temporary access/tracking; private/no-cache/noindex response; 19 invalid draft cases return safe 422 responses |
| Publish | Atomic archival and publication; audit fields; full rollback after a simulated write failure; current published retained on failure; new drafts clone the latest publication; stale published mutation rejection |
| Public | All ten block types; published-only rendering; inactive filtering; section/block ordering; sidebar/drawer group and anchor mapping; recipient and expiry from temporary access; safe static fallback with an existing draft |
| Security | Invalid/revoked 404; expired 410; lock-time status rechecks; tracking; 30/minute throttle; noindex, private/no-store/no-cache, no-referrer, nosniff, DENY and Permissions-Policy; no token/hash or storage API leakage in rendered guide HTML |
| JavaScript | Drawer open/close and navigation close; active navigation; FAQ toggle; CTA clicks; finite progress; nested reorder for six container types; editing CTA fields on an intro-only text block |

JavaScript checks execute the actual inline scripts using Node's built-in test
runner and a small DOM fixture. They do **not** replace browser rendering,
Alpine/Livewire integration, console inspection, or responsive layout checks.

## Verification results

MySQL preflight checked the bootstrapped application environment, default
connection, configured database, and `SELECT DATABASE()`: all matched
`testing`, `mysql`, `ticketing_test`, `ticketing_test`.
Existing tests that explicitly use isolated SQLite `:memory:` are retained;
the full-suite continuation was authorized after this exception was raised.
No development database reset was performed. PHPUnit runs are sequential.
The first Feature run exhausted PHP's default 128 MB limit in the existing
`SecureImageStorage` tests (exit 255). Comprehensive runs therefore use
`-d memory_limit=1G` for the test process only; application configuration is unchanged.

| Check | Result |
| --- | --- |
| Baseline MarketingGuide tests | PASS — 123 tests, 896 assertions, exit 0 |
| Final MarketingGuide tests | PASS — 137 tests, 1,233 assertions, exit 0 |
| JavaScript regression tests | PASS — 13 tests, exit 0 |
| All Feature tests | PASS — 1,146 tests, 7,061 assertions, exit 0; peak memory 154 MB |
| Full PHPUnit | PASS — 1,147 tests, 7,062 assertions, exit 0; peak memory 154 MB |
| Pint on changed PHP files | PASS, exit 0 |
| Repository-wide Pint `--test` | FAIL, exit 1 — existing formatting findings in 132 files; none in the changed PHP files. Left unchanged to preserve scope. |
| `git diff --check` | PASS, exit 0 |

Commands (after the database preflight):

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='ticketing_test'
php vendor/phpunit/phpunit/phpunit --filter MarketingGuide
php -d memory_limit=1G vendor/phpunit/phpunit/phpunit --testsuite Feature
php -d memory_limit=1G vendor/phpunit/phpunit/phpunit
node --test tests/MarketingGuideInteractions.test.mjs
php vendor/bin/pint --test
php vendor/bin/pint --test app/Livewire/Admin/MarketingGuideContentEditor.php app/Services/MarketingGuide/MarketingGuideContentService.php tests/Feature/MarketingGuideFinalQaTest.php tests/Feature/MarketingGuidePreviewPublishTest.php
git diff --check
```

## Outstanding UI and production verification

The configured Browser runtime initialized, but selection returned
`No browser is available` and browser discovery returned an empty list.
A later connection retry after continuation returned the same error.
Consequently none of the following are marked visually verified:

| Page | Desktop | Mobile |
| --- | --- | --- |
| `/admin/marketing-guide` | Blocked — browser unavailable | Blocked — browser unavailable |
| `/admin/marketing-guide/content` | Blocked — browser unavailable | Blocked — browser unavailable |
| Preview Draft | Blocked — browser unavailable | Blocked — browser unavailable |
| `/guide/{token}` | Blocked — browser unavailable | Blocked — browser unavailable |

Once a browser is available, check sidebar/drawer, active navigation,
responsive layout, modal editing, Preview/Publish controls, FAQ and CTA;
confirm no horizontal overflow and no console errors on all four pages.
Use isolated QA data for mutations. No production deployment or live production
verification was performed, and this report does not claim production sign-off.
