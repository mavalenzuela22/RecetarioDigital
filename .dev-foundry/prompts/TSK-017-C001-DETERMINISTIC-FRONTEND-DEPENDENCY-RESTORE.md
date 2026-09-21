# TSK-017 C001 — deterministic frontend dependency restore for full closure

Operate strictly as a bounded validation-only corrective for the TSK-017 full closure matrix.

Observed prior closure result:
- SQLite full Pest: PASS (101 tests / 1007 assertions)
- Percona 8.4 full gate: PASS
- genuine concurrent-admin Percona harness: PASS
- npm audit: PASS, 0 vulnerabilities
- release build + verification: PASS
- git diff --check: PASS
- path policy: PASS, 0 violations
- frontend gates failed only because local node_modules was absent:
  - npx tsc --noEmit invoked fallback tsc package
  - npm run build: vite not found
  - Playwright: command not found

Corrective:
1. Make ZERO source, test, documentation, governance, dependency-manifest, configuration, or lifecycle mutations.
2. Do not invoke Foundry Runner.
3. Run npm ci from the existing lockfile before npm audit / TypeScript / Vite / Playwright.
4. Then rerun the complete closure matrix.
5. Preserve all current TSK-017 dirty files byte-for-byte.
6. PASS only if the whole matrix, semantic review, and path policy are green.

Do not repair anything inside this task. Any substantive failure must be reported as a blocker.