# TSK-017 MT-005 — AUD-05 manual price confirmation amount consistency

Operate as the bounded product executor for the active MT-005 / AUD-05 boundary only.

Defect already re-observed:
- resources/js/Pages/Products/Show.tsx uses ProductMoneyText for both stored minor-unit integers and the raw manual decimal input.
- ProductMoneyText assumes minor units, so raw manual values such as "5" can be displayed as "$0.05" even though the server persists $5.00; decimal strings such as "5.7" / "5.70" are also not valid inputs to that helper.
- Server-side decimal-to-minor persistence semantics are already authoritative and must not be weakened or rewritten.

Required outcome:
1. The manual-price confirmation dialog must show exactly the amount the server will persist.
2. Cover raw inputs "5", "5.7", and "5.70": confirmation must show $5.00, $5.70, and $5.70 respectively.
3. After each confirmed save, the persisted/current displayed price must match the confirmation.
4. Scenario-price confirmation must remain correct because scenario values are minor-unit integers.
5. Use exact string/integer decimal semantics; do not use floating-point arithmetic for currency.
6. Keep the implementation minimal and bounded. Do not begin MT-006 global display normalization here.
7. Do not alter backend arithmetic, models, migrations, routes, services, requests, authentication, authorization, or unrelated UI.
8. Do not create or modify governance files and do not invoke Foundry Runner from inside the executor.

Expected implementation boundary:
- resources/js/Pages/Products/Show.tsx
- e2e/product-pricing.spec.ts

Regression expectations:
- Assert confirmation text before submit for integer and decimal manual inputs.
- Assert the current price shown after persistence.
- Preserve the existing scenario path and mobile widths 320 / 390.

Validation is owned by the execution contract.