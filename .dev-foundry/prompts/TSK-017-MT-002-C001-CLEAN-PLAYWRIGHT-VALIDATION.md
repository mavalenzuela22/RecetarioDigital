# TSK-017 MT-002 C001 — Clean Playwright Validation

Bounded corrective after TSK-017 MT-002 initial execution.

## Classified result

Initial MT-002 execution produced the intended minimal product change:
- removed duplicated `start = useForm({ from, to })`;
- "Iniciar preparación" now posts the current `dates` form;
- added browser regression for range A -> B.

Validation results:
- `npx tsc --noEmit`: PASS;
- `tests/Feature/TodayProductionTest.php`: PASS;
- `git diff --check`: PASS;
- Playwright did not run because port 18080 was already occupied;
- path policy failed only because the already-validated dirty MT-001 controller was included in repository diff while the MT-002 contract blocked controllers.

These are validation-boundary defects, not evidence against the MT-002 product fix.

## Required corrective

1. Preserve all existing MT-001 validated dirty changes untouched.
2. Re-observe the current MT-002 implementation and browser regression.
3. Do not broaden product scope.
4. Run focused browser validation after removing only stale Docker container(s) that currently publish host port 18080.
5. If the A -> B regression then reveals a real product/test defect, correct only within:
   - resources/js/Pages/Production/Index.tsx
   - e2e/today-production.spec.ts
   - optionally e2e/production-range.spec.ts
6. Keep the single-source-of-truth approach; do not reintroduce duplicated range state.

## Port cleanup

Before Playwright, execute a bounded cleanup that:
- finds Docker containers publishing host port 18080;
- removes only those containers;
- does not prune unrelated containers/images/volumes/networks.

Then run the existing Playwright spec normally so its configured webServer owns 18080.

## PASS gate

- path policy PASS, including preserved MT-001 dirty files;
- `npx tsc --noEmit` PASS;
- focused `e2e/today-production.spec.ts` PASS including A -> B regression;
- TodayProduction Pest PASS;
- git diff check PASS.
