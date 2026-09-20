# TSK-010 C001 — Feature Auth Harness Ordering & Recipe Test Reconciliation

Operate as a bounded corrective after the classified TSK-010 initial failure.

## Classification

### Test infrastructure defect
The initial implementation placed automatic Feature-test authentication in a global `beforeEach` in `tests/Pest.php`.

All Feature files use Laravel `RefreshDatabase`. The observed suite shows the global user/session being established before the per-test database reset, leaving many HTTP assertions effectively unauthenticated and producing redirects/401s instead of the pre-existing business behavior.

Evidence:
- Playwright full suite: 17/17 PASS through real login;
- TypeScript/Vite/Docker/Composer/diff all PASS;
- Feature failures are dominated by guest redirects / 401 after the database reset.

Required repair:
1. Remove automatic user creation/authentication from the global Pest `beforeEach`.
2. Put the default Feature-test authenticated session in `tests/TestCase.php` `setUp()`, strictly AFTER `parent::setUp()`.
3. Create the same deterministic test user with the existing User model and call `actingAs(..., 'web')`.
4. Do not bypass middleware or branch production behavior for testing.
5. `AuthTest` keeps using `authGuest()` for explicit guest scenarios; do not weaken its assertions.

### Legitimate path reconciliation
`tests/Feature/RecipeTest.php` was modified because TSK-010 intentionally changes recipe images from public to private Laravel storage and the existing recipe image test must verify the new storage disk. This is legitimate and must be included in the corrective allowlist.

No other product-code change is authorized unless the repaired Feature harness reveals a concrete remaining auth defect.

## Preserve
- all production auth routes and middleware;
- login throttling;
- session regeneration;
- POST-only logout with invalidation/token regeneration;
- operator-only provisioning;
- private authenticated recipe image delivery;
- no registration/RBAC/password reset;
- all Playwright real-login behavior.

## Validation
Run the full TSK-010 matrix:
1 docker compose build app
2 composer install
3 npm ci
4 full php artisan test
5 npx tsc --noEmit
6 npm run build
7 known E2E container cleanup
8 full Playwright mobile suite
9 git diff --check

If Pest still has failures after the harness ordering repair, preserve evidence and stop for classification. Do not make speculative product changes.
