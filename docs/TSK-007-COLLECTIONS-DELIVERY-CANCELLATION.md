# TSK-007 — Collections, Delivery & Cancellation

## Objective
Implement the smallest safe complete v1 operational boundary for an existing order after capture: record collections, mark delivery, cancel an order, and preserve the economic/fulfillment history needed by later production and historical views.

This task implements the accepted **Cobro y entrega** journey immediately after TSK-006 order capture.

## Authority
- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- accepted `cobro` flow in `docs/design/screens/flows.json`
- `docs/design/components/status-map.json`
- `docs/design/implementation-guide/IMPLEMENTATION.md`
- existing TSK-006 order, line snapshot and initial-advance semantics

Do not modify `docs/design/**`.

## Boundary

### Order detail becomes the operational "Cobro y entrega" surface
The existing order detail is the single operational screen for this task.

It must show:
- customer and delivery date/time;
- fulfillment status and payment status as independent axes;
- order lines with agreed prices;
- total, amount already received and remaining balance;
- payment action when a balance remains;
- delivery action when delivery is still eligible;
- append-only payment history;
- order notes;
- cancellation action while cancellation remains eligible.

Expected copy includes:
- `Cobro y entrega`
- `Registrar cobro`
- `Marcar como entregado`
- `Pagos registrados`
- `Pedido pagado`
- `Entrega registrada`
- `Cancelar pedido`
- `Cobro registrado.`
- `Entrega registrada.`
- `Pedido cancelado.`
- `Entregar no cambia el pago. Cobrar no cambia la entrega.`
- `Cancelar el pedido no registra una devolución.`

### Collection/payment facts
Each new collection is an append-only `OrderPayment` fact.

For a collection, persist:
- order;
- exact amount in MXN minor units;
- kind = `collection`;
- local payment date supplied by the user;
- persisted effective ordering timestamp;
- UUID request key;
- normalized request hash;
- timestamps.

Existing TSK-006 advance rows remain valid historical facts. They do not need retroactive request keys because their idempotency boundary is the TSK-006 order-creation request.

New collection rows must be immutable under normal application behavior.

### Payment rules
A collection:
- must be > 0;
- must not exceed the current remaining balance;
- is forbidden when the order balance is already zero;
- is forbidden on a cancelled order;
- may be recorded before or after delivery;
- does not change fulfillment state.

Inside one database transaction and with the order row locked:
1. re-read current balance;
2. validate amount;
3. append the collection payment;
4. update exact `paid_minor`;
5. update exact `balance_minor`;
6. derive `payment_state`:
   - `pending` only when paid = 0;
   - `partial` when 0 < paid < total;
   - `paid` when paid = total.

No credits, negative balance, overpayment, automatic refund or payment deletion.

### Payment idempotency
Every collection submission is server-idempotent:
- UUID request key;
- same key + same normalized order/amount/date replays the same payment with no duplicate aggregate update;
- same key + different payload is rejected.

A visual disabled/loading button is required but is not the integrity mechanism.

### Local payment date
The user records a local calendar date for the collection.
Persist it as a local date fact; do not convert it through browser timezone or UTC in a way that can change the date.

The payment history orders deterministically by local payment date, then persisted effective ordering timestamp/ID where needed.

### Fulfillment transitions and history
Add an append-only `OrderFulfillmentEvent` for each fulfillment mutation created by this task:
- order;
- from state;
- to state;
- UUID request key;
- normalized request hash;
- effective timestamp;
- timestamps.

Normal application behavior may not update/delete fulfillment events.

TSK-007 authorizes only these user-triggered transitions:
- `confirmed | in_preparation | ready -> delivered`
- `confirmed | in_preparation | ready -> cancelled`

The existing TSK-006 capture default `confirmed` remains unchanged.

Do not invent a manual `ready` or `in_preparation` control here; those belong to production work.

### Delivery
Delivery:
- requires explicit confirmation;
- changes only `fulfillment_state` to `delivered`;
- appends one immutable fulfillment event;
- does not alter payment totals or payment state;
- is idempotent for the same request key/payload.

If already delivered, a different request must not create another delivery event.
A cancelled order cannot be delivered.

A delivered order may remain `pending` or `partial`.

### Cancellation
Cancellation:
- requires explicit confirmation;
- changes only `fulfillment_state` to `cancelled`;
- appends one immutable fulfillment event;
- preserves order lines, agreed prices, cost snapshots and all payment rows;
- preserves `paid_minor`, `balance_minor` and `payment_state`;
- never creates a refund or negative payment;
- is idempotent for the same request key/payload.

If any amount has already been received, confirmation must explicitly warn:
`Cancelar el pedido no registra una devolución.`

A delivered order cannot be cancelled.
A cancelled order cannot be cancelled again under a new request key.

### Exact arithmetic
No binary floating-point value is authoritative.

Collections use integer MXN minor units and the existing exact money parsing helpers.

Updating aggregate paid/balance must:
- detect signed-64-bit overflow;
- never clamp;
- never use JS money as server authority.

### UI behavior
Spanish-first `es-MX`, mobile-first.

#### Payment action
When balance > 0 and order is not cancelled:
- open an accessible payment form/dialog;
- show current remaining balance;
- default amount may be the full balance;
- fields:
  - `Importe recibido`
  - `Fecha del cobro`
- validation message:
  `Escribe un importe mayor que $0 y no mayor que el saldo.`
- preserve input on validation error;
- focus first invalid field;
- block duplicate submit visually.

When balance = 0:
- do not offer a misleading active collection action;
- show `Pedido pagado`.

#### Delivery action
When delivery is eligible:
- explicit confirmation:
  `¿Entregaste este pedido a {cliente}?`
- confirm action equivalent to `Sí, ya entregué`;
- explain that the remaining balance is preserved until collection.

When delivered:
- show `Entrega registrada` and do not expose another active delivery mutation.

#### Cancellation action
When cancellation is eligible:
- explicit destructive confirmation;
- explain the order will no longer be pending operational work;
- preserve history;
- if paid amount > 0, show the no-refund warning.

When cancelled:
- disable/hide further payment, delivery and cancellation mutations as appropriate;
- historical payment and order information remains readable.

### Status independence
Use the accepted labels:
Fulfillment:
- Confirmado
- En preparación
- Listo para entregar
- Entregado
- Cancelado

Payment:
- Pendiente de cobro
- Pago parcial
- Pagado

Never combine them into one synthetic state and never imply:
- paid = delivered;
- delivered = paid;
- cancelled = refunded.

## Required backend coverage
At minimum test:
- exact partial collection updates paid/balance/payment state;
- exact final collection sets paid/balance zero;
- collection > balance rejected atomically;
- zero/negative collection rejected;
- collection on paid order rejected;
- collection on cancelled order rejected;
- collection after delivery allowed;
- local payment date persisted exactly;
- payment rows append-only and immutable;
- payment idempotent replay;
- same payment request key + different payload rejected;
- delivery from confirmed succeeds without changing payment state;
- delivery from in_preparation/ready is accepted when those states already exist;
- delivery idempotency;
- cancelled -> delivered rejected;
- cancellation preserves lines/payments/snapshots and payment aggregates;
- cancellation with prior payment creates no refund;
- cancellation idempotency;
- delivered -> cancelled rejected;
- fulfillment events append-only and immutable;
- Inertia order detail exposes action URLs, request keys and payment history.

## Required Playwright flow
Real isolated mobile E2E at 320px and 390px:
1. create prerequisites and one priced product using existing UI;
2. create an order with an initial advance that leaves a balance;
3. open `Cobro y entrega`;
4. record a collection smaller than the balance using a local payment date;
5. verify `Cobro registrado.`, updated paid amount, remaining balance and `Pago parcial`;
6. mark the order delivered with explicit confirmation;
7. verify `Entrega registrada.` while payment remains partial;
8. verify payment history contains the initial `Anticipo` and the new `Cobro`;
9. create a second order with an advance;
10. cancel it with explicit confirmation;
11. verify `Pedido cancelado.`, `Cancelado`, preserved payment/balance, and visible no-refund warning during confirmation;
12. verify no horizontal overflow.

Do not weaken/replace existing purchase, recipe, product or order-capture E2E.

## Validation matrix
Run:
1. `docker compose build app`
2. `docker compose run --rm app composer install --no-interaction --prefer-dist`
3. `npm ci`
4. `docker compose run --rm app php artisan test`
5. `npx tsc --noEmit`
6. `npm run build`
7. idempotent cleanup of the known named E2E container if present
8. `npx playwright test e2e/order-operations.spec.ts --project=mobile`
9. `git diff --check`

## Path-count guardrail
Keep total visible changed paths for the complete TSK, including governance artifacts and bounded correctives, at **30 or fewer**. Prefer 18–24.

If the smallest safe complete implementation cannot remain <=30, stop BLOCKED rather than broadening.

## Explicit non-goals
Do not implement:
- production aggregation or `Iniciar preparación`;
- manual `ready` controls;
- Today/Home operational aggregation;
- refunds, credits or reversals;
- editing/deleting payment history;
- order-line edits after capture;
- changing agreed prices after capture;
- customer CRM;
- delivery routing/addresses;
- inventory/procurement;
- accounting/tax/invoicing;
- historical comparison analytics;
- auth/RBAC;
- multilingual UI;
- design-system rewrite;
- dependency changes or broad refactors.

## Completion rule
TSK-007 is complete only when:
- governed implementation exists on the bounded task branch;
- complete mechanical validation passes;
- collections are exact, append-only and idempotent;
- fulfillment transitions preserve independent payment state and durable event history;
- mobile flow passes at 320px and 390px;
- path guardrail passes;
- governed commit/push/PR/merge/integration/reconciliation/cleanup completes.
