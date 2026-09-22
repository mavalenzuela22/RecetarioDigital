# TSK-018 MT-004 — Recipe recovery and costing clarity (AUD-11 + AUD-14)

Operate as the bounded implementation executor for active TSK-018 MT-004.

Authority:
- docs/TSK-018-USER-RECOVERY-MOBILE-WORKFLOW-CLARITY.md
- docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md
- branch: tsk-018-user-recovery-mobile-workflow-clarity
- inherited MT-001/002/003 changes are intentionally dirty and must be preserved.

Fix exactly AUD-11 and AUD-14.

## Observed baseline

### AUD-11 stale concurrent recipe edit
- SaveRecipe::save() correctly locks the Recipe row and rejects a stale base_version_id with ConflictHttpException.
- That domain safety is correct and MUST remain.
- RecipeController::update() currently lets ConflictHttpException escape, producing a generic HTTP 409/broken-page experience.
- Recipe edit forms post base_version_id and use Inertia useForm.
- Standard validation-style recovery can preserve the user's form draft while re-rendering current server props.

Required outcome:
1. Preserve the existing locked stale-write invariant and immutable recipe-version semantics.
2. Translate ONLY the stale recipe edit conflict at the recipe HTTP boundary into a natural es-MX recoverable form error rather than a generic 409 page.
3. The stale user's draft must remain visible and editable after the conflict.
4. The refreshed page props must expose the latest saved recipe/version as they naturally do through the edit GET.
5. When the server-provided recipe.version_id is newer than form.data.base_version_id and the stale conflict error is present, show a clear conflict recovery panel:
   - explain that a newer recipe version exists;
   - state that the user's changes are still in the form;
   - identify the current/latest version number;
   - provide a safe way to inspect the current saved version (opening backUrl/current recipe in another tab is acceptable);
   - provide an EXPLICIT action to keep the current draft and adopt the latest recipe.version_id as its new base.
6. Do NOT automatically rebase the draft without user action.
7. After explicit rebase, clear only the stale base_version error, preserve all draft fields, and allow save.
8. Re-saving after rebase must create the next immutable version; no overwrite/update-in-place.
9. Do not weaken request_key replay/idempotency semantics.

Preferred implementation boundary:
- keep SaveRecipe service conflict semantics intact;
- translate ConflictHttpException in RecipeController::update() to ValidationException on base_version_id, unless an equally narrow approach is clearly safer.

### AUD-14 recipe cost disclosure while editing
Observed:
- Recipes/Create.tsx always renders RecipeCostDisclosure with recipe?.cost.
- That cost belongs to the currently saved recipe version, but the same card remains visible after the user changes ingredients/yield.
- This can falsely look like the cost of the draft.

Required outcome:
1. Do NOT invent client-side recipe costing.
2. New recipe:
   - clearly state that draft cost is not yet calculated and will be confirmed when saved.
3. Existing recipe, pristine edit:
   - label the displayed amount explicitly as the cost of the saved version, including the version number.
4. Existing recipe, dirty draft:
   - keep saved-version cost visible only if useful, but label it unambiguously as saved-version cost;
   - show a prominent/natural note that the draft changed and the displayed saved cost does NOT represent current unsaved changes;
   - state that draft cost will be calculated/confirmed on save.
5. If saved-version current cost is incomplete:
   - distinguish “saved version cost incomplete” from “draft not yet calculated.”
6. Preserve exact integer money formatting and all existing snapshot/current-cost semantics.
7. Do not touch the broader Show-page vocabulary/polish that belongs to TSK-019.

## Required evidence

### Focused Pest
Extend tests/Feature/RecipeTest.php to prove:
- service-level stale protection remains intact;
- HTTP stale update returns a validation/recovery response rather than raw 409;
- no extra RecipeVersion is created by stale attempt;
- after a fresh/current base is used, next immutable version is created.

### Playwright at 320 and 390
Extend e2e/recipe-costing.spec.ts (or add one narrowly scoped recipe recovery spec if cleaner) to prove BOTH defects:
1. Cost clarity:
   - open saved recipe edit;
   - verify saved-version cost is labeled as saved version;
   - change expected_yield and/or ingredient quantity;
   - verify UI explicitly says displayed cost no longer represents the draft and draft cost is pending until save.
2. Real two-tab stale edit:
   - open the same recipe for edit in page A and page B from one authenticated browser context;
   - change and save A, creating v2;
   - make a distinct draft change in B and submit stale base v1;
   - B must NOT land on generic 409/error page;
   - B must retain its draft value;
   - B must display natural conflict recovery UI naming the newer version;
   - B explicitly chooses “keep my changes/use latest as base”;
   - B saves successfully to v3;
   - final detail shows v3 and the draft change from B.
3. No horizontal overflow at 320/390.

## Constraints
- no migrations/models/routes changes expected;
- no changes to TSK-019 copy-polish scope or TSK-020 visual conformance;
- no client-side floating-point costing;
- no automatic last-write-wins;
- no destructive conflict recovery;
- no polling/timers;
- do not invoke Foundry Runner from executor;
- preserve all inherited dirty changes.

Run full focused gate:
- composer install
- npm ci
- npx tsc --noEmit
- npm run build
- Pest tests/Feature/RecipeTest.php
- mobile Playwright recipe-costing at workers=1
- git diff --check
- zero path-policy violations.
