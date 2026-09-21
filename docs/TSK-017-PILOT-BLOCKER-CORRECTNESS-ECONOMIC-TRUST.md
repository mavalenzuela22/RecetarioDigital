# TSK-017 — Pilot Blocker Correctness and Economic Trust

## Status

Governance boundary activated from the persisted Astra adversarial remediation authority.

Implementation remains **BLOCKED UNTIL TSK-016 IS PROMOTED AND RECONCILED**.

The operator explicitly authorized one bounded operator-assisted exception for the TSK-016 Git/GitHub lifecycle promotion because the Foundry Runner repository-transaction state serializer rejects observed string arrays longer than 32 entries while the current valid TSK-016 change-set exceeds that limit. The exception is limited to commit -> push -> PR -> merge -> reconciliation -> cleanup for this already validated TSK-016 boundary.

Do not implement TSK-017 on the TSK-016 branch. Do not use the operator-assisted exception for product implementation, validation substitution, or unrelated/future repository lifecycle operations without separate authority.

## Authority

Primary remediation authority:

`docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md`

Activation handoff:

`.dev-foundry/prompts/CONTINUATION-ASTRA-AUDIT-REMEDIATION.md`

Repository authority was re-observed before activation:
- project profile: `recetario-digital-operating-v1`;
- governed runner: Foundry Runner Mac;
- existing task documentation reaches TSK-016;
- no prior TSK-017 task authority or execution contract existed;
- therefore TSK-017 remains the next available task number.

## Objective

Remove the confirmed pilot-blocking correctness defects and the cross-cutting monetary-display trust defect without weakening authentication, authorization, exact arithmetic, historical snapshots, idempotency, or v1 scope discipline.

This task contains exactly these remediation MTs:

1. MT-001 — AUD-01 concurrent administrator invariant.
2. MT-002 — AUD-02 Production selected-range/action consistency.
3. MT-003 — AUD-03 stale inactive order-line explainability and recovery.
4. MT-004 — AUD-04 first-use navigation to recipe/product catalog.
5. MT-005 — AUD-05 manual price confirmation amount consistency.
6. MT-006 — AUD-19 global monetary display precision.

Do not absorb AUD-06 through AUD-18 or OBS-01 through OBS-03 into this task except where a shared root-cause change is technically inseparable and explicitly documented.

## Execution discipline

For every MT:

1. Re-observe the current implementation.
2. Reproduce the defect before changing code where feasible.
3. Add a regression test that fails for the observed defect.
4. Implement the smallest coherent fix.
5. Run focused validation for the MT.
6. Run relevant domain regression only after focused PASS.
7. Run the expensive full closure matrix only after all MTs are focused-green.

No assistant polling for delegated execution.

## Mandatory reconnaissance — observed on activation

### MT-001 / AUD-01

Current implementation in `AccessAdminController::toggleUser` performs:
- a count of active administrators;
- then, in a separate operation, updates the target user.

The count and update are not protected by one atomic serialization boundary. Two concurrent administrators can therefore both observe count > 1 and commit deactivation.

Current regression coverage in `tests/Feature/AccessAdministrationTest.php` is sequential only.

The users migration defines:
- `active` boolean default true;
- `is_admin` boolean default false;
- indexes on both columns.

The Percona gate currently runs the normal Pest suite against Percona 8.4 but contains no dedicated genuine concurrent-request admin invariant test.

Required outcome:
At least one active administrator must remain after any legal concurrent requests.

Required regression:
Use genuine concurrent requests against Percona/MySQL-compatible storage. A sequential count assertion is not sufficient.

Do not weaken:
- self-deactivation protection;
- admin middleware/authorization;
- inactive-session enforcement.

### MT-002 / AUD-02

`resources/js/Pages/Production/Index.tsx` currently creates two independent Inertia forms initialized from the same props:
- `dates = useForm({ from, to })`;
- `start = useForm({ from, to })`.

Updating the visible filter mutates `dates`, while "Iniciar preparación" submits `start`. This is a direct stale-range source.

Required outcome:
The displayed range and the acted-on range must have one source of truth.

Regression:
Load day A, change to day B, verify day-B data, start production, and prove only day-B eligible orders transition.

### MT-003 / AUD-03

`OrderController::create` reloads only products with `active = true`.

The order form keeps selected lines in client form state. If a previously selected product becomes inactive elsewhere and the page revalidates/reloads, the selectable product list can no longer explain the retained line state.

Required outcome:
Every amount included in the draft total must correspond to an explicit visible line/state with a recoverable resolution path, or be removed consistently from both form state and total.

Regression:
Use the two-tab mixed-order scenario from the audit.

### MT-004 / AUD-04

Routes for recipes and products exist, but Home currently exposes:
- production;
- register purchase;
- take order;
- admin access when applicable.

It exposes no normal navigation to recipe or product setup. The empty-day state still offers "Tomar pedido" even when no catalog exists.

Required outcome:
A first-use purchase -> recipe -> product -> price -> order path must be discoverable using visible navigation only.

Regression:
Start from an empty business database and complete first catalog setup without typing known URLs.

### MT-005 / AUD-05

`resources/js/Pages/Products/Show.tsx` uses `ProductMoneyText` for both stored minor-unit integers and raw manual decimal input.

`ProductMoneyText("5")` interprets the raw string as minor units and displays 0.05-style semantics, while the server later persists the manual decimal as normal currency. Decimal strings can also be malformed by the same helper.

Required outcome:
Confirmation text must display exactly the amount that will be persisted and used by later orders.

Regression:
Cover integer and decimal manual prices, confirmation text, persisted value, and final displayed value.

### MT-006 / AUD-19

Money presentation is currently fragmented:
- `OrderUI.formatMinor` renders 2 decimals;
- `PurchaseUI.Money` accepts arbitrary scale;
- `ProductUI.ProductMoney` accepts arbitrary scale;
- several ordinary user-facing cost/profit surfaces explicitly pass scale 6;
- `OperationalSummary` exposes money labels through separate minor/micro formatting paths.

Required outcome:
Create/reuse one central ordinary-MXN formatter with exactly 2 visible decimals for ordinary currency amounts.

Internal integer/micro precision must remain unchanged.

Specialized unit-cost surfaces may use bounded sub-cent precision only when explicitly justified, with meaningless trailing zeros removed.

Do not scatter ad-hoc `toFixed(2)` calls.

## Focused validation policy

Each MT gets its own focused test boundary.

For MT-001 specifically, the focused gate is not PASS until a real concurrent Percona reproduction is green.

After all six MTs are focused-green, run:
- affected neighboring Pest suites;
- affected 320 px and 390 px Playwright flows;
- full SQLite Pest;
- full Percona 8.4 gate;
- npm audit;
- TypeScript noEmit;
- Vite production build;
- full mobile Playwright;
- release build + verification;
- git diff check;
- semantic review.

## TSK-016 lifecycle prerequisite and infrastructure blocker

The latest durable TSK-016 corrective execution is:

- task: `TSK-016-C008-REQUEST-RELOAD-AND-ASSOCIATIVE-ASSERTION`;
- execution: `execution_530d34443e88b371fbcee48df182fa5a5d4ca75f108b26826b40998d082715e1`;
- final status: `passed`;
- path policy: PASS;
- validation: PASS;
- post-execution validation: PASS;
- evidence complete: true.

Its full matrix includes SQLite Pest, Percona 8.4, npm audit, TypeScript, Vite build, mobile Playwright, release verification, and git diff check, all exit 0.

A repository transaction attempt for TSK-016 promotion using transaction id `tsk016-promotion-v003` failed before any repository side effect with:

`validation_service_failed`

A subsequent read-only repository preflight passed for:
- branch `tsk-016-google-login-invitation-access-admin`;
- HEAD `7777ee74899e305c13a7dee347f93de8b491283b`;
- expected dirty state;
- 58 visible changes and 0 hidden changes at that observation.

Therefore the blocker is classified as repository-lifecycle infrastructure, not product validation.

Do not blindly retry the failed transaction. Re-observe service state first.

## Next governed action

The operator-authorized TSK-016 promotion path is now the persisted script:

`docs/operator-actions/TSK-016-operator-assisted-promotion.sh`

The script is intentionally bounded to the current TSK-016 promotion and must:

1. Re-observe and guard the expected TSK-016 branch, base HEAD, origin/main base, and exact approved visible path manifest before committing.
2. Commit the already validated TSK-016 boundary, push the source branch, create/reuse the matching PR, merge it, reconcile local main with origin/main, verify TSK-016 ancestry, and clean up the task branch.
3. Abort rather than broaden scope if the repository state or GitHub state differs from the guarded expectations.
4. Preserve TSK-016 C008 as the validation authority; this operator-assisted lifecycle path does not substitute or rerun implementation validation.

After the script reports PASS:

5. Re-observe reconciled main through Foundry Runner Mac and confirm the Astra backlog and this TSK-017 authority are integrated.
6. Create branch `tsk-017-pilot-blocker-correctness-economic-trust` from the reconciled main HEAD through the normal governed repository transaction boundary.
7. Begin MT-001 with focused reproduction and genuine concurrent Percona regression before any implementation change.
