# TSK-007 C002 — Order Operations Page Title E2E Corrective

Operate as a bounded test-only corrective for TSK-007.

## Classified failure
After C001:
- Pest: 65 tests / 583 assertions PASS
- TypeScript: PASS
- Vite production build: PASS
- git diff --check: PASS
- path policy: PASS
- Playwright reaches the post-save order operations page at both 320px and 390px.

The only observed failure is:
`expect(page).toHaveTitle('Cobro y entrega')`

Actual application title:
`Cobro y entrega · EmprendimientoOS`

This is expected application shell behavior and not a product defect.

## Required mutation
Modify only:
- `e2e/order-operations.spec.ts`

Keep the assertion semantically strict for the page title, but allow the existing application suffix. Prefer a regex anchored at the beginning, e.g. equivalent to:
`/^Cobro y entrega(?: · EmprendimientoOS)?$/`

Do not:
- change production code;
- change routes, services, controllers, models, migrations, UI components or configs;
- remove the title assertion;
- weaken unrelated assertions;
- disable Playwright parallelism.

## Validation
Run the complete TSK-007 validation matrix.
If another failure remains, preserve evidence and stop for classification.
