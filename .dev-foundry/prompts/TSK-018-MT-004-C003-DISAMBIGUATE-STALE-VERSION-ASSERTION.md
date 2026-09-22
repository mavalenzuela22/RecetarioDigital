# TSK-018 MT-004 C003 — disambiguate stale-version assertion only

This is a TEST-ONLY corrective. Do not change product logic, backend behavior, product copy, domain semantics, or any non-test product file.

## Proven state before this corrective

MT-004 primary, C001, and C002 prove:
- TypeScript: PASS.
- Production build: PASS.
- RecipeTest: PASS, 10 tests / 92 assertions.
- Path policy: PASS with 0 violations in C002.
- git diff --check: PASS.
- C002 fixed the impossible edit-link lookup and the multi-tab scenario now advances through:
  - recipe creation/costing;
  - saved-version cost disclosure;
  - dirty draft warning;
  - opening pageA/pageB from the edit URL;
  - pageA saving version 2;
  - pageB submitting stale version 1;
  - rendering the recoverable conflict region on pageB.
- The ONLY observed C002 failure at both 320px and 390px is Playwright strict-mode ambiguity on:
  pageB.getByText('versión 2', { exact: false })

The locator resolves to exactly two visible elements:
1. heading: "Receta · versión 2"
2. rebase button: "Conservar mis cambios y usar la versión 2 como base"

This proves the product is already exposing the latest version correctly; the harness assertion is ambiguous.

## Required correction

Modify ONLY `e2e/recipe-costing.spec.ts`.

Replace the ambiguous generic text assertion for `versión 2` with one semantically precise assertion scoped to the conflict UI. Prefer one of these equivalent strong forms:
- assert the conflict region contains text identifying version 2; or
- assert the exact heading inside/associated with the conflict state if structurally available.

Do NOT remove the version-2 assertion. Do NOT weaken it into a generic visibility check.

Preserve every other current assertion and flow exactly, including:
- new-recipe draft cost not calculated;
- exact saved v1 cost;
- dirty-draft disclosure;
- pageA/pageB both begin on v1;
- pageA creates v2;
- pageB stale save does not land on generic 409;
- conflict region is visible;
- conflict explicitly identifies v2;
- pageB retains expected_yield=8;
- explicit "Conservar mis cambios y usar la versión 2 como base" action;
- conflict clears only after explicit rebase;
- pageB saves v3;
- final detail shows 8-piece yield;
- horizontal-overflow checks at 320px and 390px.

Keep `test.setTimeout(60_000)`.

Do NOT change product files, including:
- app/Http/Controllers/RecipeController.php
- app/Services/SaveRecipe.php
- resources/js/Components/RecipeUI.tsx
- resources/js/Pages/Recipes/Create.tsx
- tests/Feature/RecipeTest.php
- routes/web.php

Preserve all inherited dirty state.

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

PASS only if every command passes and path policy reports zero violations.
