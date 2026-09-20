# TSK-015 — Vite 8 / Rolldown-Oxc Toolchain Modernization

## Objective

Modernize the EmprendimientoOS frontend build toolchain from Vite 7 to the supported Vite 8 stack before adding new authentication UX.

Target stack:
- Vite 8;
- @vitejs/plugin-react 6;
- laravel-vite-plugin 3;
- @tailwindcss/vite version with explicit Vite 8 support;
- Tailwind CSS matching the supported 4.x line;
- Rolldown/Oxc-backed React refresh/build path.

This task must remove the temporary TSK-014 Browserslist security overrides when they are no longer required by the dependency graph.

## Upstream compatibility facts

- Vite 8 uses Rolldown and Oxc-based tooling instead of the Vite 7 esbuild/Rollup build path.
- Vite 8 keeps the Vite 7 Node requirement: Node 20.19+ or 22.12+.
- @vitejs/plugin-react 6 requires Vite 8 and removes Babel as its normal transform dependency.
- laravel-vite-plugin 3 adds Vite 8 support.
- @tailwindcss/vite 4.2.2+ adds Vite 8 support.

## Current repository observations

- Current stack is Vite ^7.0.0, @vitejs/plugin-react ^5.0.0, laravel-vite-plugin ^2.0.0.
- vite.config.ts is minimal and contains only Laravel, React and Tailwind plugins.
- No custom Babel options are configured.
- No custom Rollup hooks/options are configured.
- resources/js/app.tsx uses import.meta.glob only for Inertia page resolution, not static asset import.
- Production does not require a Node/Vite runtime.

## Allowed production changes

- package.json
- package-lock.json
- vite.config.ts only if a supported Vite 8 / plugin API migration requires it.

Governance artifacts for TSK-015 are also allowed.

## Prohibited changes

Do not modify:
- Laravel/PHP application code;
- React page/component/application code;
- Inertia behavior;
- routes/controllers/models/migrations;
- authentication;
- PWA runtime behavior;
- Playwright/Pest tests except as evidence;
- production runtime architecture;
- release packaging scripts;
- Docker/Compose unless the migration cannot be validated without a separately classified task.

Do not use:
- --force
- --legacy-peer-deps
- dependency overrides/resolutions to hide incompatibilities.

## Required implementation procedure

1. Record baseline Node/npm versions and current dependency tree.
2. Confirm the local Node runtime satisfies Vite 8 requirements.
3. Upgrade the direct toolchain to supported Vite 8-compatible versions:
   - vite ^8;
   - @vitejs/plugin-react ^6;
   - laravel-vite-plugin ^3;
   - @tailwindcss/vite at least 4.2.2;
   - tailwindcss on the compatible 4.x line.
4. Regenerate package-lock.json using normal npm resolution.
5. Remove TSK-014 overrides for browserslist and baseline-browser-mapping if the Vite 8 graph no longer requires them.
6. Verify the final installed graph contains Vite 8, plugin-react 6, laravel-vite-plugin 3 and Rolldown.
7. Verify plugin-react no longer pulls Babel solely for React Refresh.
8. Require npm audit to report zero vulnerabilities at low-or-higher severity.
9. Preserve existing build output semantics, PWA registration and Laravel asset resolution.

## Validation matrix

- node/npm compatibility check;
- npm ci;
- npm dependency-tree inspection;
- npm audit --audit-level=low;
- TypeScript noEmit;
- Vite production build;
- Docker app build;
- Composer dev install;
- full SQLite Pest;
- Percona 8.4 compatibility gate;
- clean E2E container;
- full mobile Playwright;
- release ZIP build + verify;
- git diff --check.

## Completion

PASS requires:
- supported Vite 8 toolchain installed without force/legacy peer resolution;
- no temporary Browserslist override retained unless independently justified;
- zero npm audit vulnerabilities at low or higher severity;
- no product/application behavior change;
- entire validation matrix green.

If Vite 8 requires application-code changes or a broader infrastructure/runtime redesign, stop BLOCKED with exact evidence rather than expanding scope.
