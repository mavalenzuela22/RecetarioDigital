# TSK-018 MT-005 — Today completion state (AUD-16)

Implement the final bounded MT of TSK-018.

## Confirmed root cause

`OperationalSummary::today()` already returns:
- `has_orders` based on all non-cancelled financial orders for the local business date;
- actionable production, delivery, and collection lists;
- day economics based on those financial orders.

`resources/js/Pages/Home.tsx` currently decides the large empty state using only:
- production groups empty;
- deliveries empty;
- collections empty.

Therefore a day where orders DID exist but all work is finished and the balance is zero is rendered as:
"No tienes pedidos para hoy."

That is false. The day may still have non-zero sales/cost/profit economics.

## Required behavior

1. Distinguish at least these two states:
   - truly no non-cancelled orders existed today: keep the genuine no-orders empty state;
   - orders existed today, but there is no remaining preparation, delivery, or collection work: show a clear completed-day state such as "Todo lo de hoy está completo." Do not claim there were no orders.

2. Preserve the money summary and its exact non-zero sales/cost/profit values after the final order is completed and fully paid.

3. Do not change domain economics, fulfillment semantics, collection semantics, exact arithmetic, date authority, or cancellation rules.

4. Prefer consuming the existing `has_orders` presentation fact. Only change `OperationalSummary` if a strictly necessary presentation fact is missing; do not recompute domain truth in the frontend.

5. Add focused feature regression proving:
   - a completed-only, fully paid day still has `has_orders=true`;
   - actionable production/delivery/collection collections can all be empty;
   - economics remain non-zero and exact;
   - Home payload exposes the data needed for the correct distinction.

6. Extend mobile Playwright with realistic 320px and 390px populated lifecycle coverage that reaches the completed-only state through existing product UI/domain operations. Reuse existing helpers/flows where possible. The assertion must prove:
   - the completed-day message is visible;
   - the false "No tienes pedidos para hoy." message is absent;
   - non-zero day economics remain visible;
   - no horizontal overflow.

7. Preserve all existing Today/Production assertions and TSK-018 behavior.

## Scope

Product mutations should be narrowly limited to what AUD-16 requires. Expected target files:
- resources/js/Pages/Home.tsx
- tests/Feature/TodayProductionTest.php
- e2e/today-production.spec.ts

`app/Services/OperationalSummary.php` is allowed only if genuinely necessary after inspection.

All existing dirty paths are inherited TSK-018 state and must be preserved.

Do NOT touch:
- docs/design/**
- Google OAuth
- TSK-019 polish/OBS decisions
- recipe/order semantics unrelated to AUD-16
- migrations/models unless absolutely impossible (not expected)

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

PASS only if all commands pass and path policy has zero violations.
