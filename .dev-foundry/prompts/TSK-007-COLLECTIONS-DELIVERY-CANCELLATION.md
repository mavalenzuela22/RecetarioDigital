# TSK-007 — Collections, Delivery & Cancellation — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

Read and obey:
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-007-COLLECTIONS-DELIVERY-CANCELLATION.md`
3. product authorities referenced by that task
4. existing TSK-006 order, order-line snapshot and advance-payment implementation
5. accepted `cobro` design under `docs/design/**` as read-only authority.

## Implement
Build the smallest safe complete post-order operational boundary:
- append-only idempotent collection payments;
- exact paid/balance/payment-state updates;
- local collection date;
- independent delivery transition;
- independent cancellation transition with no automatic refund;
- append-only fulfillment transition events;
- Spanish mobile `Cobro y entrega` order detail;
- payment history;
- explicit confirmation for delivery/cancellation;
- Pest and real mobile Playwright coverage.

## Critical invariants
- Do not use binary floats for money authority.
- Collection cannot exceed current locked balance.
- Collection changes payment only, never fulfillment.
- Delivery changes fulfillment only, never payment.
- Cancellation preserves payments, lines, agreed prices and cost snapshots and creates no refund.
- Delivered order cannot be cancelled.
- Cancelled order cannot be paid or delivered.
- Same request key + same normalized payload replays without duplicate side effects; same key + different payload rejects.
- Historical payment rows and fulfillment events are immutable.
- Do not implement production aggregation, Today dashboard, refunds, order editing or analytics.
- Do not modify `docs/design/**`.
- Do not change dependency manifests, Docker files or Playwright config.
- Do not weaken prior tests.

## Failure discipline
Run the complete validation matrix. Classify any failure before mutation. No blind retry.
If total visible changed paths would exceed 30, stop BLOCKED and report path pressure.
