# TSK-018 MT-003 C005 — block service worker for deterministic Playwright routing

This is a TEST-HARNESS corrective only. Do not mutate product source.

Observed evidence:
- MT-003 product behavior is otherwise green.
- AUD-08 passes at 320/390.
- Focused Pest passes: 6 tests / 59 assertions, including append-only payment idempotency.
- TypeScript, production build, git diff --check and path policy are green in C004.
- Current payment E2E installs page.route('**/pedidos/*/cobros') and aborts POST with 'internetdisconnected'.
- Error context after that supposed abort shows Cobro registrado. and a $15 collection already present in history. Therefore the route handler did not intercept the browser request.
- The URL is confirmed to be /pedidos/{order}/cobros and the matcher is structurally correct.
- public/sw.js is registered globally by resources/js/app.tsx.
- playwright.config.ts does NOT set serviceWorkers: 'block'.
- Playwright request interception can be bypassed by requests from pages controlled by Service Workers. This exactly explains why page.route did not see the payment request.

Required correction:
1. In e2e/order-operations.spec.ts only, configure the test context with: test.use({ serviceWorkers: 'block' }); (or exact equivalent supported by current Playwright).
2. Do not modify playwright.config.ts globally unless the file-level option is impossible.
3. Do not modify resources/js product code, backend code, service worker code, routes, controllers, requests, models, migrations, package files, or application behavior.
4. Keep the existing deterministic route.abort('internetdisconnected') simulation.
5. Preserve all current behavioral assertions: uncertainty alert appears at 320/390; amount/date remain; Reintentar cobro appears; unroute then retry succeeds; server-refreshed balance/history is correct; exactly one collection is visible; delivery/cancellation remain green.
6. Preserve AUD-08 order-capture tests.
7. Path policy must allow the entire inherited current dirty set and report zero violations.

PASS only if no product source changes are made by this corrective, focused Pest passes, TypeScript/build pass, order-capture + order-operations Playwright pass, git diff --check passes, and path policy has zero violations.