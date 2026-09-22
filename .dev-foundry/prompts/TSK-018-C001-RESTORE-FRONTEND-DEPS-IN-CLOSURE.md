# TSK-018 C001 — restore frontend dependencies inside closure matrix

This is a CLOSURE-HARNESS corrective only. Do not change product code, tests, copy, domain semantics, dependencies, or configuration.

## Proven state from TSK-018 full closure

The first full closure execution proved:
- full SQLite Pest PASS: 105 tests / 1117 assertions;
- full Percona 8.4 gate PASS: 105 tests / 1117 assertions;
- npm audit PASS: 0 vulnerabilities;
- release build + verification PASS;
- git diff --check PASS;
- path policy PASS with 0 violations.

The only failed commands were:
- npx tsc --noEmit
- npm run build
- npx playwright test --project=mobile

Their failures were all "command not found" / missing local frontend toolchain after the Percona script had altered installed dependencies.

The release builder itself later ran a clean npm install and built successfully, proving the repository/package lock are valid.

## Root cause

The closure matrix did not restore frontend dependencies after the Percona gate.
Earlier MT validations always performed npm ci before TypeScript/build/Playwright.
The full closure matrix omitted that restoration step.

## Required action

Make ZERO product/test/code mutations.
Preserve the entire dirty working tree exactly.

Run the full closure matrix again, but explicitly restore frontend dependencies with:
- npm ci

AFTER the Percona 8.4 gate and BEFORE:
- npm audit
- npx tsc --noEmit
- npm run build
- Playwright

Then run:
- full SQLite Pest
- full Percona 8.4
- npm ci
- npm audit --audit-level=low
- npx tsc --noEmit
- npm run build
- clean port 18080
- npx playwright test --project=mobile
- release build + verify
- git diff --check

Do not repair anything else inside this task. PASS only if the entire matrix is green and path policy has zero violations.
