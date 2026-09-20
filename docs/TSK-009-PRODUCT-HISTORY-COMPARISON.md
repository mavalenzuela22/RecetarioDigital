# TSK-009 — Product History Comparison (“Antes y ahora”)

## Objective

Implement the accepted `historial` flow: a mobile-first product-level **Antes y ahora** comparison that answers how configured production cost, sale price, unit profit and margin changed between two local business dates, with truthful cost drivers and provenance.

This is **configured product economics**, not completed-order accounting. Historical orders continue to use their immutable order-line snapshots and are never recalculated by this feature.

## Authority

- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- accepted `historial` flow in `docs/design/screens/flows.json`
- `docs/design/screens/fixtures.json`
- `docs/design/implementation-guide/IMPLEMENTATION.md`
- TSK-003 ingredient purchase history
- TSK-004 immutable recipe versions
- TSK-005 immutable product cost-profile and price history
- TSK-006 immutable order snapshots

Do not modify `docs/design/**`.

## Historical meaning

### Local as-of boundary

Dates are local business dates in `config('app.timezone')` (currently America/Monterrey).

For timestamped facts, “as of YYYY-MM-DD” means the latest applicable fact at or before the **end of that local calendar day**.

For ingredient purchases, the economic effective date is the persisted local `purchased_on` date, not record creation time.

### Sources selected for each as-of date

For a product and date:

1. recipe version: latest version for the product recipe whose `created_at` is at/before local day close; deterministic tie-break by version/id;
2. product cost profile: latest profile whose `created_at` is at/before local day close; deterministic tie-break by version/id;
3. sale price: latest `ProductPrice` whose `effective_at` is at/before local day close, ordered by `effective_at DESC, id DESC`;
4. for each ingredient line in the selected recipe version: latest ingredient purchase with `purchased_on <= asOf`, ordered by `purchased_on DESC, id DESC`.

A later-entered backdated ingredient purchase participates according to its business-effective `purchased_on` date. Do not use current/latest purchase blindly.

No interpolation. No fabricated prior values.

## Reconstructed product economics

Reconstruct product economics from the selected historical facts using the same exact integer semantics as current costing:

- ingredient usage = historical normalized unit cost × normalized recipe quantity;
- recipe batch cost = sum ingredient usage;
- recipe unit cost = batch cost / expected yield, half-up exact;
- product additional-cost allocations:
  - batch: amount / recipe yield;
  - unit: direct amount;
  - order: amount / profile reference order quantity;
- unit production cost = recipe unit cost + allocated product costs;
- sale price = historical product price;
- unit profit = sale price - unit production cost;
- margin = unit profit / sale price.

Use integer minor units / micros only. No binary floating point.

If any selected recipe ingredient has no purchase effective by that date:
- the structural reference may still exist;
- cost is incomplete;
- unit cost, profit and margin are null/pending;
- sale price may still be shown;
- never substitute zero.

If recipe version, product profile or sale price did not yet exist by that date, that side is unavailable with:
`No hay una referencia económica para esa fecha. Elige otra fecha.`

## Comparison

Inputs:
- `asOf` — required when comparing;
- `until` — required when comparing;
- `until >= asOf`.

Initial GET may render the screen without running a comparison. Prefill both date controls with the current business date, but comparison occurs only after explicit `Comparar fechas`.

When both sides are available, expose:
- production cost per unit for each date;
- absolute cost delta;
- percentage cost delta when the earlier cost is non-zero, otherwise percentage is not calculable;
- sale price for each date;
- unit profit for each date;
- margin for each date;
- whether recipe/yield structure changed;
- whether product cost-profile configuration changed.

Negative profit remains visible as a loss.

## Structural comparability and drivers

Ingredient drivers are allowed only when recipe economics are structurally comparable.

Recipe structure is comparable when both selected recipe versions have:
- the same expected yield;
- the same ingredient IDs;
- the same normalized quantities per ingredient.

A version-number change alone does not make economics incomparable if those economic fields are identical.

If recipe structure/yield differs:
- mark comparison as recipe-changed;
- show: `La receta o el rendimiento cambiaron. Compara el desglose de cada versión.`
- do not attribute ingredient drivers across unlike structures.

When recipe structure is comparable and both sides have complete ingredient cost:
- calculate per-unit cost contribution delta for each ingredient using the historical purchases selected for each date;
- omit zero deltas;
- sort by absolute magnitude descending, then stable ingredient ID;
- expose ingredient name, signed per-unit delta, and source purchase dates.

Product cost-profile configuration is compared separately using:
- reference order quantity;
- ordered components with concept, amount and allocation.

If profile configuration differs, expose a clear note that additional configured costs changed. Do not falsely attribute that part of total cost delta to ingredients.

## Provenance

Each available side exposes a user-readable provenance disclosure containing:
- recipe version number and expected yield;
- product cost-profile version and reference-order quantity;
- price effective timestamp/date;
- ingredient purchase source date for each recipe line.

The UI must state:
`Compara la economía configurada del producto. No recalcula pedidos históricos.`

## UI

Add a `Antes y ahora` history link/action to the existing product cost/pricing detail.

Route:
`/productos/{product}/historial`

History screen:
- back to product;
- product name / `Antes y ahora`;
- two local date fields;
- primary `Comparar fechas`;
- side-by-side-on-wide / stacked-on-mobile “Antes” and “Ahora” cards;
- cost delta;
- price / profit / margin comparison;
- `Qué cambió el costo` drivers;
- recipe/profile change notices;
- provenance disclosure;
- secondary `Explorar otro precio` back to existing product pricing screen.

Required states/copy:
- `Antes y ahora`
- `Comparar fechas`
- `Explorar otro precio`
- `No hay una referencia económica para esa fecha. Elige otra fecha.`
- `La receta o el rendimiento cambiaron. Compara el desglose de cada versión.`
- `Compara la economía configurada del producto. No recalcula pedidos históricos.`
- invalid range: `La fecha final debe ser igual o posterior a la inicial.`

Spanish-first es-MX, 320px and 390px, no horizontal overflow, touch targets >=48px.

Do not add charts in this task.

## Required backend coverage

At minimum test:

1. local business-day close determines timestamped recipe/profile/price inclusion;
2. ingredient selection uses `purchased_on <= date` with deterministic same-date ID ordering;
3. backdated purchase participates by `purchased_on`, not insertion date;
4. historical cost uses historical recipe version, profile and ingredient purchases rather than current state;
5. exact batch/unit/additional allocation matches current costing semantics;
6. sale price as-of uses `effective_at DESC, id DESC`;
7. profit and margin are exact and may be negative;
8. missing structural reference returns unavailable state and exact copy;
9. missing ingredient purchase keeps sale price but makes cost/profit/margin pending/null;
10. invalid reversed range is rejected;
11. cost absolute and percentage deltas are exact; zero earlier base yields null/unavailable percentage;
12. economically identical recipe versions remain driver-comparable;
13. changed recipe quantity/yield suppresses ingredient-driver attribution and exposes recipe-changed state;
14. comparable recipe produces ingredient per-unit drivers sorted by absolute magnitude;
15. product profile change is surfaced separately and not misattributed to ingredient drivers;
16. provenance identifies selected recipe/profile/price and ingredient purchase dates;
17. Product detail exposes history URL and History Inertia payload is presentation-ready.

## Required Playwright coverage

Add `e2e/product-history.spec.ts`, mobile 320px and 390px.

Through existing UI:
1. record an ingredient purchase;
2. create recipe/product;
3. set a sale price;
4. open product detail;
5. follow `Antes y ahora`;
6. verify the historical screen and the configured-product disclaimer;
7. submit a valid same-day comparison using the current business date and verify cost/price/profit/margin/provenance render;
8. submit a reversed range and verify the exact invalid-range message while inputs remain usable;
9. verify `Explorar otro precio` returns to product pricing;
10. verify no horizontal overflow.

The detailed multi-date economic-delta behavior is backend-tested because UI-created recipe/profile/price timestamp facts cannot truthfully be backdated merely for E2E.

Do not add test-only production routes or mutate timestamps through browser-only hacks.

## Validation matrix

Run:
1. `docker compose build app`
2. `docker compose run --rm app composer install --no-interaction --prefer-dist`
3. `npm ci`
4. `docker compose run --rm app php artisan test`
5. `npx tsc --noEmit`
6. `npm run build`
7. idempotent cleanup of known E2E container
8. `npx playwright test e2e/product-pricing.spec.ts e2e/product-history.spec.ts --project=mobile`
9. `git diff --check`

## Path-count guardrail

Complete TSK including governance and bounded correctives must remain <=30 visible changed paths. Prefer <=16.

## Non-goals

Do not implement:
- completed-order profitability reporting;
- cash accounting;
- historical order recomputation;
- inventory/procurement;
- forecasting;
- charts/time-series dashboards;
- editable historical facts;
- arbitrary timezone selection;
- new reporting persistence tables;
- dependency changes;
- migrations unless a proven blocker makes truthful as-of impossible;
- redesign of product pricing;
- auth/RBAC;
- broad refactors.

## Completion rule

TSK-009 closes only when:
- as-of selection is faithful to persisted effective history;
- exact historical product economics match current formula semantics;
- drivers are attributed only when structurally valid;
- recipe/profile changes are disclosed rather than hidden;
- orders are explicitly not recalculated;
- full mechanical validation passes;
- mobile E2E passes;
- independent semantic review passes;
- governed promotion and cleanup complete.
