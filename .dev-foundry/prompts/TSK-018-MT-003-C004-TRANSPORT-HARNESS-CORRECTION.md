# TSK-018 MT-003 C004 — transport harness correction

Validation/test corrective for AUD-12. Product semantics must not be weakened.

Observed evidence:
- Current payment product implementation uses axios with deterministic classification:
  - 422 => validation;
  - HTTP error response => server error;
  - no response => transport uncertainty;
  - 2xx => confirmed success then server reload.
- Focused Pest is green: 6 tests / 59 assertions, including exact append-only collection idempotency/replay semantics.
- TypeScript/build/diff are green.
- AUD-08 E2E is green.
- The remaining E2E uses Playwright route.fetch() followed by route.abort('failed'). This does NOT deterministically cause the original browser/axios request to observe a transport failure. route.fetch() creates/executes a fetched request and the subsequent abort does not provide the intended commit-then-lost-response signal to axios in this harness.
- Therefore the current E2E failure is a test-mechanism defect, not evidence that the product uncertainty branch is absent.
- C003 contract also accidentally blocked inherited prior-MT changed paths; fix path policy by allowing all current inherited dirty paths and do not re-block them.

Required corrective:
1. Keep the product implementation in resources/js/Pages/Orders/Show.tsx unchanged unless a truly mechanical testability fix is required.
2. Change only the Playwright transport simulation for the first collection attempt:
   - intercept the first matching POST;
   - abort it directly with a genuine network-style reason such as `internetdisconnected`;
   - do NOT use route.fetch() before the abort.
3. Assert at 320 and 390:
   - natural uncertainty alert appears;
   - amount/date remain unchanged;
   - retry button appears;
   - remove/disable the abort intercept and retry;
   - retry succeeds and server-refreshed balance/payment history is correct;
   - only one collection is visible.
4. Keep the server-side idempotency evidence authoritative for the separate "request may have committed then replayed with same request_key" property. Do not attempt to fake commit-then-lost-response with Playwright if the harness cannot model it reliably.
5. Do not weaken product semantics or E2E assertions.
6. Keep delivery/cancellation and AUD-08 regressions unchanged.
7. Path policy:
   - allow the full current dirty path set inherited from MT-001/002/003;
   - do not block any path already dirty before this corrective;
   - no new product mutation beyond e2e/order-operations.spec.ts unless unavoidable.

Expected mutation:
- e2e/order-operations.spec.ts
- product source should remain unchanged.

Run the complete MT-003 focused validation and require:
- zero path violations;
- Pest green;
- TS/build green;
- order-capture + order-operations Playwright green;
- git diff --check green.
