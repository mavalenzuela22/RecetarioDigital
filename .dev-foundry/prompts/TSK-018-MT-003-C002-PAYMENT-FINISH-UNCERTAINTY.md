# TSK-018 MT-003 C002 — payment finish uncertainty closure

Bounded corrective for AUD-12 only.

Observed evidence:
- AUD-08 passes at 320/390.
- Path policy PASS in C001.
- Focused Pest PASS: 6 tests / 59 assertions.
- TypeScript/build/diff PASS.
- Only AUD-12 fails: the Playwright route reaches the server with route.fetch(), then aborts the browser response. In this shape Inertia does not emit exception or onCancel, so no uncertainty UI appears.
- Current Orders/Show.tsx already has exception and onCancel handling.

Required correction:
1. Track the lifecycle outcome of each payment submission explicitly with a ref/state such as:
   - pending when submission starts;
   - success in onSuccess;
   - validation in onError;
   - cancelled/uncertain in onCancel or exception.
2. In onFinish:
   - if the attempt outcome is STILL pending, classify it as transport-uncertain and show the existing message;
   - if success or validation already occurred, do not show transport uncertainty.
3. Keep paymentSubmitting/reset semantics correct:
   - user cannot double-submit while active;
   - after uncertainty, retry is enabled;
   - after validation, field errors/focus work normally;
   - after success, dialog closes normally.
4. Preserve exact same amount, payment_date and request_key on uncertain retry.
5. Preserve the existing message exactly or semantically equivalent:
   "No pudimos confirmar si se registró el cobro. Tus datos siguen aquí. Reintenta con el mismo cobro."
6. Keep existing exception/onCancel handling as defensive paths if harmless.
7. Do not add timers, polling, offline queueing, optimistic payment writes, backend changes, or test weakening.
8. Preserve Playwright simulation: route.fetch() then route.abort('failed'), then retry. This specifically proves server-may-have-committed uncertainty and idempotent replay.
9. At 320/390 prove uncertainty UI, preserved fields, retry label, successful retry, correct single observable collection/balance/history.
10. Do not modify any MT-001/002 semantics or unrelated files.

Expected mutation:
- resources/js/Pages/Orders/Show.tsx
- e2e/order-operations.spec.ts only if mechanically necessary, without weakening the behavioral assertions.

Re-run full MT-003 focused gate and require zero path violations.