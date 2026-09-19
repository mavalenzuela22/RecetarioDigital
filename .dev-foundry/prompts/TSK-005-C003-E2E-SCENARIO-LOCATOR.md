# TSK-005 C003 — E2E Scenario Article Locator Corrective

Operate as a bounded test-only corrective for TSK-005.

## Classified failure
After C002:
- Pest: 52 tests / 437 assertions PASS
- TypeScript: PASS
- Vite build: PASS
- git diff --check: PASS
- Playwright reaches the product detail and scenario selector.

The remaining failure is a locator mismatch:
- production renders the scenario result as `<article aria-label="Resultado del escenario">`;
- an HTML `article` has the accessible role `article`, not `region`;
- the E2E incorrectly queries `getByRole('region', { name: 'Resultado del escenario' })`.

This is a test defect. Do not modify production markup solely for the test.

## Required mutation
Modify only:
- `e2e/product-pricing.spec.ts`

Use a locator consistent with the actual accessible element (for example the `article` role/name or an equivalent deterministic selector) while preserving the exact expected scenario price and margin assertions.

Do not weaken any monetary, confirmation, current-price, cost-breakdown, or overflow assertions.

## Validation
Run the complete TSK-005 validation matrix.

If another failure remains, preserve evidence and stop for classification.
