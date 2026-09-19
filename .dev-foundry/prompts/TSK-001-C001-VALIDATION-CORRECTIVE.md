# TSK-001 C001 — Bootstrap Validation Corrective

Operate as the bounded corrective implementation executor for TSK-001.

## Observed failures
The initial Laravel bootstrap implementation completed, but post-execution validation failed for two concrete reasons:

1. PHP namespace separators were lost in exactly these bootstrap files:
   - `artisan`
   - `bootstrap/app.php`

Observed invalid forms include:
- `IlluminateFoundationApplication`
- `SymfonyComponentConsoleInputArgvInput`
- `AppHttpMiddlewareHandleInertiaRequests`
- `IlluminateFoundationConfigurationExceptions`
- `IlluminateFoundationConfigurationMiddleware`

2. `package-lock.json` is absent, causing `npm ci` to fail. Consequently Vite and Playwright dependencies are unavailable to later validation commands.

## Required corrective
Make the smallest safe correction:

- restore valid PHP namespace separators in `artisan`
- restore valid PHP namespace separators in `bootstrap/app.php`
- preserve the existing Laravel 12 application shape
- generate a valid `package-lock.json` matching the existing `package.json`
- do not change dependency intent unless required solely to make the existing declared dependencies installable
- do not alter Home UX, architecture, domain scope, or add features

## Validation expectation
After correction, these must succeed:

- `docker compose run --rm app composer install --no-interaction --prefer-dist`
- `npm ci`
- `docker compose run --rm app php artisan test`
- `npm run build`
- `npx playwright test --list`
- `git diff --check`

Stop after these named findings are corrected. Do not perform adjacent cleanup.
