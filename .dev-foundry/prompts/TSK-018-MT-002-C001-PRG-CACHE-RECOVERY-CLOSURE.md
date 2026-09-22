# TSK-018 MT-002 C001 — PRG/cache/recovery closure

Bounded corrective for MT-002 only.

Observed MT-002 evidence:
- TypeScript PASS.
- Vite production build PASS.
- git diff --check PASS.
- Product changes correctly implemented PRG invitation redirect, private cache middleware, recovery focus/error UI, and invitation wrapping.
- Failures are limited to:
  1. RecoveryPasswordRequest custom min message key is incorrect for Laravel and still emits `validation.min.string`.
  2. Auth feature test compares Cache-Control directives in an exact string order; actual response contains the correct directives in a normalized different order.
  3. Browser Back after logout restores a bfcache entry at /productos; server headers alone are insufficient in current Chromium behavior.
  4. Path policy omitted legitimate MT-002 files resources/css/app.css and routes/web.php.

Required corrections:

A. RecoveryPasswordRequest
- Fix the custom message mapping so short passwords yield exactly natural es-MX text, not translation keys.
- Keep confirmation mismatch natural es-MX.
- Do not expose password values.

B. Cache header test
- Preserve private/no-store/max-age=0/must-revalidate semantics.
- Update the feature assertion to validate the presence/meaning of directives without depending on header serialization order.
- Do not weaken the middleware.

C. Back/bfcache recovery
- Add the smallest centralized client-side pageshow/bfcache revalidation mechanism.
- When a page is restored from browser back-forward cache, force a real server revalidation/reload so an expired/logged-out private route is redirected by server auth.
- Do not simulate authentication client-side.
- Do not add broad route-specific hacks.
- Public pages may harmlessly revalidate; security correctness is primary.
- Playwright must prove after logout -> Back the private Products UI is not restored as an authenticated usable page and the browser returns to login after revalidation.

D. Path policy
- resources/css/app.css and routes/web.php are legitimate inherited MT-002 changes and must be allowed.
- Preserve all inherited MT-001/MT-002 dirty paths.
- Do not modify unrelated files.

Expected mutable files for corrective:
- app/Http/Requests/RecoveryPasswordRequest.php
- tests/Feature/AuthTest.php
- resources/js/app.tsx
- e2e/auth.spec.ts only if needed for robust observable revalidation timing
No further product changes unless required by one of these exact failures.

Re-run complete MT-002 focused validation:
- composer restore
- npm ci
- TypeScript
- Vite build
- AccessAdministrationTest + AuthTest
- access-admin + auth mobile Playwright at 320/390, workers=1
- git diff --check
PASS only with zero path violations.