# TSK-016 C007 — Access Admin Test Expectations

Bounded corrective after C006.

## Classified remaining failures

C006 leaves exactly 2 failing tests; 99 tests pass in both SQLite and Percona. GoogleAuthTest is fully green. Composer, npm audit, TypeScript, Vite, Playwright mobile, and release verification all pass.

Both remaining failures are incorrect test expectations in AccessAdministrationTest.php.

### 1. Invitation expiry direction

The test currently asserts:
`$invitation->expires_at->diffInDays(now()) === 7`

Carbon's signed difference from a future expiry to now is negative, producing about -7 days. The product creates expiry with now()->addDays(7), which is correct.

Adjust the assertion to compare in the forward direction (now to expires_at), or otherwise assert the seven-day future boundary robustly without depending on fractional microseconds.

### 2. Last-admin rule test conflicts with self-deactivation rule

The test first verifies that an administrator cannot deactivate their own account. Later it tries to exercise the "last active admin cannot be deactivated" rule by deactivating that same current admin.

The controller correctly applies the stronger self-deactivation guard first, so the second expectation can never reach the last-admin branch.

Realign the test scenario so:
- self-deactivation remains tested on the current admin;
- the last-active-admin rule is tested against a different admin while the acting/current admin context is arranged so the target is not the current user and is the sole remaining active admin for that assertion;
- do not weaken or reorder production guards.

## Authorized mutation

Modify only:
- tests/Feature/AccessAdministrationTest.php
plus C007 governance artifacts.

Do not modify production code, middleware, controllers, models, schema, routes, UI, Composer, or Google auth semantics.

## Validation

Run the complete TSK-016 matrix:
- Composer validate/show/install;
- full SQLite Pest;
- full Percona 8.4 gate;
- npm ci/audit low;
- TypeScript;
- Vite 8;
- full mobile Playwright;
- release ZIP build/verify;
- git diff --check.

If all pass, report cleanly so the parent TSK can proceed to semantic review and promotion.
