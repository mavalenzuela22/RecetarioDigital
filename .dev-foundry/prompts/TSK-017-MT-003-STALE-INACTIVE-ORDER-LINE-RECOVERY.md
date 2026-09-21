# TSK-017 MT-003 — AUD-03 Stale Inactive Order-Line Explainability and Recovery

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

## Authority

Read before mutation:
- docs/TSK-017-PILOT-BLOCKER-CORRECTNESS-ECONOMIC-TRUST.md
- docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md
- resources/js/Pages/Orders/Create.tsx
- resources/js/Components/OrderUI.tsx
- app/Http/Controllers/OrderController.php
- app/Http/Requests/StoreOrderRequest.php
- app/Services/SaveOrder.php
- e2e/order-capture.spec.ts

Current branch:
`tsk-017-pilot-blocker-correctness-economic-trust`

This MT addresses only AUD-03.

## Confirmed defect

The order-create page receives only active products.

Client form state retains selected lines across Inertia-preserved reload/revalidation. Rendering currently does:

`const product = products.find(...); if (!product) return null;`

while the order total is still calculated from `form.data.lines`.

If a product already selected in a draft becomes inactive in another tab and the order page revalidates/reloads:
- the line can disappear from the visible UI;
- its amount remains included in the draft total;
- SaveOrder later rejects inactive products.

That creates an unexplained amount and no obvious recovery path.

## Required outcome

Every amount included in the draft total must correspond to an explicit visible line/state.

For a retained line whose product is no longer in the active product catalog:
- keep the line visibly represented;
- clearly mark that the product is no longer available for new orders;
- preserve its quantity/agreed price only as draft data long enough for the user to understand what happened;
- provide an obvious "Quitar" recovery action;
- prevent successful submit while any stale/unavailable line remains, or rely on the existing server validation while surfacing the line-specific error in a visible understandable way;
- never silently count an invisible line in totals.

Do not reactivate unavailable products and do not weaken the server-side active-product validation in SaveOrder.

## Preferred minimal design

Make each order-line form entry carry enough presentation snapshot data to remain renderable if its product disappears from the current active `products` prop, e.g. product name/sale-unit snapshot captured when the line is added.

If adding client-only metadata to `OrderLineInput`, ensure Laravel's validated payload continues to persist only authoritative server-recognized fields. Do not make client snapshots authoritative for product identity or pricing.

When current `products` no longer contains the line's product id:
- render the existing line card using the snapshot name;
- add a visible unavailable/inactive warning;
- retain quantity/price fields or make them read-only as appropriate, but the recovery must be explicit;
- keep `Quitar`;
- prevent accidental save until resolved, with clear message.

## Required regression

Add a deterministic two-tab browser scenario, preferably in `e2e/order-capture.spec.ts`:

1. Create two active configured/priced products.
2. In tab A, open new order and add both products; verify the expected combined total.
3. In tab B, open product configuration for one selected product and deactivate it.
4. Return to tab A and trigger an Inertia reload/revalidation that refreshes page props while preserving draft state.
5. Prove:
   - the still-active line remains visible;
   - the now-inactive/stale line remains visibly represented and marked unavailable;
   - the total is explainable by visible lines;
   - the unavailable line has a visible recovery action;
   - saving with it unresolved does not create an order;
   - after removing the unavailable line, total updates consistently and the remaining order can be saved.

The regression must fail against the current `return null` behavior.

## Scope

May mutate:
- resources/js/Pages/Orders/Create.tsx
- resources/js/Components/OrderUI.tsx
- e2e/order-capture.spec.ts
- one focused order feature test only if genuinely necessary.

Do not modify:
- SaveOrder active-product invariant;
- StoreOrderRequest semantics;
- Product model/migrations;
- unrelated order operations;
- design package.

Preserve all dirty validated changes from MT-001 and MT-002 untouched.

## Validation discipline

Before Playwright:
- `npx tsc --noEmit`
- `npm run build`
- remove only stale Docker containers publishing host port 18080.

Then:
- focused order-capture Playwright mobile;
- relevant order feature Pest;
- git diff check.

## PASS gate

- stale line remains visible and explainable after product deactivation/reload;
- explicit recovery is present;
- no invisible amount remains in total;
- unresolved stale line cannot result in a saved order;
- removal restores consistent total and permits valid save;
- TypeScript PASS;
- Vite build PASS;
- focused browser PASS;
- focused Pest PASS;
- path policy PASS;
- git diff check PASS.
