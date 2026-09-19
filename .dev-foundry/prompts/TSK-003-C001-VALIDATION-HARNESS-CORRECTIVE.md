# TSK-003 C001 — Validation Harness Corrective

Operate as the bounded implementation executor for TSK-003.

This corrective addresses only the observed validation failures from the first execution.

## Observed facts
The first execution:
- passed Docker build
- passed Composer install
- passed npm ci
- passed production frontend build
- passed git diff --check
- stayed within path policy with 24 changed files

Failures:
1. Laravel feature tests returned HTTP 419 for POST requests because CSRF middleware was active in the test harness.
2. One append-only assertion compared Eloquent attribute arrays with strict order-sensitive equality even though the persisted values were unchanged.
3. Playwright could not launch because the local WebKit browser binary was not installed.

## Required corrective

### Feature test CSRF
Modify only the test harness, not production middleware.

In `tests/Feature/IngredientPurchaseTest.php`, disable Laravel's CSRF middleware for these feature tests using the testing API / middleware class appropriate to Laravel 12.

Do not disable CSRF in application code, routes, bootstrap, or production configuration.

### Append-only assertion
Keep the append-only behavior test.

Make the assertion deterministic and value-based rather than dependent on the internal key order returned by Eloquent.

Do not weaken the assertion to check only one arbitrary field; retain strong evidence that the original persisted purchase was not mutated.

### Playwright
Do not change the E2E behavior merely because the browser executable was absent.

The corrective validation contract will install WebKit before running Playwright.

Only modify `e2e/ingredient-purchase.spec.ts` if a real functional failure remains after WebKit is available.

## Scope
Prefer changing only:
- `tests/Feature/IngredientPurchaseTest.php`

Do not modify production application code unless a new reproduced product defect makes that necessary.

Do not commit/push/merge.

Before completion:
- run the full contract validation commands available to you where practical
- keep total visible paths <= 30
- report exact changed paths and results
