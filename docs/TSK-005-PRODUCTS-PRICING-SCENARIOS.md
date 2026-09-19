# TSK-005 — Products, Cost Allocation & Pricing Scenarios

## Objective
Implement the smallest safe complete v1 product/pricing boundary that turns an existing recipe into a sellable product, calculates its current attributable unit cost with exact server-side arithmetic, lets the user explore the accepted pricing scenarios, records sale-price changes append-only, and preserves economically meaningful configuration history.

This task implements the third critical mobile journey from the product authorities: **see product cost and price scenarios**.

## Authority
- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- accepted product flow in `docs/design/screens/flows.json`
- accepted implementation guidance in `docs/design/implementation-guide/IMPLEMENTATION.md`
- existing TSK-003 ingredient economics and TSK-004 immutable recipe versions/current costing

Do not modify `docs/design/**`.

## Product boundary

### Product identity
A `Product` is a stable sellable identity that:
- references exactly one existing `Recipe`;
- uses that recipe's current version for **current** product costing;
- has a sale unit; v1 production supports `piece` only;
- has an active/inactive flag;
- keeps history even when inactive.

For v1, one sellable product per recipe is sufficient and preferred. Do not invent variants, packages, SKUs, catalogs, inventory, or CRM.

The visible product name follows the current recipe name. Do not duplicate a separately editable product-name authority in this task.

### Immutable product-cost profiles
Economically meaningful additional-cost configuration must not be overwritten in place.

Use an immutable/effective sequence equivalent to:
- `ProductCostProfile`
  - product
  - monotonic version number
  - explicit reference order quantity, integer >= 1
  - request key/hash for idempotency
  - timestamps
- `ProductCostComponent`
  - profile
  - position
  - concept, non-empty, <= 120
  - exact amount in integer minor units (MXN cents), >= 0
  - allocation: `batch | unit | order`

Every successful save of additional-cost configuration creates a new immutable profile, including the initial profile. Old profiles/components remain unchanged.

Active/inactive is availability state, not a cost fact; it may live on `Product` and may be updated atomically with the profile save. Inactive products remain readable and retain all history.

A stale profile edit must be rejected if a newer profile exists.

### Additional-cost allocation semantics
Each configured component is applied exactly once.

For a complete current recipe:
1. **recipe unit cost** = current recipe batch cost / current recipe expected yield, as supplied by authoritative TSK-004 current costing;
2. **batch component per unit** = component amount / current recipe expected yield;
3. **unit component per unit** = component amount;
4. **order component per unit** = component amount / explicit `reference_order_quantity`;
5. **current attributable product unit cost** = recipe unit cost + all three allocated component groups.

The UI must explicitly show denominators for batch and order allocations:
- batch cost divided by recipe yield;
- order/delivery cost divided by the reference-order quantity.

Do not count an order/delivery component again as a unit component.

If the recipe current cost is incomplete, the product current cost is `incomplete`; never substitute zero for missing recipe economics.

If no order-level components exist, the reference-order quantity may default to the current recipe expected yield, but it remains explicit persisted configuration and must be >= 1.

### Historical semantics
- old product cost profiles are immutable;
- old price records are immutable;
- later recipe/ingredient changes may change **current** product cost;
- they must not rewrite saved cost profiles or price history;
- future order snapshots will capture their own price/cost basis in the Orders task.

This task does not implement the historical comparison screen; it only preserves the facts needed by later history/order tasks.

## Exact arithmetic
No binary floating-point value may be authoritative.

Use integer arithmetic:
- persisted additional-cost amounts: integer MXN minor units (cents);
- current/product usage math: integer micros of MXN when sub-cent precision is needed;
- `1 cent = 10,000 micros`;
- unsafe JSON integers are strings.

Use half-up rounding where division is required.

All multiply/divide helpers must avoid signed-64-bit intermediate overflow using quotient/remainder decomposition or an equivalent exact bounded technique. Detect unrepresentable final results and fail validation; never clamp and never fall back to float.

Batch/unit/order additions must detect overflow.

## Pricing scenarios
The accepted v1 scenario set for this task is:
- ×2
- ×2.5
- ×3
- ×3.5

Exploring a scenario must **not persist** anything.

For each scenario, server-authoritative data must provide:
- multiplier;
- suggested sale price rounded half-up to MXN cents;
- profit per unit;
- expected profit for one current recipe yield;
- margin on sale price to one decimal percent.

Definitions:
- markup multiplier = suggested price / attributable unit cost;
- unit profit = sale price - attributable unit cost;
- margin = unit profit / sale price.

Do not label multiplier as margin.

If current product cost is incomplete, scenarios are incomplete and must not fabricate values.

The frontend may select among already server-calculated scenario results; it must not become the monetary authority.

## Sale-price history
A sale-price change creates an append-only `ProductPrice` record:
- product
- exact price in MXN cents, > 0
- effective timestamp / persisted ordering basis
- request key/hash for idempotency
- timestamps

Current price is the latest effective record, deterministically tie-broken by persisted ID if required.

Price rows cannot be updated or deleted through normal application behavior.

Changing price:
- requires explicit user confirmation;
- supports both accepted scenario price and manual decimal price;
- is idempotent: same request key + identical payload replays without duplicate; same key + different payload is rejected;
- displays the accepted warning: `El nuevo precio se aplicará a nuevos pedidos. Los anteriores conservan su precio acordado.`

Future orders will snapshot agreed prices; orders are not implemented here.

## UX / routes
Spanish-first, `es-MX`, mobile-first.

Provide a usable product journey:
- Product list / entry from Recetario.
- Unconfigured recipes can be turned into products without re-entering recipe data.
- Product detail titled `Costos y precio`.
- Current attributable cost card.
- Current sale price, profit per piece and margin when a price exists.
- Cost breakdown disclosure with recipe, batch extras, unit extras and order/reference allocation.
- Scenario controls ×2 / ×2.5 / ×3 / ×3.5.
- Scenario result with suggested price, profit per piece, expected yield profit and margin.
- Primary action `Usar este precio`.
- Manual sale-price entry with confirmation.
- Additional-cost editor using concept + amount + allocation.
- Explicit `Cantidad de referencia del pedido` when order allocation is relevant.
- Product active/inactive control with plain-language explanation.
- Price history may remain persistence-only in this task; do not build the later full `Antes y ahora` screen.

Expected copy includes:
- `Costo por pieza`
- `Precio actual`
- `Ganancia por pieza`
- `Margen sobre venta`
- `Explora otro precio`
- `Costos adicionales`
- `Producto activo`
- `Falta información para calcular el costo.`
- `Pérdida por pieza` when current/manual economics are negative.

Preserve valid form values on validation failure, focus the first invalid field, guard dirty navigation for cost-profile editing, and block duplicate submits visually in addition to server idempotency.

Touch targets: at least 48px; primary actions approximately 52px. Validate at 320px and 390px with no horizontal overflow.

## Required backend behavior
At minimum cover:
- creating one product from an existing recipe;
- preventing duplicate product identities for the same recipe;
- initial immutable profile;
- profile version two and stale-base rejection;
- batch/unit/order allocation;
- explicit order reference denominator;
- current recipe cost propagation;
- incomplete recipe cost propagation without zero;
- exact half-up rounding;
- overflow rejection;
- scenario math and margin-vs-multiplier distinction;
- initial price and later append-only price history;
- deterministic current price;
- price idempotency;
- price-row immutability;
- inactive product persistence/history.

## Required Pest coverage
Include focused tests for:
- one-product-per-recipe;
- exact allocation of batch, unit and order costs;
- order denominator changes current attributable cost;
- incomplete recipe -> incomplete product;
- current recipe cost change changes current product cost without rewriting old product profile or old prices;
- profile v1/v2 immutability and stale edit rejection;
- arithmetic overflow;
- ×2, ×2.5, ×3, ×3.5 suggested-price rounding;
- profit and margin calculations distinct from multiplier;
- manual price below cost reports loss rather than hiding it;
- price append-only/current ordering;
- price idempotency and same-key/different-payload rejection;
- inactive product remains readable;
- Inertia list/detail/edit surfaces.

## Required Playwright flow
Real isolated mobile E2E at 320px and 390px:
1. create ingredient purchase prerequisites;
2. create a recipe with known yield and cost;
3. open Products from Recetario and configure that recipe as a product;
4. add at least:
   - one per-unit additional cost;
   - one per-order/delivery cost with explicit reference quantity;
5. verify current attributable unit cost;
6. select a pricing scenario and verify suggested price/profit/margin;
7. use that scenario price and confirm it;
8. verify current price;
9. save a different manual price and verify it becomes current while prior price history remains in persistence via Pest;
10. verify no horizontal overflow.

Do not weaken or replace the existing purchase/recipe E2E.

## Validation matrix
Run:
1. `docker compose build app`
2. `docker compose run --rm app composer install --no-interaction --prefer-dist`
3. `npm ci`
4. `docker compose run --rm app php artisan test`
5. `npx tsc --noEmit`
6. `npm run build`
7. `npx playwright test e2e/product-pricing.spec.ts --project=mobile`
8. `git diff --check`

Use a real isolated Playwright server/database. Clean a known stale E2E container before the Playwright command if the existing harness requires it; do not modify Playwright configuration merely to hide a stale-process problem.

## Path-count guardrail
Keep total visible changed paths for the complete TSK, including governance artifacts and bounded correctives, at **30 or fewer**. Prefer 18–24.

If the smallest safe complete implementation cannot stay within 30 visible changed paths, stop BLOCKED and report why rather than broadening the boundary.

## Explicit non-goals
Do not implement:
- orders, customers, payments or fulfillment;
- production planning;
- historical comparison / analytics screen;
- inventory or procurement;
- supplier master;
- taxes, accounting or invoicing;
- product variants, SKUs or bundles;
- weighted-average ingredient cost;
- arbitrary pricing-settings administration;
- multilingual UI;
- auth/RBAC;
- design-system rewrite;
- broad refactors or dependency changes.

## Completion rule
TSK-005 is complete only when:
- governed implementation is inside the bounded task branch;
- complete mechanical evidence passes;
- exact product/pricing economics are server-authoritative;
- history is preserved;
- mobile flow passes at 320px and 390px;
- path guardrail passes;
- promotion to main completes through the governed repository transaction lifecycle.

## Bounded implementation record — 2026-09-19

The bounded implementation is present on branch tsk-005-products-pricing-scenarios; formal acceptance remains **BLOCKED on executable backend and mobile validation in this sandbox**. No commit, push, PR, merge, or promotion was performed.

### Implemented decisions

- A product references one existing recipe, uses piece, enforces one product per recipe, follows the current recipe name, and retains active/inactive state.
- Product cost profiles and components are immutable and versioned. Profile saves persist the explicit order-reference quantity, preserve old profiles, reject stale bases, and use request-key/hash replay protection.
- Current product costing consumes TSK-004 dynamic recipe economics and allocates batch, unit, and order components exactly once with integer micros, quotient/remainder half-up rounding, overflow checks, and incomplete propagation.
- The server calculates ×2, ×2.5, ×3, and ×3.5 suggested prices, unit profit, expected yield profit, and margin separately from multiplier. Scenario exploration remains non-persistent.
- Product prices are append-only, deterministically current by effective timestamp and ID, idempotent by request key, explicitly confirmed, and immutable through the model.
- Spanish mobile-first Products list/configuration/detail surfaces include recipe-to-product entry, cost breakdown denominators, reference-order input, active/inactive explanation, scenario selection, manual price confirmation, current profit/margin, and loss messaging.
- Focused Pest coverage and a real isolated mobile Playwright flow were added for product identity, exact allocation, incomplete/current propagation, profile history/staleness, arithmetic overflow, all scenarios, loss reporting, append-only/idempotent prices, inactive readability, Inertia surfaces, and 320/390px flow coverage.

### Validation evidence and limits

- PASS: local npx tsc --noEmit and git diff --check passed on the completed frontend delta before the contract npm ci environment failure.
- PASS: visible changed-path count is **22**, under the 30-path guardrail; no docs/design/** or blocked-path changes exist.
- BLOCKED: docker compose build app cannot update /Users/martin.valenzuela/.docker/buildx/activity in this sandbox.
- BLOCKED: Composer install, Pest, and the Playwright server cannot run because Docker cannot access /Users/martin.valenzuela/.docker/run/docker.sock; no backend or mobile PASS is claimed.
- BLOCKED: contract npm ci emitted npm's Exit handler never called failure, and the subsequent exact npx tsc --noEmit could not resolve the registry (ENOTFOUND registry.npmjs.org); npm run build and Playwright could not resolve their removed local executables. Dependency manifests were not changed.
- Physical-device, assistive-technology, live MySQL, public-deployment, governance-audit, and promotion evidence are not claimed. Foundry must rerun the execution contract with Docker and registry access before acceptance.
