# TSK-018 MT-002 C003 — Inertia history revalidation closure

Bounded corrective for AUD-09 only.

Observed evidence from C001/C002:
- all backend/security/access validation is green;
- path policy is green;
- focused Pest is green: 13 tests / 174 assertions;
- TypeScript/build/diff are green;
- only browser Back after logout fails at both 320 and 390.
- C001/C002 attempted pageshow/BFCache detection using event.persisted and Navigation Timing back_forward.
- That cannot close the observed behavior because this is an Inertia SPA history restoration: browser Back restores the prior Inertia page from history.state in the same document, so pageshow is not the reliable event boundary.

Required correction:
1. Replace the pageshow-only strategy in resources/js/app.tsx with a centralized browser history revalidation strategy that handles same-document Inertia Back/Forward restoration.
2. On a real browser popstate/history traversal, force a real location reload/revalidation of the current URL.
3. The reload must reach Laravel; server-side auth/session remains authoritative. After logout, traversing back to /productos must therefore resolve to /login and private Products UI must not remain rendered/usable.
4. Avoid reload loops:
   - normal page reload/navigation must not recursively trigger another reload;
   - no timers, polling, route-specific checks, or client-side auth simulation.
5. A global popstate revalidation is acceptable. Preserve pageshow persisted protection only if useful and non-looping; simplify if possible.
6. Keep private Cache-Control/Pragma/Expires middleware intact.
7. Keep all MT-002 PRG, recovery-validation and mobile overflow fixes intact.
8. Do not broaden scope beyond AUD-09.
9. e2e/auth.spec.ts must continue to assert logout -> Back resolves to /login at 320 and 390 and Products UI is not visible.
10. Do not weaken tests merely to accept /productos.

Expected mutation:
- resources/js/app.tsx
- e2e/auth.spec.ts only if timing/observation needs a robust wait without weakening semantics.

Re-run the complete MT-002 focused gate:
- composer install
- npm ci
- tsc
- Vite build
- AccessAdministrationTest + AuthTest
- access-admin + auth Playwright mobile 320/390 workers=1
- git diff --check
PASS only with zero path-policy violations and all validation commands green.