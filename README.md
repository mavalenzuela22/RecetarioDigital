# EmprendimientoOS

EmprendimientoOS is the mobile-first operating shell for a small food business. The repository remains named `RecetarioDigital` for continuity with the project record.

This baseline uses Laravel 12, PHP 8.3, Inertia, React, TypeScript, Tailwind CSS, Eloquent, Pest, and Playwright. Frontend assets are compiled ahead of deployment; IIS production does not need Node.js or Docker at runtime.

## Local setup

Docker provides the PHP/Composer toolchain. Node.js/npm remain on the host for frontend tooling.

```sh
docker compose build app
docker compose run --rm app composer install --no-interaction --prefer-dist
npm ci
```

For local Laravel commands, create a local `.env` from the values appropriate to the environment, then generate a local-only key without committing it:

```sh
docker compose run --rm app php artisan key:generate --show
```

Copy the displayed value into `.env` as `APP_KEY=...`. Database settings are read from `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`; production values belong only in the hosting environment. The test suite uses an in-memory SQLite setting from `phpunit.xml` and does not need a live production database.

## Validation commands

```sh
# Backend
docker compose run --rm app php artisan test

# Production frontend build
npm run build

# List and run the mobile Playwright project
npx playwright test --list
npx playwright test

# Whitespace check
git diff --check
```

Playwright starts Laravel through the PHP 8.3 container when `PLAYWRIGHT_BASE_URL` is not set. To target an already-running local or staging instance, set `PLAYWRIGHT_BASE_URL`.

The first migration is deliberately limited to Laravel's runtime user/session foundation. No EmprendimientoOS business tables or CRUD are included in this bootstrap task.

## Production release for IIS

Build a release ZIP locally with PHP/Composer and Node/npm available; the builder installs production Composer dependencies, compiles `public/build/`, and keeps secrets and persistent storage outside the package:

```sh
bash scripts/build-release.sh --output-dir artifacts/releases/production
bash scripts/verify-release.sh artifacts/releases/production/emprendimientoos-<commit-sha>.zip
```

Follow [the IIS deployment runbook](docs/PRODUCTION-DEPLOYMENT-IIS.md) for environment, persistent storage, deploy, smoke, and rollback invariants. After a live deployment, the read-only smoke contract is:

```sh
bash scripts/smoke-release.sh https://your-production-host.example
```
