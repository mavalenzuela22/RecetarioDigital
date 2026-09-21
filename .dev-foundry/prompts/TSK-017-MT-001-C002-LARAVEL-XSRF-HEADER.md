# TSK-017 MT-001 C002 — Laravel XSRF Header Corrective

Bounded corrective after C001.

## Classified failure

C001 reached:
- path policy: PASS;
- Composer install: PASS;
- focused AccessAdministration Pest: PASS;
- git diff check: PASS;
- Percona A/B harness progressed through baseline DB setup and HTTP server startup;
- login failed with HTTP 419 before the concurrent requests.

Root cause to correct:
The harness extracts the Laravel `XSRF-TOKEN` cookie but sends it in `X-CSRF-TOKEN`. For cookie-based CSRF, send the URL-decoded cookie value using `X-XSRF-TOKEN` instead.

## Scope

Modify only:
- scripts/test-percona-concurrent-admin.sh
- app/Http/Controllers/AccessAdminController.php only if the completed A/B proof demonstrates the current lock strategy is insufficient.

Do not modify product semantics, routes, middleware, models, migrations, UI, design artifacts, or other MTs.

## Required harness behavior

1. Keep the existing isolated HEAD baseline vs current worktree A/B topology.
2. For login and deactivation requests:
   - maintain the cookie jar across requests;
   - use `X-XSRF-TOKEN` for the decoded `XSRF-TOKEN` cookie value;
   - after successful login/session regeneration, perform an authenticated GET that also updates the cookie jar, then re-read the current XSRF cookie before later POSTs.
3. Preserve genuine concurrent authenticated HTTP requests and the lock barrier.
4. Preserve ephemeral host-port allocation and complete cleanup.
5. Baseline must reproduce exactly 0 active admins and emit:
   `BASELINE_ACTIVE_ADMIN_COUNT=0`
   `BASELINE_REPRODUCTION=PASS`
6. Fixed worktree must leave at least 1 active admin and emit:
   `FIXED_ACTIVE_ADMIN_COUNT=<n>`
   `FIXED_INVARIANT=PASS`

Do not claim PASS if either side is missing.

## Validation

- Composer dependencies available;
- focused AccessAdministration Pest PASS;
- full A/B Percona harness PASS;
- git diff --check PASS.
