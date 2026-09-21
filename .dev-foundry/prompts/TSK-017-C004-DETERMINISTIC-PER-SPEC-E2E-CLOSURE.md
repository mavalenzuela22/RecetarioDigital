# TSK-017 C004 — deterministic per-spec E2E closure

Operate strictly as a validation-only corrective for TSK-017 closure.

Evidence carried from prior closure attempts:
- Full SQLite Pest is green.
- Full Percona 8.4 is green.
- Concurrent-admin Percona proof is green.
- npm ci/audit, TypeScript, Vite build, release build+verify, git diff check, and path policy are green.
- C003 proved access-admin, auth, first-use-navigation, and home specs pass when each spec gets a fresh Playwright server/database.
- C003 stopped at ingredient-purchase because its 320/390 cases still ran concurrently inside the same spec and one transient "Guardando compra…" assertion flaked.
- Product behavior must not be changed.

Required behavior:
1. Make ZERO repository mutations.
2. Do not invoke Foundry Runner.
3. Restore Composer dev dependencies first.
4. Re-run the complete closure matrix.
5. For E2E, give each spec a fresh Playwright server/SQLite database.
6. Run each spec with --workers=1 to avoid intra-spec races, EXCEPT first-use-navigation.spec.ts which must run as its existing 2-test invocation on a fresh empty DB because both viewport cases assert first-use empty state and that invocation already passed in C003.
7. Do not change playwright.config.ts, tests, or product code.
8. Release build+verify runs last because it strips Composer dev dependencies.
9. PASS only if every gate passes and path policy has zero violations.