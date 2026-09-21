# TSK-017 C002 — stale monetary E2E expectations and deterministic full closure

Operate as a bounded corrective for TSK-017 closure only.

Observed closure failures:
1. Backend SQLite and Percona commands failed because a previous release-build validation intentionally removed Composer dev dependencies from the shared workspace. This is an environment sequencing issue; restore dev dependencies from composer.lock before backend tests.
2. Six Playwright failures are stale test expectations that still require fixed six-decimal ordinary currency after MT-006 intentionally changed ordinary currency to 2 decimals and specialized unit costs to trimmed meaningful precision.
3. One Today/Production mobile failure occurred during the fully-parallel suite against one shared SQLite database. The suite uses one Playwright webServer/database, so closure must run deterministically with one worker rather than changing product behavior or global config.

Allowed test mutations:
- e2e/ingredient-purchase.spec.ts
- e2e/product-history.spec.ts
- e2e/recipe-costing.spec.ts

Only update stale monetary expectations to the accepted MT-006 display policy:
- ingredient specialized unit cost 0.042000 -> 0.042
- purchase-card specialized unit cost 0.030000 -> 0.03
- historical/product ordinary money 4.200000 -> 4.20 and 15.800000 -> 15.80
- recipe ordinary money 18.440000 -> 18.44, 1.536667 -> 1.54, 1.844000 -> 1.84

Do not alter assertions unrelated to display precision.
Do not change application product code, pricing/costing arithmetic, Playwright config, dependencies/manifests, auth, routes, models, migrations, or business behavior.
Do not create or modify governance files and do not invoke Foundry Runner from inside the executor.

Validation sequencing:
- composer install --no-interaction --prefer-dist to restore dev dependencies from lock
- full SQLite Pest
- full Percona gate
- concurrent-admin Percona harness
- npm ci
- npm audit at low severity
- TypeScript noEmit
- Vite production build
- stale-port cleanup
- full Playwright mobile with --workers=1
- release build + verify LAST because it strips dev Composer packages
- git diff --check

PASS only if all validation commands pass and path policy has zero violations.