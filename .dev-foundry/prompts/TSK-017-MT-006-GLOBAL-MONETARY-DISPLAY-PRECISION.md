# TSK-017 MT-006 — AUD-19 global monetary display precision

Operate as the bounded product executor for the active MT-006 / AUD-19 boundary only.

Problem:
Ordinary user-facing MXN values are inconsistently rendered with six fixed decimals across ingredients, recipes, products, order economics, history, and operational summaries. Internal arithmetic intentionally uses exact integer minor/micro units and MUST remain unchanged.

Required display policy:
1. Ordinary user-facing MXN amounts MUST render exactly 2 decimals.
   Examples: total paid, batch cost, product cost per piece, sale price, order totals, attributed order cost, profit, revenue, balances, historical product costs/profits, scenario profits.
2. Preserve all internal minor/micro integer precision and persistence semantics.
3. Specialized normalized unit-cost surfaces may expose sub-cent precision ONLY where it is genuinely useful (principally ingredient cost per gram/ml/piece). They must use a bounded formatter that trims meaningless trailing zeroes instead of fixed six-decimal noise. Never display six fixed decimals merely because storage uses micros.
4. Create/reuse a central frontend monetary formatting primitive. Do not scatter ad-hoc toFixed(2), parseFloat, Number-based currency arithmetic, or duplicated formatting helpers.
5. For micro-unit ordinary currency, round for display to cents using exact integer/string semantics. Display rounding must not mutate stored values.
6. OperationalSummary server-generated ordinary cost/profit labels must follow the same 2-decimal display policy using exact integer arithmetic; raw *_micros fields remain untouched.
7. Update existing regression assertions that currently expect six-decimal ordinary amounts, including the product pricing $9.036667 case.
8. Add focused regression coverage proving:
   - ordinary micro amount 9.036667 MXN displays $9.04 MXN;
   - an exact whole-cent/whole-peso amount displays two decimals;
   - negative ordinary profit formats correctly with two decimals;
   - a specialized normalized ingredient unit-cost can retain meaningful sub-cent digits but trims trailing zeros.
9. Do not change pricing/costing formulas, persistence, domain models, migrations, routes, auth, or business decisions.
10. Do not begin TSK-018/019/020 work.
11. Do not create or modify governance files and do not invoke Foundry Runner from inside the executor.

Observed six-decimal call sites include:
- Components/ProductUI.tsx
- Components/PurchaseUI.tsx
- Components/RecipeUI.tsx
- Pages/Ingredients/Index.tsx and Show.tsx
- Pages/Orders/Show.tsx
- Pages/Products/History.tsx, Index.tsx, Show.tsx
- Pages/Recipes/Index.tsx and Show.tsx
OperationalSummary::formatMicros also emits fixed six decimals for ordinary day economics.

Prefer one coherent shared formatter module (for example Components/MoneyUI.tsx) consumed by existing UI wrappers, minimizing call-site churn while making semantic intent explicit: ordinary currency vs specialized unit cost.

Validation is owned by the execution contract.