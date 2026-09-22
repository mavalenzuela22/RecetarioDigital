# TSK-018 MT-002 C002 — back-forward revalidation closure

Bounded corrective for AUD-09 only.

Observed C001 result:
- path policy PASS, 27 changed files, 0 violations;
- composer/npm restore PASS;
- TypeScript PASS;
- Vite build PASS;
- AccessAdministrationTest + AuthTest PASS: 13 tests / 174 assertions;
- access-admin Playwright scenarios PASS/skipped as expected;
- only e2e/auth.spec.ts fails at 320/390 after logout -> page.goBack(): browser remains on /productos.
- resources/js/app.tsx currently reloads only when the `pageshow` event has `event.persisted === true`.
- In the observed Chromium run, history restoration occurs without `event.persisted` being true, so the listener does not revalidate.

Required correction:
1. Modify the centralized history restoration detection in resources/js/app.tsx so it revalidates when either:
   - `event.persisted` is true; OR
   - the current navigation entry reports type `back_forward` via the Navigation Timing API.
2. On such restoration, perform a real browser reload of the current URL. This must reach the server, where auth/session middleware remains authoritative and redirects logged-out private routes to /login.
3. Avoid route-specific checks, client-side auth simulation, timers, polling, or per-page listeners.
4. Avoid reload loops: a normal reload has navigation type `reload`, so it must not trigger another history revalidation.
5. Keep all C001 product/security changes intact.
6. Do not weaken private/no-store headers.
7. Do not change auth/session semantics.
8. Modify e2e/auth.spec.ts only if needed to observe the actual redirect robustly; do not lower the assertion that private Products UI must not remain visible/usable after Back.

Re-run full MT-002 focused validation:
- composer install
- npm ci
- tsc
- Vite build
- AccessAdministrationTest + AuthTest
- access-admin + auth mobile Playwright at 320/390 with one worker
- git diff --check

PASS only with zero path-policy violations and all validation commands green.