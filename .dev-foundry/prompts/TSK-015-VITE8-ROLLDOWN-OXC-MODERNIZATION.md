# TSK-015 — Vite 8 / Rolldown-Oxc Toolchain Modernization

Operate as the bounded implementation executor for TSK-015 in RecetarioDigital / EmprendimientoOS.

Read and follow:
- docs/TSK-015-VITE8-ROLLDOWN-OXC-MODERNIZATION.md
- .dev-foundry/profiles/project-operating-profile-v2.yaml
- docs/ARCHITECTURE-v1.md
- docs/PRODUCT-DEFINITION-v1.md

## Goal

Migrate the frontend build toolchain cleanly from Vite 7 to the supported Vite 8 ecosystem before any new product feature work.

Expected end state:
- vite major 8;
- @vitejs/plugin-react major 6;
- laravel-vite-plugin major 3;
- @tailwindcss/vite version with Vite 8 support (>=4.2.2, staying on Tailwind 4.x);
- tailwindcss compatible 4.x;
- Rolldown/Oxc path active through Vite 8/plugin-react 6;
- TSK-014 browserslist/baseline-browser-mapping overrides removed if no longer necessary;
- npm audit completely clean at low-or-higher severity.

## Hard constraints

Do not use:
- npm --force
- --legacy-peer-deps
- overrides/resolutions to suppress a peer conflict.

Do not modify application behavior or source under app/, resources/js/, routes/, database/, config/, tests/, e2e/, public/, scripts/, compose.yaml or Dockerfile.

vite.config.ts may change only if an upstream-supported Vite 8 / Laravel Vite 3 configuration migration is actually required. Prefer no config change if current config remains valid.

Do not add React Compiler or unrelated new tooling.

## Required evidence

Before mutation record:
- node --version
- npm --version
- npm ls for Vite/plugin-react/Laravel Vite/Tailwind
- current audit result.

Implement with normal npm dependency resolution.

After mutation record exact resolved versions of:
- vite
- @vitejs/plugin-react
- laravel-vite-plugin
- @tailwindcss/vite
- tailwindcss
- rolldown

Also inspect whether @babel/core, browserslist, baseline-browser-mapping, esbuild and rollup remain and explain why if present.

Remove the TSK-014 overrides when no longer necessary.

Require:
- npm audit --audit-level=low exit 0;
- npm ci exit 0;
- no invalid peer dependencies.

Then run the complete validation matrix from the task document.

If the supported Vite 8 migration requires React/Inertia/application-code changes, stop BLOCKED and report exact incompatibility instead of broadening scope.
