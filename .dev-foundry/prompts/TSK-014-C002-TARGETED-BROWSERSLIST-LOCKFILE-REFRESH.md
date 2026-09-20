# TSK-014 C002 — Targeted Browserslist Lockfile Refresh

Operate as the second bounded corrective for TSK-014.

## Classification

C001 proved that generic `npm audit fix --package-lock-only` does not advance the remaining vulnerable nested dependencies.

Observed current graph facts:
- direct `@vitejs/plugin-react` is already resolved to 5.2.0 inside the existing declared `^5.0.0` range;
- remaining moderate/high findings are nested below @vitejs/plugin-react -> @babel/core -> @babel/helper-compilation-targets -> browserslist;
- `baseline-browser-mapping <2.11.0` remains moderate;
- `browserslist <=4.28.6` remains high;
- npm reports ordinary non-force fixes are available;
- all application/regression gates remain green.

## Corrective boundary

Allow only:
- `package-lock.json`
- this corrective's governance artifacts.

`package.json` remains blocked.

Required procedure:
1. capture pre-change `npm audit --json`;
2. perform a targeted lockfile-only update of exactly:
   - `browserslist`
   - `baseline-browser-mapping`
   using npm's normal update semantics and no `--force`;
3. do not edit package.json;
4. run `npm ci`;
5. run `npm audit --audit-level=moderate` and require exit 0;
6. record final resolved versions of browserslist and baseline-browser-mapping;
7. rerun the complete TSK-014 validation matrix.

Do not add overrides/resolutions.
Do not modify any application, test, PHP, Composer, config, migration, script, Vite config, or TypeScript source.

If targeted npm update cannot clear both moderate/high findings without changing package.json or introducing an override, stop BLOCKED with exact evidence.
