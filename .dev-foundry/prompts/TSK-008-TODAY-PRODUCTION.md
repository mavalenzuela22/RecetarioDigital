# TSK-008 — Today & Production — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

## Read first
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-008-TODAY-PRODUCTION.md`
3. `docs/PRODUCT-DEFINITION-v1.md`
4. `docs/ARCHITECTURE-v1.md`
5. `docs/UX-PRINCIPLES-v1.md`
6. accepted `hoy` and `produccion` contracts in `docs/design/screens/flows.json`
7. `docs/design/implementation-guide/IMPLEMENTATION.md`

Do not modify `docs/design/**`.

## Baseline
Expected branch:
`tsk-008-today-production`

Expected parent baseline:
`26abb6d54eae0bd5f25f1de673cec4371cf342f9`

TSK-001 through TSK-007 are already closed and must remain passing.

## Goal
Implement TSK-008 exactly as governed in `docs/TSK-008-TODAY-PRODUCTION.md`:
- operational Today/Home;
- shared exact snapshot-based aggregation service;
- Production date/range view;
- atomic `confirmed -> in_preparation` start-production action;
- `in_preparation -> ready` action with durable fulfillment history;
- focused mobile UX and tests.

## Implementation constraints
- Laravel/Inertia/React/TypeScript/Tailwind only.
- Domain/business logic in services, not controllers/React.
- No new dependency.
- No migration/reporting store should be necessary; reuse existing order/line/payment/fulfillment facts.
- Money stays integer minor units/micros.
- Use configured business timezone/date.
- Preserve TSK-007 delivery/cancellation/payment invariants.
- Preserve immutable order-line snapshots.
- Do not recompute production economics from latest ingredient purchases.
- Keep total TSK visible paths <=30.
- Smallest safe complete change only.

## Failure policy
Run the full validation matrix.

If anything fails:
- preserve evidence;
- do not blind-retry;
- do not mutate product code for an unclassified validator/test defect;
- stop so Governance Author can classify before a bounded corrective.
