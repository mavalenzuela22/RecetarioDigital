# TSK-016 C006 — Auth Test Harness Realignment

Bounded corrective after C005.

## Classified remaining failures

C005 leaves 5 failing tests, with all product/runtime code otherwise passing broader validation.

### AccessAdministrationTest (3 failures)

The helper tsk016MakeAdmin():
- loads the already-authenticated test@example.com model;
- updates the database row to is_admin=true;
- returns a fresh model;
- but does NOT replace the already-authenticated guard instance.

EnsureAdmin correctly evaluates the authenticated session user, which still has is_admin=false in memory, so 403 is expected.

This is a test harness staleness issue, not a product authorization defect.

### GoogleAuthTest (2 failures)

1. Stateful redirect test expects the Socialite fake to populate Laravel's raw session key `state`. That assertion is coupled to fake internals and is not a reliable product assertion. The product requirement is that redirect uses stateful Socialite (i.e. no stateless()) and returns provider redirect.

2. Invitation replay test performs the second callback while still authenticated from the first successful callback. The route is behind guest middleware, so Laravel correctly redirects the authenticated user to home before callback logic executes. To test replay protection itself, the test must log out / forget guards before invoking the repeated callback with the old invitation token context.

## Authorized mutation

Modify only:
- tests/Feature/AccessAdministrationTest.php
- tests/Feature/GoogleAuthTest.php
plus C006 governance artifacts.

Required adjustments:
- tsk016MakeAdmin() must re-authenticate/replace the guard user with the fresh admin instance before returning it.
- stateful redirect test must assert a provider redirect without relying on raw session `state` internals; keep the test meaningful and do not make controller stateless.
- invitation replay test must explicitly return to guest state before the second callback so replay protection code is actually exercised.

Do not modify product code, middleware, routes, models, controllers, schema, UI, Composer, or auth semantics.

## Validation

Run the full TSK-016 matrix:
- Composer validate/show/install;
- full SQLite Pest;
- full Percona 8.4 gate;
- npm ci/audit low;
- TypeScript;
- Vite 8;
- full mobile Playwright;
- release ZIP build/verify;
- git diff --check.

If any real product failure remains, report it exactly.
