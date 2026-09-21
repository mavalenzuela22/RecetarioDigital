# TSK-016 C003 — Pest TestCase Deduplication

This is a bounded corrective for TSK-016 after C002.

## Classified failure

C002 successfully completed the Socialite dependency work:
- composer.lock was refreshed;
- laravel/socialite resolved to v5.31.0;
- composer validate/install PASS;
- mobile Playwright PASS;
- release build/verify PASS;
- npm audit PASS with 0 vulnerabilities;
- TypeScript and Vite 8 PASS;
- Percona migrations PASS.

The only failing gate is the Pest suite in both SQLite and Percona, and both fail before any test executes with the exact same Pest configuration error:

`Test case Tests\\TestCase can not be used. The folder tests/Feature/AccessAdministrationTest.php already uses the test case Tests\\TestCase`

Repository observation confirms:
- tests/Pest.php already contains `uses(Tests\\TestCase::class)->in('Feature');`;
- tests/Feature/AccessAdministrationTest.php redundantly declares `Tests\\TestCase::class`;
- tests/Feature/GoogleAuthTest.php redundantly declares `Tests\\TestCase::class`.

## Authorized mutation

Change only:
- tests/Feature/AccessAdministrationTest.php
- tests/Feature/GoogleAuthTest.php

In each file, remove the redundant `Tests\\TestCase::class` from the file-local `uses(...)` declaration while preserving `Illuminate\\Foundation\\Testing\\RefreshDatabase::class`.

Expected pattern:
`uses(Illuminate\\Foundation\\Testing\\RefreshDatabase::class);`

Do not modify product code, auth logic, schema, routes, UI, Composer constraints, composer.lock, or test assertions.

## Required validation

Run the complete TSK-016 matrix:
- Composer validate/show/install in Docker;
- full Pest against SQLite;
- full Percona 8.4 gate;
- npm ci and audit low;
- TypeScript;
- Vite 8 build;
- full mobile Playwright;
- release ZIP build/verify;
- git diff --check.

If another real test failure appears after Pest is able to load, report that new failure exactly; do not broaden scope automatically inside this execution.
