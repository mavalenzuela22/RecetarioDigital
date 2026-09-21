# TSK-017 MT-001 C005 — Valid Boolean Wire Payload

Bounded corrective after C004.

## Classified C004 result

C004 reached:
- path policy PASS;
- focused AccessAdministration Pest PASS;
- authentication and XSRF PASS;
- multi-worker PHP server PASS;
- both concurrent POST clients returned HTTP 302 while the harness lock was still held;
- Percona saw no application lock waiters.

Repository re-observation shows `ToggleUserAccessRequest` validates:
`active => ['required', 'boolean']`.

The harness currently sends:
`active=false`

Over an HTTP form post this is the literal string `"false"`, which does not satisfy Laravel's boolean validator. Both requests are therefore redirected by validation before `AccessAdminController::toggleUser` executes. This exactly matches the C004 evidence: fast 302 responses and zero application DB waiters.

## Corrective

Modify only:
- scripts/test-percona-concurrent-admin.sh
- app/Http/Controllers/AccessAdminController.php only if the completed A/B proof demonstrates the current lock strategy is insufficient.

Change the concurrent deactivation payload to a wire value accepted by Laravel's boolean rule, preferably:
`active=0`

Do not alter validation rules, request classes, routes, middleware, models, migrations, or UI.

## Preserve all established harness fixes

Keep:
- isolated clean HEAD baseline;
- current dirty worktree fixed phase;
- ephemeral app port;
- independent authenticated sessions/cookie jars;
- X-XSRF-TOKEN handling;
- multi-worker PHP server;
- harness-owned administrator row-lock barrier;
- direct observation from the already-running Percona container;
- bounded cleanup;
- Percona proof of >=2 distinct simultaneous application waiters;
- authoritative final active-admin counts.

## Strong diagnostics

Capture response headers or at minimum Location/status for each POST so a future 302 can be classified. A 302 is acceptable only if the request actually executed the controller path; the required Percona waiter evidence is authoritative for that.

## PASS gate

Baseline HEAD must prove:
- `DB_CONCURRENT_WAITERS>=2`
- `CONCURRENCY_OBSERVED=PASS`
- after barrier release, both legal requests complete
- `BASELINE_ACTIVE_ADMIN_COUNT=0`
- `BASELINE_REPRODUCTION=PASS`

Fixed worktree must prove:
- same genuine concurrency observation
- `FIXED_ACTIVE_ADMIN_COUNT>=1`
- `FIXED_INVARIANT=PASS`

Also:
- focused AccessAdministration Pest PASS;
- git diff --check PASS.

Do not claim PASS without both baseline vulnerability proof and fixed invariant proof.
