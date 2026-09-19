# TSK-006 — Orders & Immutable Economic Snapshots

## Objective
Implement the smallest safe complete v1 **order capture** boundary for EmprendimientoOS so the user can take a real multi-product order on a phone, preserve the agreed economics at order time, capture an optional advance payment, and leave durable facts for the later collections/delivery and production tasks.

This task implements the fourth core journey from the product authorities: **create an order**.

## Authority
- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- accepted order flow in `docs/design/screens/flows.json`
- accepted implementation guidance in `docs/design/implementation-guide/IMPLEMENTATION.md`
- accepted status map in `docs/design/components/status-map.json`
- existing TSK-003 ingredient economics
- existing TSK-004 recipe/version snapshots
- existing TSK-005 product cost profiles and price history

Do not modify `docs/design/**`.

## Product boundary

### Customer
v1 order capture does **not** introduce CRM.

Persist the customer as a human name on the order:
- required
- trimmed/collapsed whitespace
- max 120 characters

The UI may suggest previously used names if convenient, but no customer master, corporate fields, account hierarchy, addresses, tax IDs, marketing metadata or CRM workflow belong in this task.

### Order
Persist a stable `Order` with:
- immutable request key/hash for idempotent creation
- customer name snapshot
- local delivery date
- local delivery time
- optional notes, max 2000
- fulfillment state
- payment state
- exact total in MXN minor units
- exact amount paid in MXN minor units
- exact balance due in MXN minor units
- timestamps

Store local business date and time as local date/time facts. Do not browser-guess or UTC-shift them into a different calendar day.

For this order-capture task, a successfully saved order starts as:
- fulfillment: `confirmed`
- payment:
  - `pending` when paid amount = 0
  - `partial` when 0 < paid amount < order total
  - `paid` when paid amount = order total

This matches the accepted operational fixture where captured orders are confirmed and become production work. Later fulfillment transitions, cancellation, additional payments and delivery are separate tasks.

### Order lines
Each order contains at least one line. Each line snapshots:
- product identity
- product display name at order time
- sale unit (`piece` in v1)
- integer quantity >= 1
- agreed unit sale price in exact MXN minor units, > 0
- line revenue in exact MXN minor units
- product-price record ID when the agreed price exactly corresponds to the product's current stored price; nullable for a manually agreed line price
- recipe-version ID used by the current product economics at capture time
- product-cost-profile ID used by the current product economics at capture time
- attributable unit-cost snapshot in MXN micros when current product cost is complete; nullable when incomplete
- attributable line-cost snapshot in MXN micros when complete; nullable when incomplete
- explicit cost-complete flag

The saved agreed price is authoritative for the order forever. Later product price changes must never rewrite it.

The saved cost snapshot is authoritative for historical order profit. Later ingredient purchases, recipe changes or product-cost-profile changes must never rewrite it.

If product current cost is incomplete, the order may still be saved when a valid agreed price exists:
- revenue and balance remain known;
- cost snapshots are null/incomplete;
- profit is unknown;
- never substitute zero.

### Product eligibility
Only existing **active** products may be newly added to an order.

At save time, re-check product availability server-side. If a selected product became inactive, reject the order with a line-level validation error equivalent to:
`Ese producto ya no está disponible. Conservamos el resto del pedido.`

This means preserve the form and all other valid lines; do not partially save an order.

Inactive products remain valid in historical orders.

### Agreed price
The line editor should prefill the product's current sale price when one exists, but the user may explicitly agree a different positive line price.

Rules:
- decimal input, up to 2 decimals;
- > 0;
- no binary-float authority;
- an absent current product price does not block ordering if the user explicitly enters a valid agreed price;
- changing product prices later does not affect the order line.

### Advance / initial payment
Order capture accepts one optional `Anticipo recibido`:
- exact decimal money
- default 0
- min 0
- max order total

Persist the economic fact so a later payment-history task does not have to reconstruct it.

Use an append-only `OrderPayment` record for a positive initial advance:
- order
- amount minor
- kind/source equivalent to `advance`
- effective timestamp / persisted ordering basis
- timestamps

No payment row is necessary when advance = 0.

Normal application behavior must not update or delete historical payment rows.

The order's paid/balance/payment-state fields are the current aggregate state at creation and must agree exactly with the payment fact.

Additional collections after creation are outside this task.

### Idempotency
Order creation must be server-idempotent.

- request key is a UUID;
- same key + identical normalized payload returns/replays the already-created order without duplicate lines/payments;
- same key + different payload is rejected;
- a duplicate-submit button state is UX only, not the integrity mechanism.

### Exact arithmetic
No authoritative money or quantity calculation may use binary floating point.

Use:
- prices/totals/paid/balance: integer MXN minor units;
- cost basis/profit inputs: integer MXN micros;
- quantities: integer count.

Required exact checks:
- line revenue = agreed price minor × quantity;
- order total = exact sum of line revenues;
- line cost micros = unit cost micros × quantity;
- paid <= total;
- balance = total - paid.

Detect signed-64-bit overflow before persisting. Never clamp and never fall back to float.

Unsafe JSON integers are strings.

### Profit semantics exposed in this task
The order detail may show:
- known revenue;
- known paid/balance;
- estimated historical profit only when every line has a complete cost snapshot.

When every line has complete cost:
- total cost snapshot = sum line cost snapshots
- profit snapshot = order revenue micros - total cost micros

When any line lacks cost:
- show `Ganancia pendiente de calcular.`
- do not present zero cost/profit.

Do not implement analytics/history comparisons here.

## UX / routes
Spanish-first `es-MX`, mobile-first.

Provide:
- Orders index titled `Pedidos`
- primary `Tomar pedido`
- order creation page titled `Tomar pedido`
- post-save detail/summary page

Order form:
1. customer name
2. add active products
3. vertical product lines
4. integer quantity stepper or equally direct mobile control
5. agreed unit price visibly editable and prefilled from current product price when available
6. delivery date
7. delivery time
8. optional notes
9. `Anticipo recibido`
10. always-visible closing summary: total / anticipo / saldo
11. sticky `Guardar pedido`

Expected copy includes:
- `Cliente`
- `Productos`
- `Agregar producto`
- `Cantidad`
- `Precio acordado`
- `Fecha de entrega`
- `Hora`
- `Nota (opcional)`
- `Anticipo recibido`
- `Total del pedido`
- `Saldo pendiente`
- `MXN · El precio acordado se conserva en este pedido.`
- `Agrega al menos un producto.`
- `El anticipo debe estar entre $0 y el total.`
- `Pedido registrado.`

Plain-language status labels must preserve separate axes:
- fulfillment: `Confirmado`
- payment: `Pendiente de cobro`, `Pago parcial`, or `Pagado`

Do not imply that paid means delivered or delivered means paid.

### Form behavior
- preserve valid values on validation failure;
- focus first invalid field;
- adding/removing lines must not erase other lines;
- quantity cannot silently become zero; remove is a distinct action;
- duplicate product lines should be prevented or rejected clearly;
- inactive/unavailable product error preserves the rest of the form;
- block duplicate submit visually;
- support server idempotency;
- dirty-back confirmation on the order form;
- 48px minimum touch targets and approximately 52px primary action;
- no horizontal overflow at 320px or 390px.

### Entry points
Add a clear path from the existing application to Orders. A home action such as `Tomar pedido` and/or `Ver pedidos` is in scope. Do not redesign Home into the full Today dashboard in this task.

## Required backend behavior / tests
At minimum cover:
- customer-name validation without CRM;
- one and multiple product lines;
- active-only product eligibility;
- duplicate product-line rejection;
- integer quantity >=1;
- current price prefill but manual agreed-price override;
- exact line/order totals;
- incomplete current cost -> nullable cost snapshot, known revenue;
- complete cost -> immutable unit/line cost snapshots;
- recipe/profile/price changes after order creation do not rewrite order snapshots;
- product becoming inactive before save is rejected without partial persistence;
- payment state pending/partial/paid derived from exact advance;
- advance > total rejected;
- positive advance creates immutable payment fact;
- zero advance creates no payment row;
- order idempotent replay;
- same request key + different payload rejection;
- arithmetic overflow rejection;
- Inertia index/create/show surfaces.

## Required Playwright flow
Real isolated mobile E2E at 320px and 390px:
1. create purchase prerequisites;
2. create at least two recipes/products with known prices;
3. open `Pedidos` and `Tomar pedido`;
4. enter a unique customer name;
5. add two products;
6. use different integer quantities;
7. verify current prices prefill;
8. modify at least one agreed price manually;
9. set local delivery date/time;
10. enter an advance;
11. verify total / advance / balance before save;
12. save;
13. verify `Pedido registrado.`;
14. verify detail shows customer, both lines, exact agreed prices, total, payment state and balance;
15. verify no horizontal overflow.

Do not weaken or replace prior purchase/recipe/product E2E tests.

## Validation matrix
Run:
1. `docker compose build app`
2. `docker compose run --rm app composer install --no-interaction --prefer-dist`
3. `npm ci`
4. `docker compose run --rm app php artisan test`
5. `npx tsc --noEmit`
6. `npm run build`
7. idempotent cleanup of the known named E2E container if present
8. `npx playwright test e2e/order-capture.spec.ts --project=mobile`
9. `git diff --check`

## Path-count guardrail
Keep the total visible changed-path set for the complete TSK, including governance artifacts and bounded correctives, at **30 or fewer**.

Prefer 18–24 paths. If the smallest safe complete implementation cannot remain <=30, stop BLOCKED rather than expanding the boundary.

## Explicit non-goals
Do not implement:
- payment collection after order creation;
- marking ready/delivered;
- cancellation/refunds;
- production aggregation;
- Today dashboard aggregation;
- historical analytics / `Antes y ahora`;
- customer master/CRM;
- addresses/routes;
- inventory or procurement;
- invoicing/accounting/tax;
- discounts/coupons;
- taxes;
- arbitrary currencies;
- auth/RBAC;
- multilingual UI;
- complex offline sync;
- design-system rewrite;
- broad refactors or dependency changes.

## Completion rule
TSK-006 is complete only when:
- governed implementation exists on the bounded task branch;
- complete validation passes;
- order economics are exact and server-authoritative;
- price/cost/payment facts are historically preserved;
- mobile flow passes at 320px and 390px;
- path guardrail passes;
- governed commit/push/PR/merge/integration/reconciliation/cleanup completes.

## Bounded implementation record — 2026-09-19

The bounded implementation is present on branch `tsk-006-orders-snapshots`; formal acceptance remains **BLOCKED on executable backend and mobile validation in this sandbox**. No commit, push, PR, merge, or promotion was performed.

- Added atomic order capture with customer-name snapshots, multi-product lines, exact integer MXN minor-unit revenue/advance/balance arithmetic, local delivery date/time, notes, confirmed fulfillment, derived payment state, and append-only positive advance facts.
- Order lines preserve product display name, agreed price, matching current product-price identity when applicable, current recipe/profile identities, attributable cost micros, line revenue/cost, and explicit completeness. Incomplete current cost remains null and the detail shows `Ganancia pendiente de calcular.`.
- Server-side save locks and rechecks all selected products before any order, line, or payment is written. Normalized request hashes replay identical submissions and reject key reuse with different data.
- Added Spanish Inertia Orders index/create/show surfaces, a Home entry point, dirty-back protection, duplicate-submit blocking, exact client-side summary arithmetic, current-price prefill, editable agreed prices, quantity controls, and 320/390px Playwright coverage.
- PASS: visible changed-path count is **18**, under the 30-path guardrail; no `docs/design/**` or blocked-path changes exist. `git diff --check` passed. The local Playwright runner discovers both mobile cases.
- BLOCKED: `docker compose build app`, Composer install, Pest, and the real Playwright server cannot run because Docker cannot update BuildKit state or access `/Users/martin.valenzuela/.docker/run/docker.sock`.
- BLOCKED: required `npm ci` ended with npm's `Exit handler never called` error and removed executable links; exact `npx tsc --noEmit`, `npm run build`, and exact Playwright execution therefore could not be claimed. Direct local diagnostics found only pre-existing TypeScript errors in `resources/js/app.tsx` and `resources/js/Pages/Purchases/Create.tsx`; the direct Vite build is missing the incomplete-tree dependency `vite-plugin-full-reload`. No dependency manifest or lockfile was changed.
- Physical-device, assistive-technology, live MySQL, public-deployment, governance-audit, and promotion evidence are not claimed. Foundry must rerun the execution contract with Docker, PHP/Composer, and a healthy npm installation before acceptance.
