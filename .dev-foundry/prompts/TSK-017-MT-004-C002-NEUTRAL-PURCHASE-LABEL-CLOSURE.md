# TSK-017 MT-004 C002 — Neutral Purchase Label + Closure

Bounded corrective after MT-004 C001.

## Authoritative evidence already green

C001 execution `execution_d6d06e13b1276a375e043ff739a307a35fde257c7dfd7d8fbd586fad0159b018` completed the full focused functional matrix successfully:
- TypeScript PASS;
- Vite build PASS;
- first-use-navigation Playwright 2/2 PASS at 320 and 390;
- HomeTest PASS;
- git diff check PASS.

C001 final status was failed only because the executor improperly created an extra nested governance prompt/contract during its own execution, which caused path-policy/registry integrity failures. Those unapproved duplicate governance files have been deleted by the Governance Author.

## Remaining semantic defect

The generic Home no-orders-today state now has neutral heading/body, but one action still says:
`Registrar primera compra`

The condition proves only that there are no orders today. It does not prove the business has never registered a purchase. An established business on a quiet day must not be shown false onboarding copy.

## Required corrective

1. In `resources/js/Pages/Home.tsx`, change that action to neutral wording, preferably:
   `Registrar una compra`
2. Keep the persistent footer action `Registrar compra` unchanged.
3. In `e2e/first-use-navigation.spec.ts`, assert the neutral empty-state action and use it for the first purchase step. Keep the existing full visible-navigation journey and 320/390 coverage.
4. Do not change HomeController or any domain logic unless strictly required by compilation.
5. Do not create any .dev-foundry file, prompt, contract, execution request, validation request, or other governance artifact. Do not invoke Foundry Runner from inside the executor. Governance Author owns those surfaces.
6. Do not broaden scope.

## PASS gate

- tsc --noEmit PASS;
- fresh Vite build PASS;
- first-use-navigation Playwright 2/2 PASS;
- HomeTest PASS;
- git diff --check PASS;
- path policy PASS with no newly invented governance artifacts.
