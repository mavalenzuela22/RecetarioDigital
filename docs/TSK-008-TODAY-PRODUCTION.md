# TSK-008 — Today & Production

## Objective

Implement the smallest safe complete v1 operational boundary that answers:

- what must be prepared today;
- what must still be delivered today;
- who still owes money today;
- what revenue/cost/profit is represented by today's non-cancelled orders;
- what production workload exists for a selected local date/range;
- how to start preparation and move prepared orders to `ready`.

This task replaces the current marketing-style Home shell with the accepted **Hoy en tu cocina** operational surface and implements the accepted **Producción** flow.

It must remain a lightweight operating surface, not an ERP/reporting dashboard.

## Authority

- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- accepted `hoy` and `produccion` flows in `docs/design/screens/flows.json`
- `docs/design/implementation-guide/IMPLEMENTATION.md`
- existing TSK-006 immutable order-line economic snapshots
- existing TSK-007 payment and fulfillment history

Do not modify `docs/design/**`.

## Business date

The server is authoritative for the business date.

Use the configured Laravel application timezone (`config('app.timezone')`, currently `America/Monterrey`) and local calendar dates. Do not derive the authoritative business date from browser UTC conversion.

Home uses the current business date.

Production accepts an inclusive local date range:
- `from` required after normalization;
- `to` optional and defaults to `from`;
- `to < from` is invalid.

Do not introduce arbitrary timezone selection in v1.

## One operational/economic read model

Today and Production must derive from persisted operational facts:
- `orders`;
- immutable `order_lines` snapshots;
- append-only `order_payments`;
- append-only `order_fulfillment_events`.

Do not add a parallel reporting table/store.

Put aggregation/business calculations in a service/domain class, not React and not controllers.

### Exact arithmetic

Use existing exact integer helpers.

Authoritative values:
- revenue/payment/balance: MXN minor units;
- attributable cost: micros;
- profit: micros.

Never use binary floating point for authoritative aggregation.

Use overflow-safe addition. Do not clamp.

Revenue-to-profit conversion uses the existing exact minor-to-micros conversion.

If any economically included line has incomplete/null cost:
- revenue remains available;
- cost is incomplete;
- profit is `null`;
- UI says it is pending;
- never substitute zero cost.

## Today / Home semantics

Home is an action surface for the current business date.

### Financial day set

For today's revenue/cost/profit and balance totals include:
- orders whose `delivery_date` is the business date;
- every fulfillment state except `cancelled`.

A delivered order still belongs to that day's economics and may still owe money. Completing delivery must not make the day's revenue or debt disappear.

Cancelled orders contribute neither expected revenue, cost/profit nor balance due in Today.

### Production-to-prepare set

`Piezas por preparar` includes line quantities from orders due today in:
- `confirmed`;
- `in_preparation`.

It excludes:
- `ready` because production is already complete;
- `delivered`;
- `cancelled`.

Group the production preview by stable `product_id` while preserving the order-line snapshot display name for the constituent order facts.

### Deliveries pending

A delivery is pending today when the order is due today and fulfillment is:
- `confirmed`;
- `in_preparation`;
- `ready`.

Do not treat `delivered` or `cancelled` as pending delivery.

Show customer, local delivery time and fulfillment label with a direct path to the order.

### Collections pending

A collection is pending today when:
- order is due today;
- order is not cancelled;
- `balance_minor > 0`.

This includes delivered-but-unpaid orders.

Show the customer, exact balance and a direct path to the existing `Cobro y entrega` order detail.

Do not treat advance amounts as profit.

### Today money

Expose:
- expected revenue for the day's non-cancelled orders;
- outstanding balance for those orders;
- estimated cost only when complete;
- estimated profit only when complete.

These are order-snapshot economics, not cash accounting.

Use plain labels that make this distinction clear.

### Empty/partial states

Required concepts:
- `Hoy en tu cocina`
- `No tienes pedidos para hoy.`
- `Tomar pedido`
- `Ver producción`
- `Registrar compra`
- `Necesitas preparar`
- `Entregas pendientes`
- `Por cobrar hoy`
- `Venta estimada`
- `Ganancia estimada`
- when cost is incomplete: `Ganancia pendiente de calcular.`

No fake example values remain on Home.

## Production range semantics

The Production page answers the selected range using orders whose `delivery_date` is within `from..to` inclusive.

### Active production set

Include orders in:
- `confirmed`;
- `in_preparation`;
- `ready`.

Exclude:
- `delivered`;
- `cancelled`.

This follows the accepted production flow's active-work boundary.

For each product group expose:
- stable product ID;
- snapshot display name suitable for the group;
- total active units across constituent order lines;
- units still requiring preparation (`confirmed + in_preparation`);
- units already `ready`;
- constituent orders with customer, delivery date/time, quantity, fulfillment state and order-detail URL.

A ready order may remain visible in the active production range for context, but its quantity must not be counted as `Necesitas preparar`.

### Production economics

For the active production set expose:
- estimated revenue;
- attributable snapshot cost when complete;
- estimated profit when complete.

Do not recompute historical order economics from current ingredient purchases.

## Start production

The Production page has the accepted primary action `Iniciar preparación`.

For the selected inclusive range:
- transition every currently `confirmed` order in the range to `in_preparation`;
- append one immutable `OrderFulfillmentEvent` per actual transition;
- preserve payment state and all payment aggregates;
- preserve lines/economic snapshots;
- do not change `in_preparation`, `ready`, `delivered` or `cancelled` orders;
- do not mark anything ready, delivered or paid.

The operation must be atomic across the selected confirmed orders.

Inside one transaction:
1. select candidate orders deterministically by ID;
2. lock them;
3. re-check range and state;
4. append fulfillment events for actual `confirmed -> in_preparation` transitions;
5. update only fulfillment state.

Repeated or concurrent submission must be safe:
- once an order is no longer `confirmed`, it is not transitioned again;
- duplicate/concurrent clicks must not append duplicate `confirmed -> in_preparation` events.

Generated event request keys remain unique UUIDs and request hashes must describe the actual order/from/to transition. The action does not need a speculative new batch table.

Success copy:
`Pedidos en preparación.`

If there are no confirmed orders left, the operation is a safe no-op and still leaves the user on a correct Production view.

## Mark ready

TSK-007 explicitly deferred the `ready` control to production work. This task completes that step.

Authorize exactly:
- `in_preparation -> ready`.

Use the existing append-only fulfillment event mechanism and strict UUID request-key/hash idempotency.

Rules:
- only `in_preparation` may become `ready`;
- `confirmed` cannot skip directly to `ready`;
- `ready` cannot be marked ready again with a new request key;
- `delivered` and `cancelled` cannot become ready;
- payment state/totals never change;
- order lines and economics never change.

The existing TSK-007 delivery/cancellation transitions remain valid:
- `confirmed | in_preparation | ready -> delivered`
- `confirmed | in_preparation | ready -> cancelled`.

Do not weaken those rules.

Required copy:
- `Marcar listo`
- `Listo para entregar`
- `Pedido listo para entregar.`

## UI / mobile behavior

Spanish-first `es-MX`.

Validate at 320px and 390px.

### Home
Follow the accepted warm handcrafted design language:
- current date and operational title first;
- production preview before lower-priority actions;
- delivery and collection rows readable without horizontal tables;
- money summary below operational facts;
- primary `Ver producción`;
- secondary `Registrar compra`;
- direct `Tomar pedido` path when useful;
- order rows link to existing order detail.

Do not retain the previous marketing hero/focus-area content as the primary Home experience.

### Production
Use:
- back navigation;
- `Producción` title;
- date/range controls;
- product groups with constituent orders;
- exact economics summary;
- `Iniciar preparación` when confirmed orders exist;
- `Marcar listo` only for in-preparation constituent orders;
- clear empty state;
- no horizontal overflow.

Touch targets remain >=48px; primary actions approximately 52px.

Do not add a dense enterprise table.

## Required backend coverage

At minimum test:

1. Home uses the configured local business date.
2. Today excludes cancelled orders from financial totals.
3. Today keeps delivered-but-unpaid balance/revenue visible.
4. Today `pieces to prepare` counts confirmed + in_preparation but not ready/delivered/cancelled.
5. Today delivery-pending excludes delivered/cancelled.
6. Today collection-pending includes delivered unpaid orders.
7. Today revenue/balance exact arithmetic.
8. Today profit uses order-line snapshots and becomes null when any included line cost is incomplete.
9. Production date range is inclusive and defaults `to = from`.
10. Invalid reversed range is rejected.
11. Production active set contains confirmed/in_preparation/ready and excludes delivered/cancelled.
12. Product aggregation preserves constituent order association and exact quantities.
13. Production economics use order snapshots, not current ingredient cost.
14. Start production atomically changes only confirmed orders to in_preparation.
15. Start production appends immutable fulfillment events.
16. Start production preserves payment state, paid and balance.
17. Repeated/concurrent-safe start does not duplicate transition events.
18. Mark ready accepts only in_preparation -> ready.
19. Mark ready is request-key/hash idempotent.
20. Mark ready preserves payment/economic facts.
21. Existing delivery/cancellation transition behavior remains valid.
22. Home and Production Inertia payloads expose only presentation-ready exact strings/booleans/URLs needed by UI.

## Required Playwright coverage

Add a focused mobile flow at both 320px and 390px.

Because Today is intentionally a shared business-day aggregate, the new spec may serialize its two viewport cases locally if needed to avoid cross-worker mutation of the same business-date production set. Do not change global Playwright parallelism.

The flow must:
1. create prerequisites and a priced product through existing UI;
2. create an order due on the authoritative business date with a remaining balance;
3. open Home;
4. verify the unique product/customer appears in today's preparation/collection surface;
5. open Production;
6. verify the product group and constituent order;
7. start preparation;
8. verify `En preparación` and success;
9. mark that order ready;
10. verify `Listo para entregar` and success;
11. return Home;
12. verify that order no longer contributes as needing preparation while it remains a pending delivery and its unpaid balance remains actionable;
13. verify no horizontal overflow.

Update the existing Home smoke spec to the real Today surface rather than preserving obsolete marketing-copy assertions.

Do not weaken existing order-operation E2E.

## Validation matrix

Run:
1. `docker compose build app`
2. `docker compose run --rm app composer install --no-interaction --prefer-dist`
3. `npm ci`
4. `docker compose run --rm app php artisan test`
5. `npx tsc --noEmit`
6. `npm run build`
7. idempotent cleanup of the known named E2E container if present
8. `npx playwright test e2e/home.spec.ts e2e/order-operations.spec.ts e2e/today-production.spec.ts --project=mobile`
9. `git diff --check`

## Path-count guardrail

Keep total visible changed paths for the complete TSK, including governance artifacts and bounded correctives, at **30 or fewer**. Prefer 16–22.

If the smallest safe complete implementation cannot remain <=30, stop BLOCKED instead of broadening.

## Explicit non-goals

Do not implement:
- inventory or procurement;
- ingredient demand planning / shopping lists;
- recipe scaling instructions;
- route optimization or addresses;
- refunds/credits;
- editing order lines or historical snapshots;
- full accounting/cashflow;
- historical before/after analytics;
- charts/reporting warehouse;
- overdue-across-all-dates collections dashboard;
- customer CRM;
- auth/RBAC;
- multilingual UI;
- offline synchronization;
- design-system rewrite;
- dependency changes;
- new reporting persistence tables;
- broad refactors.

## Completion rule

TSK-008 is complete only when:
- Home is the accepted operational Today surface;
- Production works for an inclusive local date/range;
- production quantities retain order association;
- start-production and mark-ready lifecycle rules are durable and history-preserving;
- exact order-snapshot economics are used;
- incomplete cost never becomes zero;
- complete backend/mechanical validation passes;
- focused mobile E2E passes at 320px and 390px;
- semantic review confirms behavior matches this boundary;
- governed commit/push/PR/merge/integration/reconciliation/cleanup completes.
