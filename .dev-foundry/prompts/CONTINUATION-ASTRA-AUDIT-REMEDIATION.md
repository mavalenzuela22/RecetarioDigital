# RecetarioDigital — CONTINUATION / ASTRA AUDIT REMEDIATION START

Operate as Governance Author for RecetarioDigital / EmprendimientoOS v1.0 using the governed repository workflow.

## Mandatory first read

Read this persisted authority first:

`docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md`

Treat it as the remediation handoff for the independent Astra adversarial audit.

Do not rely on chat memory for the finding inventory.

## Current objective

Begin remediation with the first planned boundary:

**TSK-017 — Pilot Blocker Correctness and Economic Trust**

The backlog intentionally limits the audit remediation to only three TSKs total:
- TSK-017 — pilot blockers + monetary display precision
- TSK-018 — recovery/mobile/workflow reliability
- TSK-019 — polish + explicit product decisions

Before activating a task number, re-confirm the next available TSK from repository authority. If TSK-017 remains available, use it; otherwise preserve the scope and use the next governed number.

## TSK-017 planned MTs

1. AUD-01 — concurrent administrators can leave zero active admins.
2. AUD-02 — Production acts on stale/previous date range.
3. AUD-03 — stale inactive order line disappears but remains economically included.
4. AUD-04 — first-use navigation cannot discover recipe/product setup.
5. AUD-05 — manual price confirmation displays a different amount.
6. AUD-19 — user-facing MXN values leak six-decimal internal precision.

Do not add AUD-06+ to this task except where a shared root-cause change is technically inseparable and explicitly justified.

## Critical execution rule

For every finding:

1. independently re-observe the current implementation;
2. reproduce the finding before changing code where feasible;
3. add a regression test that fails for the observed defect;
4. implement the smallest coherent fix;
5. run focused validation first;
6. only after focused PASS run the expensive full regression matrix.

Do not repeat the TSK-016 mistake of repeatedly running the entire expensive matrix before the focused failure is green.

## Mandatory reconnaissance before implementation

Before writing tests/fixes, inspect:
- nearby existing tests and E2E conventions;
- framework APIs actually available;
- model defaults and DB defaults where relevant;
- SQLite vs Percona semantic differences;
- existing money formatting helpers/design-system primitives;
- current mobile navigation patterns.

Do not invent framework helpers by analogy.

## AUD-01 requirement

Sequential last-admin tests are insufficient.

The final regression must exercise genuine concurrent requests against Percona/MySQL-compatible storage and prove the invariant that at least one active admin remains.

Do not weaken self-deactivation or admin authorization rules.

## AUD-19 requirement

Internal precision remains exact.

Ordinary user-facing MXN must not show storage precision such as:
- $28.700000
- $32.800000
- $10.000000
- $1.000000

Establish/reuse one central money formatter rather than scattering ad-hoc formatting calls.

Default visible money policy: 2 decimals for ordinary currency amounts. Specialized sub-cent unit-cost presentation requires explicit bounded semantics.

## Validation strategy

Use staged gates:

### Focused
Run only the directly affected feature/E2E tests until the finding is reproducibly green.

### Domain regression
Run relevant neighboring suites and 320/390 Playwright flows.

### Full task closure
Then run the full governed matrix, including:
- SQLite Pest
- Percona 8.4
- npm audit
- TypeScript
- Vite build
- mobile Playwright
- release verification
- git diff check

Perform semantic review after mechanical PASS.

## No polling

Respect the repository no-polling rule for delegated execution. After an async request is accepted, wait for Foundry wakeup unless the operator explicitly asks to inspect status.

## Promotion

Do not claim the task closed until its validated branch is promoted/reconciled through the governed repository lifecycle.

If the repository transaction service is still unavailable, classify that infrastructure issue separately from product validation and preserve all work without blind retries.

## After TSK-017

Do not automatically absorb the remaining audit findings into TSK-017.

Continue according to the persisted backlog:
- TSK-018 for S2 workflow/recovery/mobile findings;
- TSK-019 for S3 polish and explicit OBS decisions.

Google OAuth real remains a separate unverified acceptance boundary until test credentials are configured.
