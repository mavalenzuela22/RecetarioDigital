# TSK-017 C003 — isolated E2E spec closure

Operate strictly as a validation-only corrective for TSK-017 closure.

Observed evidence:
- C002 passed composer restore, full SQLite Pest, full Percona 8.4, concurrent-admin Percona proof, npm ci, npm audit, TypeScript, Vite build, release build+verify, git diff check, and path policy.
- C002 Playwright failures were caused by test-state leakage through one shared webServer/SQLite database across the entire suite.
- Focused MT runs already proved the affected product flows individually.
- C002 also corrected stale six-decimal E2E expectations; do not modify those files further unless validation itself proves an unexpected issue.

Required behavior:
1. Make ZERO repository mutations.
2. Do not create or modify product, test, documentation, config, dependency, or governance files.
3. Do not invoke Foundry Runner.
4. Restore Composer dev dependencies first.
5. Run backend/full database/security/build gates.
6. Run every Playwright spec file in a separate Playwright invocation so each gets a freshly migrated isolated SQLite webServer database.
7. Do not change playwright.config.ts.
8. Release build+verify must run last because it strips Composer dev dependencies.
9. PASS only if every spec invocation and every other closure gate passes with path policy zero violations.

This corrective changes validation topology only; it does not alter product behavior.