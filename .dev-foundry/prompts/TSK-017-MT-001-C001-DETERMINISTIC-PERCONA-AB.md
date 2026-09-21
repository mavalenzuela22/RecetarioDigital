# TSK-017 MT-001 C001 — Deterministic Concurrent Percona A/B Harness

This is a bounded corrective for the failed execution of TSK-017 MT-001 / AUD-01.

## Failure classification

Previous execution:
- request: req_6f9836220671fcf3b6ab2b195680b02d
- execution: execution_bd198118a44a495c2b76bd158662adb6f64fd57de382f8a9ba3c87389d026e4d

The executor produced a plausible transactional row-locking fix, but validation never reached a valid concurrency verdict because:
1. the focused feature command used `php artisan test`, unavailable in that image state;
2. the Percona harness attempted to publish fixed host port 18080, which was already allocated;
3. the original contract omitted already-dirty governance paths from allowedPaths, causing unrelated path-policy failure.

Therefore MT-001 remains unproven.

## Required corrective

Re-observe:
- current dirty controller implementation;
- current `scripts/test-percona-concurrent-admin.sh`;
- original HEAD implementation at `HEAD:app/Http/Controllers/AccessAdminController.php`;
- current access feature tests;
- compose and Percona test topology.

### A/B proof is mandatory

Build the harness so one execution proves both sides:

A. **Baseline vulnerable proof**
- create a temporary isolated checkout/snapshot from repository HEAD (the clean pre-MT implementation), without altering the governed worktree;
- run the same genuine concurrent HTTP deactivation scenario against that baseline using disposable Percona;
- prove the original implementation can reach `active_admin_count=0`;
- treat failure to reproduce the vulnerable race as BLOCKED/FAIL, not PASS.

B. **Corrected worktree proof**
- run the identical genuine concurrent HTTP scenario against the current worktree implementation;
- prove `active_admin_count >= 1`.

The two request contexts must be genuinely concurrent and authenticated, share one Percona database per phase, and overlap in time.

### Harness reliability requirements

- Do not use a fixed host port. Allocate an available ephemeral host port or otherwise avoid host-port collision deterministically.
- Clean all temporary containers/networks/volumes/temp directories even on failure.
- No operator babysitting.
- No production test hooks/backdoors.
- Do not mutate the baseline snapshot.
- Output clear markers such as:
  - `BASELINE_ACTIVE_ADMIN_COUNT=0`
  - `BASELINE_REPRODUCTION=PASS`
  - `FIXED_ACTIVE_ADMIN_COUNT=<n>`
  - `FIXED_INVARIANT=PASS`

### Production fix review

Current worktree controller already attempts:
- DB transaction;
- stable `orderBy('id')`;
- `lockForUpdate()` on all administrator rows;
- authoritative target reload under lock;
- invariant check inside transaction.

Review this critically. Keep it if the A/B harness proves it and it preserves all existing semantics. Adjust only if required by actual evidence.

### Focused test

Run existing AccessAdministration feature coverage using an executable test runner that actually exists in the container. Prefer `./vendor/bin/pest tests/Feature/AccessAdministrationTest.php` after ensuring Composer dependencies are present. Do not use a nonexistent Artisan command.

### Scope

May modify only:
- app/Http/Controllers/AccessAdminController.php
- tests/Feature/AccessAdministrationTest.php
- tests/Feature/ConcurrentAccessAdministrationTest.php
- scripts/test-percona-concurrent-admin.sh

Existing governance changes are pre-existing authority and must not be edited by the executor.

### Completion

PASS requires all:
- baseline HEAD race reproduced with 0 active admins;
- fixed worktree genuine concurrent run leaves >=1 active admin;
- focused access feature tests pass;
- git diff --check passes;
- no product scope expansion.
