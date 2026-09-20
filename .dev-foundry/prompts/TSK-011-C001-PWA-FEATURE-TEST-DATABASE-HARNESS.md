# TSK-011 C001 — PWA Feature Test Database Harness

Operate as a bounded corrective after the classified TSK-011 validation failure.

## Classification

The initial TSK-011 implementation completed with:
- path policy PASS;
- Docker build PASS;
- Composer install PASS;
- npm ci PASS;
- TypeScript PASS;
- Vite PASS;
- full Playwright mobile suite PASS (18/18);
- git diff check PASS.

Only the two new `Tests\Feature\PwaTest` cases failed.

Both failures occurred in `tests/TestCase.php:14` before any PWA assertion:
`SQLSTATE: no such table: users`.

TSK-010 established a shared authenticated Feature-test harness in `Tests\TestCase::setUp()`, which creates the default test user after Laravel database-reset traits execute.

The new `PwaTest.php` omitted `RefreshDatabase`, so its in-memory SQLite schema did not exist when the shared harness attempted to create the authenticated user.

This is a test-harness defect. There is no evidence of a PWA runtime defect.

## Required repair

Modify only `tests/Feature/PwaTest.php`:
- import `Illuminate\Foundation\Testing\RefreshDatabase`;
- apply `uses(RefreshDatabase::class);`.

Do not alter the assertions unless a subsequent concrete failure proves an assertion defect.
Do not modify `tests/TestCase.php`.
Do not modify manifest, service worker, icons, Blade metadata, app bootstrap or E2E code.

## Validation

Run the complete TSK-011 matrix:
1. docker compose build app
2. composer install
3. npm ci
4. full php artisan test
5. npx tsc --noEmit
6. npm run build
7. known E2E container cleanup
8. full Playwright mobile
9. git diff --check

Stop and preserve evidence on any remaining failure.
