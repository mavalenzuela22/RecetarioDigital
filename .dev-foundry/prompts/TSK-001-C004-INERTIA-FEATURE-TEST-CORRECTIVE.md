# TSK-001 C004 — Inertia Feature Test Corrective

Operate as the bounded corrective implementation executor for TSK-001.

## Retained passing evidence from C003
Do not disturb:
- Composer install PASS
- npm ci PASS
- Laravel package discovery PASS
- Vite production build PASS
- Playwright test listing PASS
- git diff --check PASS
- path policy PASS

## Named finding
The feature test sends an incomplete simulated Inertia request:

`tests/Feature/HomeTest.php`

Current headers include:
- `X-Inertia: true`
- `X-Requested-With: XMLHttpRequest`

This causes a legitimate Inertia 409 response in the test harness.

## Required corrective
Make the smallest valid correction to the existing feature test:
- request `/` as a normal browser/document request
- assert HTTP 200
- preserve an Inertia assertion that the rendered component is `Home`

Do not modify application code, routes, middleware, dependencies, UX, Docker, or configuration.

## Validation
All must pass:
- `docker compose run --rm app composer install --no-interaction --prefer-dist`
- `npm ci`
- `docker compose run --rm app php artisan test`
- `npm run build`
- `npx playwright test --list`
- `git diff --check`

Stop after this single finding is corrected.
