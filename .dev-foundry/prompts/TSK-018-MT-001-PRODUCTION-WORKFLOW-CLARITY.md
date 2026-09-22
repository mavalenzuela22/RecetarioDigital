# TSK-018 MT-001 — Production workflow clarity (AUD-06 + AUD-13)

Operate as the bounded implementation executor for the active TSK-018 MT-001.

Repository:
- branch: tsk-018-user-recovery-mobile-workflow-clarity
- base HEAD: 369baf5841cdc3a9dcab8a16a14fc60ce380acec
- task authority: docs/TSK-018-USER-RECOVERY-MOBILE-WORKFLOW-CLARITY.md
- remediation authority: docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md

Fix exactly AUD-06 and AUD-13.

AUD-06:
The Production page groups order lines by product. A mixed order can therefore appear inside multiple product groups, but the current "Marcar listo" button transitions the whole order to ready. The domain is intentionally order-level. Do not invent line-level fulfillment.

Required UX:
- make the action scope unmistakably order-level wherever the button appears;
- wording must be natural es-MX and fit 320/390;
- after activation, success/status language must remain consistent with whole-order semantics;
- preserve existing fulfillment transitions and idempotency.

AUD-13:
A reversed/invalid production range is rejected server-side, but the current page does not display form errors. With Inertia preserveState, stale data can remain visible while the user receives no visible indication that the filter failed.

Required UX:
- render production range validation errors visibly and accessibly;
- clearly state that the displayed production data remains for the last accepted range when a submitted range is rejected;
- preserve the rejected input so the user can correct it;
- do not clear valid existing data merely to hide the defect;
- do not change TSK-017's single source of truth for displayed-range/start-production action.

Regression requirements:
1. Add/extend focused browser coverage at both 320 and 390.
2. Prove mixed-order ready action is labeled as affecting the complete order and that marking it ready transitions the whole order, not a line.
3. Prove reversed range submission shows a visible natural-language error/recovery message while the previously accepted range/data remain explicitly identified as the last valid view.
4. Preserve existing range A -> range B -> Start Production consistency regression.
5. Add focused feature assertions if controller/Inertia payload or validation semantics are touched.

Implementation constraints:
- no domain model, migration, route, arithmetic, pricing, auth, or design-overhaul changes;
- no TSK-019/020 work;
- no broad cleanup;
- no floating arithmetic changes;
- do not invoke Foundry Runner or mutate governance files from the executor.

Before editing, inspect the current implementation and tests and reproduce the behavior from code/test evidence. Make the smallest safe complete change.