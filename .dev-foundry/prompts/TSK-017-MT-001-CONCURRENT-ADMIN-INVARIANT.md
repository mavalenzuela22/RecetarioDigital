# TSK-017 MT-001 — AUD-01 Concurrent Administrator Invariant

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

## Authority

Read before changing code:
- docs/TSK-017-PILOT-BLOCKER-CORRECTNESS-ECONOMIC-TRUST.md
- docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md
- app/Http/Controllers/AccessAdminController.php
- tests/Feature/AccessAdministrationTest.php
- scripts/test-percona.sh
- relevant auth/session routes and middleware only as read-only reconnaissance unless an allowed path below explicitly permits mutation.

Current branch:
`tsk-017-pilot-blocker-correctness-economic-trust`

This MT addresses only AUD-01.

## Defect

`AccessAdminController::toggleUser` currently counts active administrators and later updates the target user in a separate operation. Two concurrent administrators can both observe count > 1 and both commit deactivation, leaving zero active administrators.

Sequential assertions are insufficient.

## Required execution order

1. Re-observe the implementation, tests, MySQL/Percona behavior, Laravel transaction APIs, auth/session behavior, and available process/concurrency facilities.
2. Build a focused regression harness using **genuine concurrent requests/processes against the same disposable Percona 8.4 database**.
3. Before changing production code, execute that harness against the existing vulnerable implementation and capture evidence that it can reproduce the zero-active-admin invariant violation. If deterministic synchronization is required, keep it in test/harness code; do not add a test-only production backdoor.
4. Add/adjust focused automated regression coverage.
5. Implement the smallest coherent production fix.
6. Re-run the genuine concurrent Percona regression and prove that at least one active administrator remains.
7. Run the existing AccessAdministration feature tests.
8. Run `git diff --check`.

Do not proceed from step 3 to implementation merely because a sequential test fails. The concurrency proof is mandatory.

## Correctness constraints

The fix must be storage-safe for Percona/MySQL-compatible production behavior. Prefer a transaction/locking strategy with deterministic lock acquisition over application-process mutexes.

The invariant is:
> after any legal concurrent access-administration requests, at least one active administrator remains.

Preserve:
- self-deactivation protection;
- administrator authorization;
- inactive-session enforcement;
- existing activation/deactivation behavior for regular users;
- existing error semantics where practical.

Do not weaken the rule to eventual consistency.

Avoid deadlock-prone lock ordering. If row locking is used, acquire relevant locks in a stable deterministic order and re-observe authoritative rows inside the transaction instead of trusting stale route-model state.

## Genuine concurrency requirement

The focused Percona test must involve at least two independently executing request/process contexts sharing the same Percona database and overlapping in time. Merely calling the controller twice sequentially, using two sequential HTTP test calls, or asserting a count inside one transaction does not qualify.

It is acceptable to add a dedicated script such as:
`scripts/test-percona-concurrent-admin.sh`
if that is the cleanest repeatable boundary.

The harness must:
- create/prepare exactly the admin state it needs;
- execute the two deactivation attempts concurrently;
- observe both request outcomes;
- query authoritative Percona state afterward;
- fail if active admin count becomes 0;
- clean up its disposable environment.

Do not require operator babysitting.

## Scope

Allowed product/test mutations are intentionally narrow:
- app/Http/Controllers/AccessAdminController.php
- tests/Feature/AccessAdministrationTest.php
- tests/Feature/ConcurrentAccessAdministrationTest.php (optional new focused test)
- scripts/test-percona-concurrent-admin.sh (optional new focused harness)

Do not modify unrelated UI, routes, migrations, models, auth rules, design package, or other AUD findings.

## Completion report

Report:
- exact pre-fix concurrency reproduction and result;
- root cause;
- exact locking/transaction strategy;
- post-fix concurrent Percona result;
- focused feature-test result;
- git diff check;
- changed paths;
- any limitation preventing a genuine request-level concurrent proof.

Do not claim PASS if the genuine concurrent Percona proof is missing.
