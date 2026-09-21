# TSK-017 MT-001 C003 — Percona-Observed Concurrency Barrier

Bounded corrective after C002.

## Classified C002 result

C002 achieved:
- path policy PASS;
- focused AccessAdministration Pest PASS;
- Laravel authentication and CSRF now work;
- baseline reaches "Launching two authenticated concurrent deactivation requests";
- harness then aborts on its shell-level overlap detector before allowing the experiment to complete.

The current detector:
- samples curl PIDs after an arbitrary sleep;
- treats any status-file content as non-overlap;
- is not authoritative evidence of database concurrency.

Do not change product code merely to satisfy that detector.

## Corrective objective

Replace the fragile shell-level overlap assertion with **database-observed concurrency evidence from the disposable Percona instance**, while preserving the genuine parallel HTTP requests and the external row-lock barrier.

## Required approach

1. Keep two independently authenticated curl POST processes started in parallel.
2. Keep a harness-owned transaction holding the relevant administrator row locks long enough to create a deterministic wait window.
3. During that window, query Percona repeatedly (bounded retries, short interval) using available metadata such as:
   - performance_schema.data_lock_waits / data_locks, or
   - information_schema.innodb_trx / innodb_lock_waits,
   whichever is actually available to the test DB user/server version.
4. Establish and print database evidence that **both application requests are simultaneously blocked/waiting on the harness-held administrator-row locks** before releasing the barrier.
5. Emit a clear marker:
   `DB_CONCURRENT_WAITERS=2` (or larger)
   and `CONCURRENCY_OBSERVED=PASS`.
6. Only after that observation, allow the harness lock to release and wait for both HTTP requests to finish.
7. Preserve HTTP outcome capture and authoritative active-admin query afterward.
8. Baseline HEAD must still produce:
   `BASELINE_ACTIVE_ADMIN_COUNT=0`
   `BASELINE_REPRODUCTION=PASS`
9. Fixed worktree must produce:
   `FIXED_ACTIVE_ADMIN_COUNT=<n>` where n >= 1
   `FIXED_INVARIANT=PASS`.

If the normal test DB user cannot inspect the needed metadata, use the disposable Percona root account **inside this test harness only** for observation. Do not change application credentials or production configuration.

## Diagnostics

If database observation cannot prove two simultaneous waiters, print bounded diagnostics before failing:
- curl PID liveness;
- response status-file sizes/content if any;
- relevant Percona transaction/lock-wait rows;
- server worker/process evidence if useful.

Do not fail merely because one shell status file is non-empty unless the HTTP request actually completed/errored.

## Server concurrency

Verify the Laravel/PHP server really has capacity to process at least two requests concurrently. If `artisan serve` with `PHP_CLI_SERVER_WORKERS` is not providing that guarantee in this container, start an appropriate PHP CLI server form that honors multiple workers, without modifying application production code.

## Scope

Modify only:
- scripts/test-percona-concurrent-admin.sh
- app/Http/Controllers/AccessAdminController.php only if the completed A/B proof shows the current locking fix is insufficient.

Do not change routes, middleware, models, migrations, UI, design package, or unrelated findings.

## PASS gate

PASS requires all:
- concurrency observed from Percona, not inferred only from shell timing;
- vulnerable HEAD baseline reaches 0 active admins;
- corrected worktree leaves >=1;
- focused AccessAdministration Pest passes;
- git diff --check passes.
