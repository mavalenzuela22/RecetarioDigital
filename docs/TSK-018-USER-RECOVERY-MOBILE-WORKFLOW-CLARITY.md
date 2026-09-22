# TSK-018 — User Recovery, Mobile Reliability and Workflow Clarity

Status: **CLOSURE PASS — full deterministic validation green; promotion/reconciliation remains.**

Branch: `tsk-018-user-recovery-mobile-workflow-clarity`
Base main: `369baf5841cdc3a9dcab8a16a14fc60ce380acec`

## Goal

Resolve the confirmed Astra S2 recovery/mobile/workflow defects AUD-06 through AUD-16 without changing established domain semantics, exact economic arithmetic, authentication/authorization guarantees, historical snapshots, idempotency, or the accepted design authority.

The operator authorizes this TSK to proceed end-to-end through bounded MT implementation, corrective validation, full closure validation, promotion and reconciliation under the active repository governance. Stop only for a genuine product decision, authority conflict, unavailable required tooling, or an unbounded/new scope.

## MT structure

### MT-001 — Production workflow clarity (AUD-06 + AUD-13)

Status: **PASS**

- Make it explicit that `Marcar listo` completes the whole order when shown inside a product grouping; do not invent line-level fulfillment.
- Invalid/reversed production date ranges must visibly surface validation failure and must not silently present stale data as if the filter succeeded.
- Regressions cover 320 px and 390 px and preserve the corrected TSK-017 displayed-range/action consistency.
- Closure execution: `execution_b26afb5c778b59587f8d93b7d6838d2f666a017fc52a6d63a92e2598642bb47c`.

### MT-002 — Access/Auth recovery (AUD-07 + AUD-09 + AUD-10 + AUD-15)

Status: **PASS**

- Successful invitation creation lands on a navigable GET state so browser refresh does not produce 405.
- Browser Back after logout revalidates against server authority and does not restore usable/rendered private commercial state.
- Recovery-password validation is natural es-MX with sensible focus/recovery behavior.
- Invitation rows/emails/actions remain usable at 320 px without horizontal overflow or hidden Revocar.
- Session invalidation, CSRF, authorization, and invitation semantics remain intact.
- Closure execution: `execution_54923a5152ddab3cc3e26cc1af956f2c014c9f8d6f23c8859c1db6db077b1f3f`.

### MT-003 — Order reliability (AUD-08 + AUD-12)

Status: **PASS**

- Dirty order drafts are protected through visible Back/navigation with an explicit discard decision; clean/successful flows remain unblocked.
- Collection transport failure is surfaced as **not confirmed**, preserves amount/date/request key, and offers a safe retry.
- Collection retry uses the same idempotency key and remains append-only; backend replay semantics prevent duplicate collections.
- Mobile Playwright validates 320/390 uncertainty UI, preserved input, retry, one observable collection, and correct post-retry balance/history.
- Test harness blocks service workers only for the affected spec so Playwright request interception is deterministic; no production service-worker behavior is changed.
- Final closure execution: `execution_9d7c3fbc236c115bdcaebdee61044ec4ba5af5e8fabebe3492e889ccfbd294f9`.
- Final corrective was test-only: corrected the expected balance from `$0.00 MXN` to the mathematically correct `$25.00 MXN` for a $50 order with $10 advance and $15 collection.

### MT-004 — Recipe recovery and costing clarity (AUD-11 + AUD-14)

Status: **PASS**

- Stale concurrent recipe edits must become a recoverable user-facing conflict rather than generic English 409/broken-page behavior.
- Cost presentation while editing must clearly distinguish saved-version cost from draft/not-yet-calculated/incomplete cost.
- Preserve immutable recipe-version semantics and exact costing.
- Closure execution: `execution_3e841bc8e27b4408ce935882ebbda3c7f1c506054cf97e4885924ddb0ab920f5`.
- Closure gates: TypeScript PASS, production build PASS, RecipeTest 10/92 PASS, mobile Playwright 320/390 PASS, `git diff --check` PASS, path policy 0 violations.

### MT-005 — Today completion state (AUD-16)

Status: **PASS**

- Distinguish “no orders existed today” from “today’s work is complete”.
- Preserve non-zero sales/cost/profit truth after the final order is completed.
- Validate realistic populated 320/390 states.
- Closure execution: `execution_315376c033aa91aac900805dd66d7fb996c5f68d385ba970b4f925f08ed1a3f7`.
- Closure gates: TypeScript PASS, production build PASS, TodayProductionTest 8/120 PASS, mobile Playwright 5/5 PASS, `git diff --check` PASS, path policy 0 violations.

## Validation policy

Each MT requires:
- regression reproduction before/with the fix;
- focused Pest where domain/controller behavior changes;
- 320/390 Playwright for affected mobile flows;
- TypeScript and production build for frontend changes;
- `git diff --check`;
- path-policy zero violations.

TSK closure additionally requires:
- full SQLite Pest;
- full Percona 8.4 gate;
- npm audit;
- TypeScript;
- production Vite build;
- deterministic mobile Playwright coverage with isolated test state where required;
- release build/verification;
- semantic review;
- promotion-ready validation and governed promotion.

## Scope constraints

- No TSK-019 polish/OBS decisions.
- No TSK-020 design-conformance overhaul.
- No Google OAuth credential acceptance.
- No broad refactors unrelated to AUD-06..AUD-16.
- UI changes must remain compatible with `docs/design/**`; visual conformance itself remains TSK-020.


## Full closure

Status: **PASS**

- Deterministic closure execution: `execution_0358eebccc1c75bae2856eeb0f87236ebc7fc31ee5e1081dba65a9025b721831`.
- Full SQLite Pest: 105 tests / 1117 assertions PASS.
- Full Percona 8.4: 105 tests / 1117 assertions PASS.
- npm audit: 0 vulnerabilities.
- TypeScript noEmit: PASS.
- Production Vite build: PASS.
- Mobile Playwright: PASS under deterministic per-spec fresh-server/database topology.
- Release build + verify: PASS.
- `git diff --check`: PASS.
- Path policy: 0 violations.
- Closure harness restored Composer dev dependencies before backend gates, restored npm dependencies before frontend gates, isolated Playwright per spec, and ran release packaging last.
