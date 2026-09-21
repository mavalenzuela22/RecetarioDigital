# TSK-017 MT-004 — AUD-04 First-Use Catalog Navigation

Prepared under the persisted TSK-017 authority. Do not execute until MT-003 is formally CLOSED PASS.

## Objective

Make the first-use business setup path discoverable using visible in-app navigation only:

purchase -> recipe -> product -> price -> order.

No user should need to know or type /recetas or /productos URLs.

## Confirmed current gap

Home currently exposes visible actions for:
- Ver producción
- Registrar compra
- Tomar pedido
- Accesos for admins

HomeController currently passes no recipe/product catalog URLs.

Routes and catalog screens already exist, and Recipes/Index already links to Products/Index. The defect is discoverability/navigation from the normal operational entry point, especially on an empty business database.

The current empty-day state says "Puedes comenzar con un pedido nuevo" and offers Tomar pedido even when there is no configured catalog.

## Required outcome

From Home on an empty business database, a user can discover and complete the setup path without typing a known URL:

1. register first ingredient purchase;
2. navigate visibly to Recetario;
3. create first recipe;
4. navigate visibly to Products;
5. configure the product;
6. set/confirm a selling price;
7. navigate visibly to take the first order.

The navigation may use Home as a hub and existing back-navigation; do not invent a wizard unless truly necessary.

## Product behavior

Home should expose clear catalog/setup actions for Recetario and Productos in the normal action area.

The empty-day state must not imply that a new order is the only/primary next step when no catalog exists. Prefer visible setup guidance and actions based on current catalog readiness if existing data can provide it cheaply; otherwise provide persistent catalog navigation plus neutral empty-state guidance.

Do not hide existing operational actions for established users.

## Required regression

Create a focused browser test, preferably a new `e2e/first-use-navigation.spec.ts`, using the default Playwright isolated database which begins with only the provisioned E2E user.

The test must:
- start at Home;
- never call `page.goto` with /compras, /recetas, /productos, or /pedidos after initial Home navigation;
- use only visible links/buttons to traverse the setup flow;
- register an ingredient purchase;
- return/navigate visibly to Home/Recetario;
- create a recipe from visible navigation;
- reach Products via visible navigation;
- configure the recipe as a product;
- set a manual price;
- reach order capture via visible navigation;
- create an order;
- prove the order is successfully registered;
- execute at 320 px and 390 px or otherwise explicitly cover both widths.

A helper may use route-independent form interactions, but no known-route shortcuts for the journey itself.

## Scope

May mutate:
- app/Http/Controllers/HomeController.php
- resources/js/Pages/Home.tsx
- e2e/first-use-navigation.spec.ts
- tests/Feature/HomeTest.php
- shared shell/navigation only if a focused implementation proves Home-only navigation cannot meet the objective.

Do not redesign the visual system; accepted-design conformance belongs to TSK-020.
Do not absorb unrelated UX findings.
Preserve all validated MT-001 through MT-003 changes.

## Validation

Before browser:
- npx tsc --noEmit
- npm run build
- remove only stale Docker container(s) publishing host port 18080

Then:
- Playwright first-use-navigation spec on mobile
- HomeTest Pest
- relevant existing purchase/recipe/product/order focused tests only if touched behavior warrants
- git diff --check

## PASS gate

- fresh empty-business user can complete the entire visible-navigation journey without known URL entry;
- 320/390 behavior remains usable;
- existing operational actions remain available;
- TypeScript/build/Pest/path policy/diff PASS.
