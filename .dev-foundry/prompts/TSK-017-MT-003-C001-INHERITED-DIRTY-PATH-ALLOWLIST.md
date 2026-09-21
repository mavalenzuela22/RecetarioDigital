# TSK-017 MT-003 C001 — Inherited Dirty Path Allowlist

Bounded governance corrective after MT-003 functional validation.

## Classified result

The prior MT-003 execution functionally passed every validation command:
- TypeScript PASS;
- Vite production build PASS;
- order-capture Playwright PASS including the stale inactive selected-product recovery scenario;
- OrderCapture Pest PASS;
- git diff check PASS.

The only failure was path policy because `app/Http/Controllers/AccessAdminController.php` remains dirty from already-validated MT-001 and the MT-003 contract blocked controllers globally.

This corrective exists only to validate the current already-fixed MT-003 worktree with inherited validated dirty paths explicitly allowlisted.

## Requirements

- Do not modify MT-001 or MT-002 product changes.
- Do not broaden MT-003 implementation.
- Re-run the same focused validation matrix.
- Preserve the current MT-003 source and regression unless a validation command reveals a genuine defect.
- Path policy must PASS with all pre-existing validated dirty paths allowlisted.

## Validation

1. npx tsc --noEmit
2. npm run build
3. remove only stale Docker containers publishing host port 18080
4. npx playwright test e2e/order-capture.spec.ts --project=mobile
5. focused OrderCapture Pest
6. git diff --check

PASS requires all commands plus path policy PASS.
