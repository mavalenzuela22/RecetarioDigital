# TSK-004 C001 — Validation Test Corrective

Operate as the bounded implementation executor for the already implemented TSK-004 Recipes & Yield Costing boundary.

## Classification
The prior TSK-004 execution implementation completed with path policy PASS, but validation failed. Re-observation classifies the failures as test/test-environment defects, not product defects:

1. The overflow test uses unit cost 999999999990000000 and 1.001 g. Its final usage cost is approximately 1.001e18 micros, below signed 64-bit PHP_INT_MAX. The test expectation is mathematically wrong. Replace the case with a valid input that actually drives the exact final usage cost above PHP_INT_MAX while remaining inside TSK-003 accepted purchase and recipe input bounds. Do not weaken product overflow protection.
2. `version_number` is correctly represented as an integer. Fix strict test assertions that incorrectly expect strings.
3. `UploadedFile::fake()->image()` requires GD, which the application/runtime contract does not require. Replace it with a valid bounded fake upload that exercises the application's JPEG/PNG/WebP validation/storage behavior without requiring GD. Do not add GD or modify Docker.
4. A new recipe intentionally starts with zero ingredient lines. The E2E incorrectly assumes line 0 already exists. Make the E2E explicitly use the production `Agregar ingrediente` action before addressing the first line. Prefer accessible locators where stable; do not change production markup solely to satisfy this test.

## Allowed mutations
Only:
- `tests/Feature/RecipeTest.php`
- `e2e/recipe-costing.spec.ts`

Do not modify product implementation, routes, models, services, migrations, React production code, CSS, design docs, manifests, Docker, or Playwright configuration.

## Validation
Run:
- full Pest suite;
- TypeScript no-emit;
- production Vite build;
- real recipe Playwright flow;
- git diff --check.

Preserve truthful evidence. If a corrected test exposes a genuine product defect, stop and report it instead of expanding this corrective.
