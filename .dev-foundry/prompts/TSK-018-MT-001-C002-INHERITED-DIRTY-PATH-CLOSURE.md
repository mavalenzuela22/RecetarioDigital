# TSK-018 MT-001 C002 — inherited dirty path closure

Validation-only corrective.

Observed facts from C001:
- all validation commands passed;
- Playwright today-production: 5/5 passed;
- focused Pest TodayProductionTest: 7 tests / 83 assertions passed;
- TypeScript PASS;
- production build PASS;
- git diff --check PASS;
- C001 failed only because path policy blocked two legitimately inherited MT-001 product files:
  - app/Http/Controllers/ProductionController.php
  - resources/js/Pages/Production/Index.tsx
- No product correction is required.

Required behavior:
1. Make ZERO repository mutations.
2. Do not invoke Foundry Runner.
3. Re-run the MT-001 focused validation gate.
4. Allow the complete currently inherited TSK-018 dirty set, including the two product files above.
5. PASS only if path policy has zero violations and every validation command passes.

Do not modify product, tests, configuration, dependencies, or governance files.