# TSK-018 — Full closure matrix

Operate strictly as a bounded closure/validation executor for TSK-018. MT-001 through MT-005 are already focused-green.

Repository location:
- branch: tsk-018-user-recovery-mobile-workflow-clarity
- expected base HEAD: 369baf5841cdc3a9dcab8a16a14fc60ce380acec

Discipline:
1. Make ZERO product, test, documentation, governance, dependency, configuration, or lifecycle mutations. The only expected filesystem outputs are ordinary ignored build/test/release artifacts created by validation commands.
2. Do not invoke Foundry Runner.
3. Before validation, inspect the complete git diff for semantic consistency across MT-001..MT-005. Fail rather than edit if you find a correctness regression, scope leak, unsafe arithmetic change, auth/authorization weakening, stale/private-cache regression, idempotency weakening, recipe-version immutability regression, misleading Today economics, or changes outside AUD-06..AUD-16.
4. Preserve exact internal minor/micro arithmetic and all current TSK-018 implementations.
5. Do not start TSK-019 or TSK-020 and do not perform Google OAuth credential acceptance.
6. Treat any failed validation as a real closure failure and report its exact command/output. Do not attempt repairs inside this task.

Closure evidence required:
- full SQLite Pest suite;
- full Percona 8.4 gate;
- npm audit at low severity;
- TypeScript noEmit;
- production Vite build;
- full mobile Playwright;
- release build and verification;
- git diff --check;
- path policy zero violations;
- semantic review of the complete TSK-018 diff.

PASS only when the whole matrix is green and no unexpected repository mutation is introduced.
