# TSK-011 C002 — Stable Service Worker Registration Assertion

Operate as a bounded corrective after TSK-011 C001.

## Classification

After C001:
- full Pest suite PASS, including PwaTest;
- Docker/Composer/npm/TypeScript/Vite/diff PASS;
- path policy PASS;
- 17/18 Playwright tests PASS;
- only e2e/pwa.spec.ts fails.

The PWA E2E performs two separate service-worker registration reads:
1. waitForFunction polling getRegistration('/') until active scriptURL ends with /sw.js;
2. a second page.evaluate getRegistration('/') that then returned activeScript as undefined.

The server logs show /sw.js is served successfully and the first readiness condition passes. This is a test synchronization defect, not evidence of a service-worker runtime defect.

## Required repair

Modify only e2e/pwa.spec.ts:
- replace the split wait/read sequence with one stable page.evaluate using `await navigator.serviceWorker.ready`;
- return the active worker scriptURL, registration scope, and active state from that ready registration;
- assert scriptURL ends in /sw.js, scope pathname is /, state is activated;
- keep every manifest/icon/cache-boundary/business-data assertion unchanged.

Do not modify:
- public/sw.js
- manifest
- icons
- app.tsx
- Blade metadata
- backend tests
- auth/domain/runtime code.

## Validation

Run the complete TSK-011 matrix:
1 docker compose build app
2 composer install
3 npm ci
4 full php artisan test
5 npx tsc --noEmit
6 npm run build
7 known E2E container cleanup
8 full Playwright mobile
9 git diff --check

Stop and preserve evidence on any remaining failure.
