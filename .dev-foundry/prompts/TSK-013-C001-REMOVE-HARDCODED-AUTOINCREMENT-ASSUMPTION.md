# TSK-013 C001 — Remove Hardcoded Auto-Increment Assumption

Operate as a bounded corrective for TSK-013.

## Failure classification

The Percona 8.4 service:
- pulled and started successfully;
- reached healthy state through Docker Compose --wait;
- accepted connections;
- completed migrate:fresh;
- created all required runtime/domain tables;
- executed the full Pest suite.

The suite then reported 12 failures in tests/Feature/IngredientPurchaseTest.php.

Observed root cause:
- the test assumes newly-created ingredient ID is always 1;
- SQLite :memory: happens to satisfy that assumption under the existing test lifecycle;
- Percona/MySQL auto-increment values are not guaranteed to reset between transactional test cases;
- actual persisted IDs increased (for example 7, 8, 9, ...), while assertions still expected routes containing /ingredientes/1;
- the final Inertia failure is the same hardcoded-ID issue when requesting ingredient 1 after a different ID was created.

This is a cross-database test harness defect, not an application/domain defect.

## Required corrective

Modify only tests/Feature/IngredientPurchaseTest.php as production/test code.

1. In the parameterized "persists exact quantities..." test:
   - perform the POST;
   - load IngredientPurchase::sole();
   - assert the redirect against route('ingredients.show', $purchase->ingredient_id) rather than hardcoded 1;
   - preserve all exact quantity/cost/canonical-unit assertions.

2. In "renders the overview, selected form, durable history and confirmed success":
   - after posting the purchase, obtain the persisted IngredientPurchase/Ingredient;
   - use its actual ingredient_id for the show route;
   - use the same actual ID for the purchases.create selected-ingredient query;
   - preserve all Inertia assertions and the explicit 999 not-found assertion.

Do not reset AUTO_INCREMENT manually.
Do not change migrations, models, services, controllers, routes, database code, or production behavior.

## Validation

Rerun the complete TSK-013 matrix:
- SQLite Pest;
- Percona 8.4 compatibility gate;
- TypeScript/Vite;
- full mobile Playwright;
- release ZIP build + verify;
- shell syntax;
- git diff check.

Do not promote. Preserve evidence and stop on any remaining failure.
