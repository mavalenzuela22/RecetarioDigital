# TSK-018 MT-004 C002 — fix multi-tab recipe recovery test harness

This is a TEST-ONLY corrective. Do not change product logic, backend behavior, or product copy.

## Proven state before this corrective

Primary MT-004 and C001 already prove:
- Path policy: PASS with 0 violations in C001.
- TypeScript: PASS.
- Production build: PASS.
- RecipeTest: PASS, 10 tests / 92 assertions.
- AUD-14 product behavior is visible at both 320 and 390:
  - saved-version cost is explicitly labeled;
  - after the draft changes, UI says the shown cost does not represent unsaved changes;
  - UI says draft cost will be calculated/confirmed at save.
- The only C001 failure is in Playwright test harness at e2e/recipe-costing.spec.ts line ~51.

## Root cause

The test:
1. starts on recipe detail where the link "Editar receta" exists;
2. clicks "Editar receta";
3. is now already on /recetas/{id}/editar;
4. changes the draft;
5. then incorrectly tries to locate another link named "Editar receta" on the edit form to obtain editUrl.

That link cannot exist on the edit form. Error-context snapshots at both 320 and 390 prove the page is correctly on:
- heading "Receta · versión 1";
- edit form visible;
- saved-version cost region visible;
- dirty-draft warning visible;
- no "Editar receta" link.

Therefore the 30-second timeout is entirely caused by waiting for an impossible locator.

## Required correction

Modify ONLY e2e/recipe-costing.spec.ts:

1. Once the test has navigated into the edit form and confirmed heading "Receta · versión 1", capture the current edit URL directly from the browser:
   const editUrl = page.url();
   The URL may be captured before or after the draft change, but do not query a nonexistent "Editar receta" link from the edit page.

2. Use that URL for pageA.goto(editUrl) and pageB.goto(editUrl).

3. Because the scenario now deliberately performs:
   - recipe creation and costing;
   - draft-cost assertions;
   - two additional tabs;
   - v2 save;
   - stale v1 submission;
   - conflict recovery;
   - explicit rebase;
   - v3 save;
   set an explicit per-test/file timeout of 60_000 ms for this recipe-costing scenario. This is not a semantic weakening; all assertions remain mandatory.

4. Preserve ALL current behavioral assertions:
   - new recipe draft cost not yet calculated;
   - saved v1 cost label and exact amount;
   - dirty draft warning;
   - pageA and pageB both start at v1;
   - pageA creates v2;
   - pageB stale save does not land on generic 409;
   - conflict region names v2;
   - pageB retains draft expected_yield=8;
   - explicit "Conservar mis cambios..." action;
   - conflict clears only after explicit rebase;
   - pageB saves to v3;
   - final detail shows 8-piece yield;
   - no horizontal overflow at 320 and 390.

5. Do NOT change:
   - app/Http/Controllers/RecipeController.php
   - app/Services/SaveRecipe.php
   - resources/js/Components/RecipeUI.tsx
   - resources/js/Pages/Recipes/Create.tsx
   - tests/Feature/RecipeTest.php
   - routes/web.php
   - product copy
   - domain semantics.

6. Preserve full inherited dirty state. No path-policy violations.

## Validation

Run:
- docker compose run --rm app composer install --no-interaction --prefer-dist
- npm ci
- npx tsc --noEmit
- npm run build
- docker compose run --rm app ./vendor/bin/pest tests/Feature/RecipeTest.php
- clean port 18080
- npx playwright test e2e/recipe-costing.spec.ts --project=mobile --workers=1
- git diff --check

PASS only if every command passes and path policy has zero violations.
