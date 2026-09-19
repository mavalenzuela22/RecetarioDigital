# TSK-001 — Bootstrap Application Shell

Operate as the bounded implementation executor for the greenfield RecetarioDigital repository.

## Goal
Create the minimal technical foundation for **EmprendimientoOS v1.0** without implementing business modules yet.

Read before changing anything:
- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- `.dev-foundry/profiles/project-operating-profile-v2.yaml`

## Required implementation
Create a production-quality baseline using:
- Laravel
- PHP compatible with the production PHP 8.3 runtime
- Inertia.js
- React
- TypeScript
- Tailwind CSS
- Eloquent ORM
- MySQL-compatible database configuration
- Pest
- Playwright
- Composer lockfile
- npm lockfile for frontend build tooling

Production must not require a persistent Node.js runtime.

Create only a minimal application shell:
- product-facing name: **EmprendimientoOS**
- repository name remains **RecetarioDigital**
- mobile-first root layout
- one polished placeholder Home screen that communicates the product purpose
- responsive behavior suitable for phone and desktop
- accessibility-conscious semantic HTML
- no real business CRUD yet

Add only the project structure needed for this shell and the next bounded domain task. Do not create speculative framework layers.

## PWA bootstrap
Add:
- a valid web app manifest
- mobile viewport/theme metadata
- installable-app metadata
- icons only if they can be created without inventing final branding

Do not implement complex offline synchronization.

## Database bootstrap
Configure Laravel for a MySQL-compatible production database.

Use environment-based database settings and never version credentials.

Provide only the minimum initial migration/schema needed to prove the Laravel database layer is configured. Do not prematurely implement the full EmprendimientoOS domain schema in TSK-001.

The application must be able to install dependencies, run tests, and build frontend assets without a live production database connection.

## Testing bootstrap
Provide:
- at least one Pest test proving the backend test runner works
- at least one Playwright smoke test using a phone-sized/mobile project
- scripts or documented commands for backend tests, frontend build, Playwright, and production asset build

## Local development toolchain
Docker is available on the development Mac and may be used to provide the local PHP/Composer toolchain.

Prefer a reproducible containerized development/test workflow over installing PHP or Composer directly on the macOS host.

The task may add the minimum Docker development files required to:
- run PHP compatible with production PHP 8.3
- run Composer
- execute Laravel/Pest commands
- build/test the application reproducibly

Use a root `compose.yaml` with a service named `app` for the PHP/Composer toolchain so validation can invoke deterministic commands through `docker compose run --rm app ...`.

Keep Node/npm on the host when practical; containerizing Node is not required.

Docker is a development and validation tool only. Do not make production deployment depend on Docker.

## Deployment compatibility
The resulting application must remain compatible with:
- IIS-hosted PHP 8.3 application execution
- MySQL-compatible Percona Server 8.4
- prebuilt frontend assets

Do not require in production:
- Docker
- a long-running Node.js server
- PostgreSQL
- Prisma
- Next.js server runtime

Do not attempt a live GoDaddy deployment in this task.

## Git hygiene
Preserve existing:
- `.env` ignore
- `.DS_Store` ignore

Extend `.gitignore` for:
- Composer/vendor dependencies
- Node dependencies
- Vite/build output where generated
- Laravel runtime/local artifacts
- test artifacts
- Playwright runtime output
- environment-local files
- ephemeral Foundry runtime/evidence directories where appropriate

Do not ignore versioned governance artifacts under:
- `.dev-foundry/profiles`
- `.dev-foundry/prompts`
- `.dev-foundry/execution-contracts`
- `docs`

## Hard boundaries
Do not:
- add business feature CRUD
- add inventory
- add accounting/tax functionality
- implement authentication provider integration
- commit secrets
- modify governance documents
- create a native mobile application
- create microservices
- introduce PostgreSQL, Prisma, or Next.js

## Acceptance criteria
- Composer dependencies install successfully
- npm dependencies install successfully
- Laravel application key/runtime setup for local test execution is documented or automated without committing secrets
- Pest tests pass
- production frontend asset build passes
- Playwright smoke test is structurally valid and can list/run where the local runtime permits
- `git diff --check` passes
- the resulting Home screen is coherent at phone width
- no secret-bearing files are versioned
- changes remain inside the execution-contract allowlist

Stop and report rather than expanding scope when a missing product decision would materially affect architecture.
