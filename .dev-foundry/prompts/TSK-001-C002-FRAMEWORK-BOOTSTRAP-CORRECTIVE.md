# TSK-001 C002 — Framework Bootstrap and Frontend Dependency Corrective

Operate as the bounded corrective implementation executor for TSK-001.

## Retained passing evidence from C001
Do not undo these passing results:
- PHP namespace separators in `artisan` and `bootstrap/app.php` are corrected.
- `package-lock.json` now exists.
- `npm ci` passes.
- `npx playwright test --list` passes.
- `git diff --check` passes.

## Named findings to correct

### C002-F001 — Laravel core providers were replaced
Observed `config/app.php` defines:
- `providers` as only `App\\Providers\\AppServiceProvider::class`
- `aliases` as an empty array

This removes Laravel's default provider and facade registrations and causes:
`Target class [files] does not exist.`

Correct `config/app.php` using the Laravel 12 framework-native pattern:
- import `Illuminate\\Support\\ServiceProvider`
- import `Illuminate\\Support\\Facades\\Facade`
- use `ServiceProvider::defaultProviders()` and include the application provider without duplicating it
- use `Facade::defaultAliases()`
- preserve the existing EmprendimientoOS name, locale, timezone, key, maintenance, and previous_keys settings unless framework correctness requires an exact small adjustment

### C002-F002 — axios missing from frontend dependency graph
Observed Vite failure:
`Rollup failed to resolve import "axios"` from the Inertia/Precognition dependency graph.

Add `axios` as a direct project dependency in the appropriate dependency section and regenerate `package-lock.json`.

## Hard boundaries
Do not:
- alter Home UX
- add product/domain features
- change architecture
- change Docker design
- upgrade unrelated packages
- perform npm audit remediation
- modify governance documents
- modify files unrelated to the two named findings

## Validation
All must pass:
- `docker compose run --rm app composer install --no-interaction --prefer-dist`
- `npm ci`
- `docker compose run --rm app php artisan test`
- `npm run build`
- `npx playwright test --list`
- `git diff --check`

Stop after correcting the named findings.
