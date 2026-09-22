# TSK-018 MT-003 C003 — direct payment transport observability

Bounded corrective for AUD-12 only.

Observed evidence across MT-003, C001, C002:
- AUD-08 dirty-draft protection is green at 320/390.
- OrderOperations Pest is green: 6 tests / 59 assertions.
- TypeScript/build/diff/path policy are green.
- Only AUD-12 remains: an intercepted collection POST that reaches the server through Playwright route.fetch() and then has its browser response aborted does not produce a reliable Inertia form lifecycle callback. exception/onCancel/onFinish strategies have all failed to expose the uncertainty state.
- axios is already an application dependency.
- Laravel uses its standard XSRF-TOKEN cookie mechanism.

Required correction:
1. Stop using Inertia useForm.post for the PAYMENT submission only. Keep useForm as local form/error state if useful.
2. Submit the payment with axios.post directly to paymentUrl using the exact current payload:
   - amount
   - payment_date
   - request_key
3. Send Accept: application/json so Laravel validation errors are observable as 422 JSON instead of redirect-based HTML validation.
4. Preserve Laravel CSRF/XSRF protection. Use normal same-origin axios XSRF behavior; do not disable CSRF and do not add secrets.
5. Classify outcomes deterministically:
   a. Confirmed success (2xx after redirect/follow as applicable):
      - mark success;
      - close payment dialog;
      - refresh the current order facts from the server using router.reload() or an equivalent Inertia refresh;
      - visible order balance/payment history must come from server state.
   b. HTTP 422 with validation errors:
      - map server errors back into paymentForm field errors;
      - focus first invalid field;
      - do NOT show transport uncertainty.
   c. Any HTTP response with an error status other than 422:
      - show a natural es-MX server-error message that does not claim transport uncertainty;
      - keep form values and request_key;
      - allow retry.
   d. Network/transport failure where axios error has NO HTTP response:
      - show exactly/semantically:
        "No pudimos confirmar si se registró el cobro. Tus datos siguen aquí. Reintenta con el mismo cobro."
      - preserve amount, payment_date, request_key unchanged;
      - show "Reintentar cobro";
      - allow retry with the same payload/key.
6. Remove obsolete payment-specific Inertia exception/onCancel/onFinish machinery if no longer needed.
7. Keep delivery/cancellation using existing Inertia forms unchanged.
8. Keep no polling, no timers, no offline queue, no optimistic payment mutation, no backend changes.
9. Do not rotate request_key after uncertain transport.
10. On retry after the first request may already have committed, existing backend idempotency must collapse the replay; observable payment history must show only one collection.

Regression:
- Keep Playwright simulation exactly meaningful:
  first matching collection request -> route.fetch() to let server process -> route.abort('failed') to lose browser response.
- At 320 and 390 prove:
  uncertainty alert visible;
  amount/date preserved;
  Reintentar cobro visible;
  retry succeeds after route continue;
  order refresh shows correct balance;
  payment history contains the advance and exactly one collection amount, not duplicate collections.
- Preserve all existing order-capture, delivery and cancellation checks.
- Focused Pest remains green.

Expected mutation:
- resources/js/Pages/Orders/Show.tsx
- e2e/order-operations.spec.ts only if necessary for exact single-collection assertion; do not weaken semantics.
No backend/domain/config mutation.
