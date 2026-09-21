# TSK-017 MT-001 C006 — Signal-Released Lock Barrier

Bounded corrective after C005.

## Classified C005 result

C005 finally proved genuine request-level concurrency against Percona:

- focused AccessAdministration Pest: PASS;
- two independently authenticated HTTP POST requests entered the database path;
- Percona observed two distinct application waiters:
  - DB_CONCURRENT_WAITERS=2
  - CONCURRENCY_OBSERVED=PASS
- server concurrency: PASS.

However both baseline requests eventually returned HTTP 500.

The harness currently holds the administrator row locks for 60 seconds. That exceeds or races the database lock-wait timeout, so the two valid requests time out while waiting for the harness-owned lock instead of being released to complete the intended race. The 500s are therefore an instrumentation side effect after concurrency was successfully established.

## Corrective objective

Replace the fixed-duration row-lock sleep with a **signal-released deterministic barrier**.

The lock-holder must:

1. begin one transaction;
2. lock the administrator rows in stable id order;
3. print LOCK_CONNECTION_ID and LOCK_READY;
4. hold the transaction while waiting for a harness control signal;
5. commit immediately after the signal;
6. have a bounded safety timeout so it can never hang indefinitely.

The host-side harness must:

1. launch both authenticated POST requests;
2. observe >=2 simultaneous Percona waiters;
3. once `CONCURRENCY_OBSERVED=PASS` is established, immediately send the release signal;
4. then wait for the lock-holder and both HTTP requests to finish;
5. query authoritative final state.

## Recommended implementation

Use a host temporary control directory mounted only into the lock-holder container, for example:

- mount `$phase_tmp/control:/tmp/harness-control`;
- lock-holder loops on `file_exists('/tmp/harness-control/release')` with short bounded sleeps;
- after observer PASS, host creates `$phase_tmp/control/release`;
- safety deadline ~20–30 seconds;
- on any observer failure, create the release signal before diagnostics/return so requests and transaction are not left waiting.

Do not add any production hook or application sleep.

## Preserve all established proof properties

Keep:
- clean HEAD baseline snapshot;
- current worktree fixed phase;
- ephemeral published port;
- two distinct authenticated sessions;
- X-XSRF-TOKEN handling;
- `active=0` boolean wire payload;
- multi-worker PHP server;
- direct Percona performance_schema observation;
- >=2 distinct database waiters;
- full cleanup;
- response status/location diagnostics.

## Required A/B result

Baseline HEAD must prove:
- DB_CONCURRENT_WAITERS >= 2;
- CONCURRENCY_OBSERVED=PASS;
- release signal issued only after that proof;
- both requests complete without harness-induced lock timeout;
- BASELINE_ACTIVE_ADMIN_COUNT=0;
- BASELINE_REPRODUCTION=PASS.

Fixed worktree must prove:
- same real concurrency observation;
- release signal issued after proof;
- FIXED_ACTIVE_ADMIN_COUNT >= 1;
- FIXED_INVARIANT=PASS.

If the fixed worktree current transaction/locking strategy does not satisfy this once the harness is correct, adjust only `AccessAdminController.php` based on evidence.

## Scope

May modify only:
- scripts/test-percona-concurrent-admin.sh
- app/Http/Controllers/AccessAdminController.php only if A/B evidence requires it.

Do not change request validation, routes, middleware, models, migrations, UI, design authority, or unrelated findings.

## Validation

PASS requires:
- focused AccessAdministration Pest PASS;
- complete baseline + fixed Percona A/B harness PASS;
- git diff --check PASS.
