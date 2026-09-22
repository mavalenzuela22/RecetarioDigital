# TSK-018 MT-003 — Order reliability (AUD-08 + AUD-12)

Operate as the bounded implementation executor for active TSK-018 MT-003.

Repository:
- branch: tsk-018-user-recovery-mobile-workflow-clarity
- inherited MT-001 and MT-002 changes are intentionally dirty
- task authority: docs/TSK-018-USER-RECOVERY-MOBILE-WORKFLOW-CLARITY.md
- remediation authority: docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md

Fix exactly AUD-08 and AUD-12.

AUD-08 — dirty order draft can be discarded by visible Back/navigation
Observed implementation:
- Orders/Create.tsx already computes form.isDirty, installs beforeunload, and defines a confirm-based back() function.
- That back() is not wired into OrderShell, because OrderShell currently only forwards a URL back prop and not AppShell's onBack callback.
- Purchases/Create.tsx already contains a better established pattern: custom discard dialog, allowLeave/saving refs, beforeunload protection, and AppShell onBack wiring.

Required outcome:
- wire the order-create visible Back control through an explicit dirty-draft guard;
- prefer the already-established custom discard dialog pattern from Purchases/Create rather than native confirm;
- if clean, Back navigates normally;
- if dirty, Back keeps the draft and asks whether to continue editing or discard;
- while saving, do not allow accidental duplicate/discard navigation;
- preserve browser-level beforeunload protection for real unloads;
- successful order save must not trigger a discard warning;
- do not alter order calculations, stale-product recovery, request-key semantics, or product availability behavior.
- If OrderShell needs an onBack prop, add only that narrow pass-through to AppShell.

AUD-12 — failed/offline collection submission has no visible failure/retry guidance
Observed implementation:
- Orders/Show.tsx payment form posts with an idempotent paymentRequestKey but only handles validation errors.
- RecordOrderPayment is append-only and idempotent by request_key/request_hash.
- Purchases/Create.tsx already handles Inertia transport exceptions with router.on('exception') and preserves the same request key for safe retry.

Required outcome:
- a transport/network failure while registering a collection must show a visible natural es-MX message that the app could not CONFIRM whether the collection was registered;
- do not claim the collection definitely failed, because transport loss may occur after server commit;
- keep the payment dialog/data available so the user can safely retry;
- retry must use the SAME request_key and same payload, relying on existing idempotency to avoid duplicate collections;
- button/state should make retry clear (for example "Reintentar cobro");
- validation failures remain field-level and must not be mislabeled as network uncertainty;
- successful retry closes the dialog and shows the normal confirmed success state;
- no offline queue, optimistic payment mutation, or fake local success.

Regression requirements:
1. order-capture Playwright at 320 and 390:
   - start a new order and make it dirty;
   - activate visible Back;
   - prove a discard dialog appears and draft remains when choosing continue editing;
   - activate Back again and discard; prove navigation reaches orders index;
   - preserve no horizontal overflow.
2. order-operations Playwright at 320 and 390:
   - open collection dialog;
   - intercept/abort the collection POST once to simulate transport failure;
   - prove natural-language uncertainty/retry guidance appears and entered amount/date remain;
   - remove interception and retry;
   - prove the collection is registered exactly once observably (correct balance/payment history, no duplicate collection);
   - preserve existing delivery/cancellation regressions.
3. Focused feature tests remain green; add feature coverage only if backend semantics are touched. Backend changes are not expected.
4. TypeScript, production build, git diff --check green.

Implementation constraints:
- preserve exact integer money arithmetic;
- preserve append-only OrderPayment semantics;
- preserve idempotent request_key replay semantics;
- preserve fulfillment/payment independence;
- no migrations/models/routes;
- no TSK-019 or TSK-020 work;
- no broad component redesign;
- do not invoke Foundry Runner or mutate governance files from executor.

Reuse established patterns in Purchases/Create.tsx where appropriate. Make the smallest safe complete change.