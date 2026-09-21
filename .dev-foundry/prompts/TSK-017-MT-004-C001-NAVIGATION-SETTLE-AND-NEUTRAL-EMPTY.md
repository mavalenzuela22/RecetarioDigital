# TSK-017 MT-004 C001 — Navigation Settle + Neutral Empty State

Bounded corrective after MT-004 initial validation.

## Evidence

Initial MT-004:
- path policy PASS;
- TypeScript PASS;
- Vite build PASS;
- HomeTest PASS;
- git diff check PASS;
- Playwright failed only in the new visible-navigation journey after saving the first purchase.

The test clicked two identical "← Volver" links back-to-back without asserting the intermediate Inertia navigation had settled. Because Inertia navigation is client-side, the second locator/click can resolve against the pre-transition page and repeat the same destination. The test then waits for Home's "Recetario" link while not actually on Home.

## Additional semantic review finding

The current MT-004 Home empty state says:
"Empieza por preparar tu catálogo"

but the condition is only "no production/deliveries/collections today". That does NOT prove the business lacks a catalog. An established business with a quiet day would receive false setup guidance.

The MT authority explicitly permits persistent catalog navigation plus neutral empty-state guidance when readiness is not observed.

## Corrective

1. Keep the Home recipe/product navigation and controller URLs.
2. Make the no-orders-today empty-state copy neutral. It must not assert that catalog setup is missing.
   Example intent: no orders today; user may register a purchase, review the catalog, or take a new order when appropriate.
   Keep setup/catalog actions visible.
3. Repair `e2e/first-use-navigation.spec.ts` so every visible back-navigation step waits for the expected intermediate surface/URL before continuing.
   - after ingredient show -> Ingredients index, assert the Ingredients surface;
   - then visible back -> Home, assert Home/URL;
   - similarly after recipe/product flows, prove each visible return step rather than issuing rapid duplicate clicks.
4. No `page.goto` route shortcuts may be introduced after initial Home.
5. Preserve 320 and 390 coverage.
6. Do not introduce catalog-readiness backend queries merely to personalize empty copy; neutral copy is sufficient and avoids scope expansion.

## PASS gate

- TypeScript PASS;
- fresh Vite build PASS;
- first-use-navigation Playwright 320 + 390 PASS;
- HomeTest PASS;
- git diff check PASS;
- path policy PASS.
