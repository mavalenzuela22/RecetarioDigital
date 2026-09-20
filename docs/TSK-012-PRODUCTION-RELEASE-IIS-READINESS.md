# TSK-012 — Production Release & IIS Deployment Readiness

## Objective

Make EmprendimientoOS v1 repeatably deployable to the declared production target:

- IIS / Windows Hosting
- PHP 8.3
- MySQL-compatible Percona 8.4
- one Laravel deployable
- no persistent Node.js runtime

This task is release engineering only. It does not add product features or change domain behavior.

## Authority

- `docs/ARCHITECTURE-v1.md`
  - simple deployment to existing GoDaddy Windows Hosting / IIS;
  - PHP 8.3 production;
  - MySQL-compatible Percona;
  - frontend assets built before deployment;
  - production must not require a persistent Node/Vite process.
- `README.md`
- current Laravel runtime configuration.
- TSK-010 private recipe-image storage and authenticated delivery.

## Required deliverables

### 1. Repeatable release builder

Create `scripts/build-release.sh`.

The script must build a production-ready ZIP for upload/extraction to an IIS-hosted Laravel release directory.

Default behavior:
- fail on a dirty tracked worktree;
- permit an explicit `--allow-dirty` override for governed validation only;
- accept an optional output directory;
- derive/record the current git commit SHA;
- run production frontend build from a clean npm install;
- install Composer production dependencies with `--no-dev --optimize-autoloader --no-interaction --prefer-dist`;
- stage the deployable into a temporary release directory;
- create a ZIP plus SHA-256 checksum and human-readable release manifest;
- never print or package secrets.

The production package must include:
- application PHP source;
- `vendor/`;
- Laravel bootstrap/config/database migrations;
- compiled `public/build/`;
- public PWA assets and `public/web.config`;
- Blade/resources needed at runtime;
- Artisan/composer metadata needed by Laravel.

The production package must exclude:
- `.env` and every `.env.*`;
- `.git/`;
- `.dev-foundry/`;
- `node_modules/`;
- tests/e2e/browser reports;
- Docker/local-development files;
- docs/governance source not required at runtime;
- npm/TypeScript/Vite tooling not required at runtime;
- local logs/sessions/cache/views;
- local database files;
- any mutable/private application data.

### 2. Persistent storage boundary

The release package must never contain or overwrite recipe/user runtime data under:
- `storage/app/private/**`
- `storage/app/public/**`

The staged release must still contain/create the Laravel writable directory skeleton needed for:
- `storage/app/private`
- `storage/framework/cache/data`
- `storage/framework/sessions`
- `storage/framework/views`
- `storage/logs`
- `bootstrap/cache`

The release manifest must state these are writable runtime directories.

### 3. Package verifier

Create `scripts/verify-release.sh`.

Given the produced ZIP, it must fail non-zero unless all required release invariants hold.

At minimum verify:
- `artisan`, `vendor/autoload.php`, `public/index.php`, `public/web.config`, `public/build/manifest.json`, `public/manifest.webmanifest`, `public/sw.js` exist;
- app/config/migrations/resources needed at runtime exist;
- no `.env` file is packaged;
- no `.git`, `.dev-foundry`, `node_modules`, tests, e2e, reports or Docker files are packaged;
- no file exists under `storage/app/private` or `storage/app/public`;
- no cached Laravel config/routes/views from the build environment are packaged under `bootstrap/cache/*.php`;
- writable directory skeleton exists;
- checksum file matches the ZIP.

Do not require application secrets to verify package structure.

### 4. Production deployment runbook

Create `docs/PRODUCTION-DEPLOYMENT-IIS.md`.

Document exact operator steps and invariants:

#### IIS layout
- extract each release to its own versioned directory;
- IIS site/application physical path points to that release's `public/` directory;
- URL Rewrite must be available for `public/web.config`;
- PHP 8.3 must be configured by hosting;
- `storage` and `bootstrap/cache` must be writable by the PHP/IIS identity.

#### Persistent environment
The package contains no `.env`.

Production configuration must be supplied outside the release package.
Document required/critical values without secrets:
- `APP_ENV=production`
- `APP_DEBUG=false`
- stable `APP_KEY` preserved across releases
- correct HTTPS `APP_URL`
- MySQL/Percona DB connection values
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- secure cookie enabled under HTTPS
- `FILESYSTEM_DISK=local`
- production-appropriate log channel/level.

Never provide real passwords or default production credentials.

#### Persistent private files
Recipe images live under `storage/app/private`.
A deployment must preserve that directory across releases.
Do not use `storage:link` as a substitute for private recipe images.

#### Deploy sequence
After release extraction/environment/persistent-storage wiring:
1. `php artisan migrate --force`
2. `php artisan optimize`
3. verify writable storage/cache
4. smoke test.

No Node command is required on the server.

#### Rollback
- preserve previous release directory;
- switch IIS physical path back to the previous `public/`;
- retain the same APP_KEY/environment/private storage;
- database migration rollback is not automatic; backup database before production migration and treat schema rollback separately.

### 5. Smoke script

Create `scripts/smoke-release.sh <base-url>`.

It must use curl and fail non-zero on unexpected results.

Without requiring credentials, verify:
- `GET /up` -> 200;
- `GET /login` -> 200;
- representative business route as guest redirects to login (accept the framework's normal redirect status);
- `/manifest.webmanifest`, `/sw.js`, favicon and PWA icons are publicly retrievable.

It must not submit passwords or mutate business data.

### 6. README operator entry point

Update README with a short Production release section linking the builder, verifier, runbook and smoke script.

### 7. Release artifact hygiene

Add generated release output under `artifacts/releases/` to `.gitignore`.

No generated ZIP/checksum/manifest may become tracked source.

## Safety / correctness invariants

- No application/domain/auth behavior changes.
- No migrations are added or modified.
- No dependencies are added.
- No secrets or environment values are committed.
- Do not package private recipe images.
- Do not package build-machine Laravel caches.
- Production never requires Node.js.
- IIS document root is `public/`, not repository root.
- Release package includes production Composer dependencies and compiled Vite assets.

## Validation

Required:
1. `docker compose build app`
2. `docker compose run --rm app composer install --no-interaction --prefer-dist`
3. `npm ci`
4. `docker compose run --rm app php artisan test`
5. `npx tsc --noEmit`
6. `npm run build`
7. full mobile Playwright suite
8. `bash scripts/build-release.sh --allow-dirty --output-dir artifacts/releases/tsk012-validation`
9. `bash scripts/verify-release.sh artifacts/releases/tsk012-validation/*.zip`
10. inspect ZIP list to prove exclusion/presence invariants
11. `bash -n scripts/build-release.sh scripts/verify-release.sh scripts/smoke-release.sh`
12. `git diff --check`

The smoke script is syntax/contract validated locally; it is not executed against production unless the operator separately supplies a live deployment URL.

## Path budget

Target <= 12 changed tracked paths; hard maximum 18.

## Non-goals

- deploy automatically to GoDaddy;
- manipulate live IIS configuration;
- create or rotate production secrets;
- run live production migrations;
- change business code;
- add CI/CD platform dependencies;
- add containers to production.

## Completion

TSK-012 closes when the release utility builds a verified safe ZIP, docs describe deploy/rollback precisely, the normal application validation matrix remains green, and governed promotion/reconciliation/cleanup completes.
