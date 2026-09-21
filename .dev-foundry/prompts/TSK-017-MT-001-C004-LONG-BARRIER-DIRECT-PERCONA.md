# TSK-017 MT-001 C004 — Deterministic Long Barrier and Direct Percona Observation

Bounded corrective after C003.

## Classified C003 result

C003 reached:
- path policy PASS;
- focused AccessAdministration Pest PASS;
- authentication/CSRF PASS;
- baseline HTTP server had multiple PHP worker processes;
- two authenticated deactivation requests were launched;
- both ultimately returned HTTP 302;
- Percona observation failed to prove two simultaneous waiters before the harness barrier released.

The harness-owned row-lock barrier was only 12 seconds, while observation itself incurred expensive helper-container startup. The experiment therefore raced against its own instrumentation.

This is a harness defect, not evidence that the product race is absent.

## Corrective objective

Make the A/B concurrency proof deterministic by ensuring the harness lock remains held long enough and by observing lock waits **directly from the already-running Percona container**, without starting a new app/helper container for each sample.

## Required changes

Modify only:
- scripts/test-percona-concurrent-admin.sh
- app/Http/Controllers/AccessAdminController.php only if the completed A/B proof demonstrates the current lock strategy is insufficient.

### Barrier

- Increase the harness-owned row-lock hold window to a bounded but generous value (for example 45–60 seconds).
- The harness must still cleanly terminate and release resources on success or failure.
- Do not make the application itself sleep.

### Direct Percona observation

Use the running Percona service/container directly, e.g. via `docker compose exec -T percona ...`, rather than `docker compose run app ...` for each observer sample.

Use disposable test-only root credentials if required for `performance_schema`.

Sample at a short bounded interval and inspect:
- `performance_schema.data_lock_waits` / `data_locks`, preferred on Percona 8.4;
- optionally join `performance_schema.threads` / processlist for diagnostics.

The observer must prove at least **two distinct requesting transactions/threads** simultaneously waiting on locks held by the harness transaction.

Emit:
- `DB_CONCURRENT_WAITERS=<n>` where n >= 2
- `CONCURRENCY_OBSERVED=PASS`

### A/B acceptance

Baseline HEAD:
- identical two authenticated concurrent POSTs;
- both must have crossed the vulnerable count path before being released to update;
- after barrier release, authoritative state must show:
  `BASELINE_ACTIVE_ADMIN_COUNT=0`
  `BASELINE_REPRODUCTION=PASS`

Fixed worktree:
- same scenario;
- authoritative state must show:
  `FIXED_ACTIVE_ADMIN_COUNT=<n>` with n >= 1
  `FIXED_INVARIANT=PASS`

If baseline does not reach 0, do not claim PASS. Print diagnostics and fail.

### Preserve previous fixes

Keep:
- ephemeral published app port;
- correct `X-XSRF-TOKEN` handling;
- independently authenticated cookie jars;
- multi-worker PHP server;
- isolated HEAD snapshot;
- complete cleanup.

## Validation

- Composer install available;
- focused AccessAdministration Pest PASS;
- full deterministic A/B Percona harness PASS;
- git diff --check PASS.
