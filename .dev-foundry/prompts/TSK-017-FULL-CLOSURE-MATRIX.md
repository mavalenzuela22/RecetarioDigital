# TSK-017 — Full closure matrix

Operate strictly as a bounded closure/validation executor for TSK-017. All six implementation MTs are already focused-green.

Repository location:
- branch: tsk-017-pilot-blocker-correctness-economic-trust
- expected base HEAD: bbabd266be69915391c54ddca5bc906d773bba57

Discipline:
1. Make ZERO product, test, documentation, governance, dependency, configuration, or lifecycle mutations. The only expected filesystem outputs are ordinary ignored build/test/release artifacts created by validation commands.
2. Do not invoke Foundry Runner.
3. Before validation, inspect the complete git diff for semantic consistency across MT-001..MT-006. Fail rather than edit if you find a correctness regression, scope leak, unsafe arithmetic change, auth/authorization weakening, unexplained monetary semantics, or a change outside the six authorized remediation boundaries.
4. Preserve exact internal minor/micro arithmetic and the current TSK-017 implementations.
5. Do not start TSK-018/019/020.
6. Treat any failed validation as a real closure failure and report its exact command/output. Do not attempt repairs inside this task.

Closure evidence required:
- full SQLite Pest suite;
- full Percona 8.4 gate;
- genuine concurrent-admin Percona harness;
- npm audit at low severity;
- TypeScript noEmit;
- production Vite build;
- full mobile Playwright;
- release build and verification;
- git diff --check;
- path policy zero violations;
- semantic review of the complete TSK-017 diff.

PASS only when the whole matrix is green and no unexpected repository mutation is introduced.