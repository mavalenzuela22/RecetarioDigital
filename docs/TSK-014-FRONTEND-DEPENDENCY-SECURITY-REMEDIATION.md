# TSK-014 — Frontend Dependency Security Remediation

## Objective

Eliminate the currently observed npm dependency vulnerabilities without introducing unnecessary breaking upgrades or changing product behavior.

Current governed validation repeatedly reports:
- 4 npm vulnerabilities total;
- 2 high;
- 2 moderate.

This task must first classify the current advisories from `npm audit --json`, then apply the smallest supported dependency change that removes every high/moderate advisory.

## Scope

Allowed production changes:
- `package.json` only when a direct dependency constraint must change;
- `package-lock.json`.

Governance artifacts for this TSK are also in scope.

No application code change is authorized.

## Required procedure

1. Run `npm audit --json` before modifying dependencies.
2. Record in execution evidence:
   - vulnerable package name;
   - severity;
   - whether direct or transitive;
   - affected dependency chain;
   - fix availability;
   - whether the package participates in shipped browser runtime or build/test tooling.
3. Prefer the narrowest remediation:
   - lockfile-compatible transitive update first;
   - direct dependency patch/minor update when required;
   - no `npm audit fix --force`;
   - no major upgrade unless the existing supported semver range already resolves to that major.
4. Do not add a new dependency merely to suppress an advisory.
5. Do not use overrides/resolutions as a first choice. If an override is truly necessary, execution must stop and report BLOCKED rather than silently adding one.
6. After remediation, `npm audit --audit-level=moderate` must exit 0.

## Invariants

- no PHP/Laravel application behavior changes;
- no React/Inertia UI changes;
- no product/domain/financial behavior changes;
- no migration/config/runtime changes;
- no new production Node runtime;
- existing lockfile reproducibility preserved;
- TypeScript build remains green;
- full mobile Playwright remains green;
- SQLite Pest remains green;
- Percona 8.4 gate remains green;
- production release ZIP still builds and verifies.

## Validation matrix

1. `npm ci`
2. `npm audit --audit-level=moderate`
3. `npx tsc --noEmit`
4. `npm run build`
5. `docker compose build app`
6. `docker compose run --rm app composer install --no-interaction --prefer-dist`
7. SQLite Pest
8. Percona compatibility gate
9. clean known E2E container
10. full mobile Playwright
11. rebuild and verify TSK-012 release ZIP
12. `git diff --check`

## Completion

PASS requires no remaining moderate/high/critical npm advisories and the complete application/release regression matrix to remain green.

If eliminating an advisory requires an explicit breaking major upgrade or application-code change, stop BLOCKED with exact advisory and dependency-chain evidence instead of broadening scope.
