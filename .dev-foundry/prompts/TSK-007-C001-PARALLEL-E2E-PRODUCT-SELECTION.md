# TSK-007 C001 — Parallel Order E2E Product Selection Corrective

Operate as a bounded test-only corrective for TSK-007.

## Classified failure
Initial TSK-007 implementation validation shows:
- Pest: 65 tests / 583 assertions PASS
- TypeScript: PASS
- Vite production build: PASS
- git diff --check: PASS
- path policy: PASS
- only Playwright order operations failed.

Root cause is in `e2e/order-operations.spec.ts`:
the helper `captureOrder()` selects `Producto para agregar` with `selectOption({ index: 1 })`.

The 320px and 390px tests run in parallel against the same isolated E2E database. Each worker creates a uniquely named recipe/product. Therefore option index 1 is not guaranteed to be the product created by the current worker. When the other worker's product is selected, the following locator `Cantidad <current recipe>` does not exist.

This is a test defect, not a product defect.

## Required mutation
Modify only:
- `e2e/order-operations.spec.ts`

Select the exact product belonging to the current test by its unique recipe/product label, using the helper's `recipe` argument and the known rendered product option label.

Do not:
- disable Playwright parallelism;
- change production code;
- change routes, migrations, services, controllers, UI components or configs;
- weaken assertions.

## Validation
Run the complete TSK-007 validation matrix.

If another failure remains after this fix, preserve evidence and stop for classification.
