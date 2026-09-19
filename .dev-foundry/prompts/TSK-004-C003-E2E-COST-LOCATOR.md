# TSK-004 C003 — E2E Cost Region Locator Corrective

Operate as a bounded test-only corrective for TSK-004.

## Observed evidence
C002 full validation reached the recipe detail page successfully. Pest passed 41 tests / 349 assertions, TypeScript passed, Vite build passed, and git diff --check passed.

The remaining Playwright failure is a test locator mismatch:
- production detail page renders the cost section with `aria-label="Costo vigente"`;
- its visible heading remains `Costo de esta receta`;
- the E2E currently searches for a region named `Costo de esta receta`.

This is a test defect. Do not modify production UI for it.

## Required mutation
Modify only `e2e/recipe-costing.spec.ts` so the test locates the actual accessible cost region and continues asserting the expected visible batch cost and per-piece cost.

Do not weaken monetary assertions, navigation assertions, version assertions, or horizontal overflow assertion.

## No product mutation
Do not change app/, database/, resources/, routes/, docs/design/, manifests, Docker, or Playwright config.

## Validation
The contract will first remove the known stale E2E container and then run the complete TSK-004 matrix. Preserve truthful evidence. If a later assertion exposes a product defect, stop and report it rather than expanding scope.
