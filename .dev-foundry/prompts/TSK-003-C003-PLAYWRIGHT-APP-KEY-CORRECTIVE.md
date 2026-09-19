# TSK-003 C003-R7 — Deterministic Validation Error Focus

Operate as the bounded implementation executor for TSK-003.

Observed after R6:
- Docker build: PASS
- Composer install: PASS
- npm ci: PASS
- Pest: PASS, 32 tests / 280 assertions
- production frontend build: PASS
- WebKit install: PASS
- Laravel managed E2E server: PASS
- assets and application routes are served successfully
- one Playwright viewport passed end-to-end
- the other failed only because the invalid Total pagado field was not focused after validation
- error message, preserved input, and aria-invalid were all correct
- current implementation focuses from Inertia onError via a single requestAnimationFrame, which is race-prone relative to React rendering

## Required corrective

Modify only `resources/js/Pages/Purchases/Create.tsx`.

Make validation-error focus deterministic:
- focus the first invalid field only after `form.errors` has rendered
- prefer a React `useEffect` driven by `form.errors`
- if the first invalid field is store or note and the optional section is closed, open it first, then focus on the subsequent render
- cancel any pending animation frame on effect cleanup
- remove the race-prone direct focus from `onError`
- preserve all existing copy, accessibility attributes, validation semantics, dirty-form behavior, idempotency behavior, and submission logic

Do not change backend/domain behavior.
Do not weaken the E2E assertion; the field must genuinely receive focus.

## Hard guardrail
Repository remains at exactly 30 visible TSK-003 paths.
Do not create files.
Do not exceed 30 visible changed paths.

Do not commit, push, create PR, or merge.
