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
- Next.js App Router
- TypeScript
- Tailwind CSS
- ESLint
- Prisma configured for PostgreSQL
- Vitest
- Playwright
- npm lockfile

Create only a minimal application shell:
- product-facing name: **EmprendimientoOS**
- repository name remains **RecetarioDigital**
- mobile-first root layout
- one polished placeholder Home screen that communicates the product purpose
- responsive behavior suitable for phone and desktop
- accessibility-conscious semantic HTML
- no real business CRUD yet

Add foundational project structure for future domain modules without speculative abstractions.

## PWA bootstrap
Add:
- a valid web app manifest
- mobile viewport/theme metadata
- installable-app metadata/icons only if they can be generated without introducing arbitrary branding

Do not implement complex offline synchronization in this task.

## Database bootstrap
Configure Prisma for PostgreSQL and provide an initial schema containing only the minimum shared primitives needed to prove configuration.

Do **not** prematurely implement the full domain schema in TSK-001.

The application must be able to build without a live production database connection.

## Testing bootstrap
Provide:
- at least one Vitest test proving the test runner works
- at least one Playwright smoke test targeting a mobile viewport or mobile project
- scripts for lint, typecheck, test, e2e, and build

## Git hygiene
Preserve existing:
- `.env` ignore
- `.DS_Store` ignore

Extend `.gitignore` for:
- Node dependencies
- Next.js build output
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

## Acceptance criteria
- dependencies install successfully
- lint passes
- typecheck passes
- unit tests pass
- production build passes
- Playwright smoke test is structurally valid and runs where the local server/runtime permits
- `git diff --check` passes
- the resulting mobile Home screen is coherent at phone width
- no secret-bearing files are versioned
- changes remain inside the execution contract allowlist

Stop and report rather than expanding scope when a missing product decision would materially affect architecture.
