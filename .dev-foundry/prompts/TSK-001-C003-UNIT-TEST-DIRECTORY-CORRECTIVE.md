# TSK-001 C003 — Missing Unit Test Directory Corrective

Operate as the bounded corrective implementation executor for TSK-001.

## Retained passing evidence from C002
Do not disturb:
- Composer install PASS
- npm ci PASS
- Laravel package discovery PASS
- Vite production build PASS
- Playwright test listing PASS
- git diff --check PASS
- path policy PASS

## Named finding
`php artisan test` exits because the configured test suite references:
`tests/Unit`
and that directory does not exist.

## Required corrective
Make the smallest valid correction so Laravel/Pest can run the configured test suites.

Preferred options, in order:
1. create `tests/Unit/.gitkeep` if an empty directory is sufficient; or
2. create one minimal, meaningful unit smoke test if the test runner requires a PHP file.

Do not modify application code, dependencies, UX, Docker, configuration, or existing tests unless strictly required by this single finding.

## Validation
All must pass:
- `docker compose run --rm app composer install --no-interaction --prefer-dist`
- `npm ci`
- `docker compose run --rm app php artisan test`
- `npm run build`
- `npx playwright test --list`
- `git diff --check`

Stop after this finding is corrected.
