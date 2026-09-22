# TSK-018 MT-001 C001 — mixed-order duplicate locator correction

This is a bounded corrective for MT-001 validation only.

Observed failure:
- Product code, TypeScript, Vite build, focused Pest (7 tests / 83 assertions), path policy and diff-check all passed.
- The sole failure was Playwright strict-mode at e2e/today-production.spec.ts:107.
- In the mixed-order scenario the same order/customer intentionally appears once under each of its two product groups. The locator `getByText(customer, { exact: true })` therefore correctly resolves to 2 elements.
- This is not a product defect; it is a stale/incorrect test expectation introduced by MT-001 itself.

Required correction:
- modify only e2e/today-production.spec.ts;
- replace the single-element customer visibility expectation with an assertion that accurately proves the mixed order is represented in both product groups (for example count 2 and/or visibility through the already-derived order rows);
- preserve all AUD-06 assertions that the action says "Marcar todo el pedido como listo", appears in both groups, and after one activation both representations become ready and both action buttons disappear;
- preserve AUD-13 reversed-range assertions unchanged unless mechanically necessary;
- do not change product code, wording, domain behavior, config, or any unrelated test.

Re-run the complete MT-001 focused gate and PASS only if all commands pass.