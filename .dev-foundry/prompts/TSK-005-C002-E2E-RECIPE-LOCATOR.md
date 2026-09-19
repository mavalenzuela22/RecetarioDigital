# TSK-005 C002 — Parallel E2E Recipe Locator Corrective

Operate as a bounded test-only corrective for TSK-005.

## Classified failure
After C001:
- Pest: 52 tests / 437 assertions PASS
- TypeScript: PASS
- Vite build: PASS
- git diff --check: PASS
- Playwright reached the Products list but failed because the two viewport tests run in parallel against the same isolated SQLite database.

Each worker creates its own uniquely named recipe. The E2E then uses a generic locator for "Configurar producto de esta receta", which matches both unconfigured recipes and violates Playwright strict mode.

This is a test defect, not a product defect.

## Required mutation
Modify only:
- `e2e/product-pricing.spec.ts`

Scope the product-configuration link to the recipe created by the current test using its unique recipe name/suffix, or another equally deterministic locator grounded in the rendered row.

Do not change production code, routes, database schema, product services, CSS, manifests, Docker or Playwright configuration.
Do not disable parallelism and do not weaken the assertions.

## Validation
Run the complete TSK-005 validation matrix.

If another failure remains after this locator fix, preserve evidence and stop for classification.
