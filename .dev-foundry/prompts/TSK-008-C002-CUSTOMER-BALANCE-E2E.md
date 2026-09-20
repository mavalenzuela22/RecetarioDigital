# TSK-008 C002 — Scope Final Balance Assertion to Customer Collection Row

Operate as a bounded test-only corrective for TSK-008.

## Classified failure
After C001:
- path policy PASS;
- Docker build PASS;
- Composer install PASS;
- npm ci PASS;
- Pest: 71 tests / 660 assertions PASS;
- TypeScript PASS;
- Vite PASS;
- git diff --check PASS;
- only Playwright failed.

Observed failure:
`getByText('$50.00 MXN', { exact: true })` is ambiguous on Home because the same correct value appears in:
- the customer's collection row;
- expected revenue;
- total balance.

This is a test defect, not a product defect.

## Required mutation
Modify only:
- `e2e/today-production.spec.ts`

Replace the global amount assertion with a strict assertion scoped to the unique customer's collection link/row. The assertion must verify that after the order is marked ready:
- the customer remains present in "Por cobrar hoy";
- that same actionable collection row still shows `$50.00 MXN`.

Do not:
- change product code;
- remove the balance assertion;
- use `.first()` merely to silence ambiguity when a semantic customer-scoped locator can be used;
- change global Playwright config or parallelism;
- weaken any existing order-operations test.

## Validation
Run the full TSK-008 validation matrix.
If anything else fails, preserve evidence and stop for classification.
