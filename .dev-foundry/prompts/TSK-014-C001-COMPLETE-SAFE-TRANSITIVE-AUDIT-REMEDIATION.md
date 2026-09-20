# TSK-014 C001 — Complete Safe Transitive Audit Remediation

Operate as a bounded corrective for TSK-014.

## Failure classification

The initial TSK-014 execution improved the dependency graph but post-execution validation still failed only at:

`npm audit --audit-level=moderate`

Current remaining blockers:
- `baseline-browser-mapping >=2.0.0 <2.11.0` — moderate — transitive build-tooling dependency under @vitejs/plugin-react -> Babel -> browserslist;
- `browserslist <=4.28.6` — high — transitive build-tooling dependency under @vitejs/plugin-react -> Babel.
- npm explicitly reports a non-force `npm audit fix` is available.

A low-severity esbuild advisory may remain; TSK-014 acceptance requires zero moderate/high/critical.

Everything else in the prior matrix passed, including TypeScript, Vite build, SQLite Pest, Percona 8.4, full mobile Playwright, release ZIP verification, and diff check.

## Corrective boundary

Product/dependency source code must not change.

Allowed package change:
- `package-lock.json` only.

Procedure:
1. record the current `npm audit --json` state;
2. run `npm audit fix --package-lock-only` without `--force`;
3. do not edit `package.json`;
4. run `npm ci`;
5. require `npm audit --audit-level=moderate` exit 0;
6. rerun the complete TSK-014 validation matrix.

If npm cannot clear moderate/high/critical without changing package.json, adding overrides, or forcing a breaking upgrade, stop BLOCKED with exact evidence.

Do not modify application code, Composer dependencies, PHP config, migrations, tests, scripts, or runtime behavior.
