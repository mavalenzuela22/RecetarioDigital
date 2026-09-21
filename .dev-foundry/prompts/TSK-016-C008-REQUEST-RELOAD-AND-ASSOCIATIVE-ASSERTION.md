# TSK-016 C008 — Request Reload and Associative Assertion

Bounded corrective after C007.

## Classified state

C007 reached:
- SQLite Pest: 100 PASS / 1 FAIL;
- Percona Pest: 99 PASS / 2 FAIL;
- GoogleAuthTest fully PASS;
- Composer/Socialite PASS;
- npm audit 0 vulnerabilities;
- TypeScript/Vite PASS;
- mobile Playwright PASS;
- release build/verify PASS.

The two remaining failure classes are test-harness/assertion defects.

### A. Existing-session inactive-user test

In AccessAdministrationTest, the test authenticates a fresh active User model, then saves active=false using a different/stale User instance. Laravel's test guard retains the already-loaded authenticated model in memory, so EnsureActiveUser sees active=true within that same test process.

The product middleware is intended to enforce deactivation on the next request, where the session provider reloads the user from persistence.

Fix the test harness to preserve the authenticated session ID but clear/forget the cached guard instance after changing the database row, forcing the next request to resolve the user from persistence. Do not bypass EnsureActiveUser and do not alter product middleware.

### B. Append-only immutable attributes assertion on Percona

IngredientPurchaseTest snapshots `getAttributes()` from an Eloquent model and later uses `toEqualCanonicalizing()` on associative arrays.

That matcher canonicalizes values rather than preserving key/value association and becomes sensitive to database-driver attribute ordering. Percona returns the same immutable data with a different internal attribute order, creating a false failure.

Replace this with a key-preserving equality check:
- compare the same associative key/value pairs independent of insertion order;
- sorting both associative arrays by key before equality is acceptable;
- do not weaken the assertion to a small subset; it must still prove the complete original attribute set is unchanged.

## Authorized mutation

Modify only:
- tests/Feature/AccessAdministrationTest.php
- tests/Feature/IngredientPurchaseTest.php
plus C008 governance artifacts.

Do not modify any production code, middleware, controller, model, migration, route, UI, dependency, or business rule.

## Validation

Run complete TSK-016 matrix:
- Composer validate/show/install;
- full SQLite Pest;
- full Percona 8.4 gate;
- npm ci/audit low;
- TypeScript;
- Vite 8 build;
- full mobile Playwright;
- release ZIP build/verify;
- git diff --check.

If all pass, report cleanly for semantic review and promotion.
