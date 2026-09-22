# TSK-018 C002 — deterministic full closure

Operate strictly as a validation-only corrective for TSK-018 closure.

## Proven state

Previous closure attempts established:
- all MT-001..MT-005 focused validations are green;
- full SQLite Pest previously passed 105 tests / 1117 assertions;
- full Percona 8.4 previously passed 105 tests / 1117 assertions;
- npm audit passed with 0 vulnerabilities;
- TypeScript and production Vite build pass after `npm ci`;
- release build + verify passes;
- git diff --check passes;
- path policy passes with 0 violations.

Two closure-harness defects were identified:
1. release build strips Composer dev dependencies, so later closure attempts can start with `php artisan test` unavailable unless Composer dev dependencies are restored first;
2. running the entire Playwright suite with 4 workers shares one SQLite/server state across specs and produces cross-spec races. TSK-017 already established the governed deterministic closure pattern: fresh server/database per spec, workers=1, with first-use-navigation kept as its existing two-test invocation.

## Required behavior

1. Make ZERO product, test, documentation, dependency, configuration, or lifecycle mutations.
2. Do not invoke Foundry Runner.
3. Restore Composer dev dependencies FIRST.
4. Run full SQLite Pest.
5. Run full Percona 8.4.
6. Restore frontend dependencies with `npm ci`.
7. Run npm audit, TypeScript and production build.
8. Run every Playwright spec in `e2e/` deterministically:
   - give each spec a fresh Playwright server/SQLite database by cleaning port 18080 between spec invocations;
   - run every spec with `--workers=1`, EXCEPT `first-use-navigation.spec.ts`, which must keep its existing fresh empty-DB two-test invocation as already proven in TSK-017.
9. Run release build + verify LAST because it strips Composer dev dependencies.
10. Run git diff --check.
11. Preserve the whole inherited dirty state exactly.
12. PASS only if every gate passes and path policy has zero violations.

Do not repair any failure inside this task; report exact evidence only.
