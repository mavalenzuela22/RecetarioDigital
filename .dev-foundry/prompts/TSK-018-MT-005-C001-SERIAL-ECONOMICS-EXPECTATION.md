# TSK-018 MT-005 C001 — align serial Today economics expectation

This is a TEST-ONLY corrective. Do not change product code, domain behavior, product copy, or backend economics.

## Proven state

The MT-005 primary execution proved:
- TypeScript PASS.
- Production build PASS.
- TodayProductionTest PASS, including the new completed-fully-paid-day regression.
- Path policy PASS with 0 violations.
- git diff --check PASS.
- Mobile Playwright 320px completed-day scenario PASS.
- The 390px scenario reaches the correct completed-day UI:
  - "Todo lo de hoy está completo." is visible;
  - "No tienes pedidos para hoy." is absent;
  - balance is $0.00 MXN.

The only failure is an incorrect E2E expectation for day economics at 390px.

## Root cause

`test.describe.configure({ mode: 'serial' })` intentionally keeps database state across these Today/Production tests.

The first mobile scenario at 320px creates and fully completes a $125.00 day contribution with:
- revenue $125.00
- cost $21.00
- profit $104.00

The second scenario at 390px creates an equivalent additional completed order in the same business date and same serial database state.

Therefore the correct aggregate Today economics at 390px are exactly:
- revenue $250.00
- cost $42.00
- profit $208.00
- balance $0.00

The observed product output matches this exactly. The test incorrectly expected the first scenario's single-order values again.

## Required correction

Modify ONLY `e2e/today-production.spec.ts`.

Keep the 320px assertions exact:
- $125.00 MXN revenue
- $21.00 MXN cost
- $104.00 MXN profit

For the subsequent 390px serial scenario, assert the exact cumulative values:
- $250.00 MXN revenue
- $42.00 MXN cost
- $208.00 MXN profit

Also keep/assert $0.00 MXN balance in the completed state if practical without ambiguity.

Do not weaken to generic non-zero checks.
Do not reset database state.
Do not change serial mode.
Do not change any product file.

Preserve all other Today/Production assertions and inherited dirty state.

## Validation

Run:
- docker compose run --rm app composer install --no-interaction --prefer-dist
- npm ci
- npx tsc --noEmit
- npm run build
- docker compose run --rm app ./vendor/bin/pest tests/Feature/TodayProductionTest.php
- clean port 18080
- npx playwright test e2e/today-production.spec.ts --project=mobile --workers=1
- git diff --check

PASS only if every command passes and path policy has zero violations.
