# TSK-017 MT-002 — AUD-02 Production Selected-Range / Action Consistency

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

## Authority

Read before mutation:
- docs/TSK-017-PILOT-BLOCKER-CORRECTNESS-ECONOMIC-TRUST.md
- docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md
- resources/js/Pages/Production/Index.tsx
- app/Http/Controllers/ProductionController.php
- app/Http/Requests/ProductionRangeRequest.php
- app/Services/StartProduction.php
- e2e/today-production.spec.ts
- relevant order/production feature tests as read-only reconnaissance.

Current branch:
`tsk-017-pilot-blocker-correctness-economic-trust`

This MT addresses only AUD-02.

## Confirmed defect

`resources/js/Pages/Production/Index.tsx` creates two independent forms from the same initial props:

- `dates = useForm({ from, to })`
- `start = useForm({ from, to })`

The visible range filter mutates `dates`, but "Iniciar preparación" posts `start`. After the user changes A -> B and refreshes Production, the acted-on range can remain stale at A.

## Required outcome

The range shown to the user and the range acted on by "Iniciar preparación" must have one authoritative source of truth.

After the page reflects range B, starting production must act on B and must not mutate eligible orders that belong only to A.

Do not redesign production flow or change domain semantics.

## Required execution order

1. Re-observe current UI/controller/service behavior and existing tests.
2. Add a focused regression that demonstrates the stale A -> B failure before the production fix where feasible.
3. Implement the smallest coherent fix in `Production/Index.tsx`.
4. Run the focused browser regression.
5. Run relevant production feature tests / today-production regression.
6. Run TypeScript noEmit and git diff check.

## Regression scenario

Build a deterministic browser scenario in the existing `e2e/today-production.spec.ts` or a new narrowly focused production spec:

- create or provision two eligible confirmed orders:
  - order A: delivery date A;
  - order B: delivery date B;
- open Production for A;
- change the visible date range to B and submit "Actualizar fechas";
- assert the page now displays only/appropriately reflects B;
- click "Iniciar preparación";
- prove order B transitions to `in_preparation`;
- prove order A remains `confirmed` / untouched.

The test must fail against the stale dual-form implementation and pass after the fix.

Prefer browser-visible assertions plus authoritative follow-up state via UI/navigation. If a minimal test setup helper is required inside e2e, keep it local to the test boundary.

## Implementation guidance

Prefer one range source of truth. Valid examples include:
- posting the current `dates.data` through the start action;
- a single shared form/state used by both filter and action.

Avoid synchronization effects between two duplicated forms unless absolutely necessary; duplicated state is the root cause.

Preserve:
- current route/controller semantics;
- normalized inclusive range behavior;
- ready action behavior;
- existing mobile layout;
- existing success/error UX.

## Scope

May mutate:
- resources/js/Pages/Production/Index.tsx
- e2e/today-production.spec.ts
- optionally one new e2e production-range spec if cleaner
- focused production feature test only if genuinely needed.

Do not modify controllers/services/models/migrations/routes unless the regression proves the defect cannot be fixed coherently in the UI boundary; if so, report rather than broaden casually.

Do not touch MT-001 code except preserve its dirty validated state.

## PASS gate

- focused A -> B browser regression PASS;
- existing today-production browser flow remains PASS;
- relevant focused Pest production coverage PASS where present;
- `npx tsc --noEmit` PASS;
- `git diff --check` PASS;
- path policy PASS.
