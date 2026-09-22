# TSK-018 MT-003 C001 — payment transport uncertainty closure

Bounded corrective for AUD-12 plus inherited path-policy recognition only.

Observed MT-003 result:
- AUD-08 dirty-order draft protection passed at both 320px and 390px.
- Focused Pest passed: 6 tests / 59 assertions.
- TypeScript PASS.
- Vite production build PASS.
- git diff --check PASS.
- The only functional failure is AUD-12: after Playwright performs route.fetch() on the collection POST and then aborts the browser response, no uncertainty alert appears.
- Current Orders/Show.tsx listens to router 'exception' only.
- In this transport-abort shape Inertia does not necessarily publish 'exception'; the visit can be cancelled instead.
- Path policy also falsely blocked four inherited TSK-018 files from prior MTs. They are not MT-003 mutations.

Required correction:

A. Payment transport uncertainty
1. Preserve the existing natural message:
   "No pudimos confirmar si se registró el cobro. Tus datos siguen aquí. Reintenta con el mismo cobro."
2. Centralize a tiny helper that marks the active payment submission as transport-uncertain.
3. Invoke that helper from BOTH:
   - the existing router 'exception' handler when paymentSubmitting.current is true; and
   - the payment form visit's onCancel callback when paymentSubmitting.current is true.
4. A validation/server response handled through onError is NOT transport uncertainty and must not show the uncertainty message.
5. onSuccess must still close the dialog.
6. onFinish must clear paymentSubmitting without clearing the uncertainty message.
7. Preserve amount, payment_date and request_key unchanged after uncertain transport.
8. Retry must use the exact same request_key and payload.
9. Do not synthesize success. The observable order state after retry must come from the server.
10. Do not broaden into offline queueing, optimistic writes, polling or new backend behavior.

B. Regression
- Keep the existing Playwright transport simulation: first collection request reaches the server via route.fetch(), then the browser response is aborted.
- At 320/390 prove:
  - uncertainty alert is visible;
  - entered amount/date remain;
  - button says Reintentar cobro;
  - retry succeeds;
  - only one collection is observable because idempotent replay prevents duplication;
  - balance/payment history remain correct.
- Preserve all existing delivery/cancellation assertions.
- Preserve AUD-08 dirty-draft tests unchanged.

C. Path policy
- Recognize the complete inherited current TSK-018 dirty set.
- Do not block inherited changed paths such as prior controllers/requests/routes.
- No new mutations outside the narrow MT-003 files.

Expected corrective mutations:
- resources/js/Pages/Orders/Show.tsx
- e2e/order-operations.spec.ts only if mechanically necessary to observe the lifecycle without weakening semantics

Do not modify backend/domain logic. Do not invoke Foundry Runner from executor.

Re-run complete MT-003 focused validation:
- composer install
- npm ci
- tsc --noEmit
- production build
- focused OrderOperationsTest
- order-capture + order-operations mobile Playwright workers=1
- git diff --check
PASS only if path policy has zero violations and all validation commands are green.