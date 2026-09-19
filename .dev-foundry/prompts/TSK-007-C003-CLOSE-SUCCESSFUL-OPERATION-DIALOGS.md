# TSK-007 C003 — Close Successful Operation Dialogs

Operate as a bounded product corrective for TSK-007.

## Classified product defect
After C002:
- backend Pest remains 65/65 with 583 assertions PASS;
- TypeScript, Vite build, path policy and git diff check PASS;
- Playwright reaches the real post-order operations UI.

Observed defect:
after a successful collection POST, the server mutation succeeds, balance/history update and `Cobro registrado.` appears, but the native payment dialog remains open and intercepts pointer events. The user cannot proceed naturally to delivery without manually dismissing a stale successful form.

Inspection of `resources/js/Pages/Orders/Show.tsx` confirms the same structural issue exists for all three mutation dialogs:
- payment POST;
- delivery POST;
- cancellation POST.

All use Inertia with preserved state and none close the dialog on successful navigation/mutation.

## Required product mutation
Modify only:
- `resources/js/Pages/Orders/Show.tsx`

For each successful operation:
- payment;
- delivery;
- cancellation;

close the active dialog only in the successful callback.

Preserve these semantics:
- validation/server errors keep the dialog open;
- payment input/errors remain available on failure;
- success flash remains visible after the dialog closes;
- duplicate-submit protection remains;
- payment/fulfillment state independence remains unchanged;
- no service/controller/model/migration/domain changes.

Do not use timeouts or forced DOM manipulation as a workaround. Use the Inertia success lifecycle.

## Validation
Run the full TSK-007 validation matrix.
If another failure remains, preserve evidence and stop for classification rather than blind retry.
