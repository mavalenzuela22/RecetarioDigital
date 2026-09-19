# TSK-006 — Orders & Immutable Economic Snapshots — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

Read and obey:
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-006-ORDERS-SNAPSHOTS.md`
3. product authorities referenced by that task
4. TSK-003 ingredient purchase economics
5. TSK-004 recipe/version/current-cost semantics
6. TSK-005 product current-cost and append-only price semantics
7. accepted order design under `docs/design/**` as read-only authority.

## Implement
Build the smallest safe complete order-capture boundary:
- customer name only, no CRM;
- multi-product order;
- agreed line price snapshots;
- attributable cost snapshots from current product economics;
- local delivery date/time;
- notes;
- exact initial advance / total / balance;
- append-only positive advance payment fact;
- confirmed fulfillment default;
- pending/partial/paid payment state;
- server idempotency;
- Spanish mobile Orders index/create/show flow;
- Pest and real mobile Playwright coverage.

## Critical invariants
- Never use binary floats as money authority.
- Order line price/cost snapshots never rewrite after later product/recipe/ingredient changes.
- Incomplete cost remains incomplete/null, never zero.
- Active product eligibility is rechecked at save.
- Do not partially save when one selected product became unavailable.
- Same request key + same normalized payload replays; same key + different payload rejects.
- Payment and fulfillment are independent axes.
- Quantity is integer >=1.
- Advance cannot exceed order total.
- Do not implement later collections, delivery transitions, production, cancellation or analytics.
- Do not modify `docs/design/**`.
- Do not change dependency manifests, Docker files or Playwright config.
- Do not weaken TSK-003/004/005 behavior or tests.

Use existing exact helpers where safe and bounded, especially TSK-005 product costing and decimal-money parsing. Avoid broad refactors solely for reuse.

## Failure discipline
Run the full contract validation matrix. Classify each failure before corrective mutation. No blind retry.

If the complete visible changed-path set would exceed 30, stop BLOCKED and report why.
