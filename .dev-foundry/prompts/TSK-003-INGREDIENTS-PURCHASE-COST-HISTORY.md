# EmprendimientoOS — TSK-003 IMPLEMENTATION

Operate as the bounded implementation executor for **TSK-003 — Ingredients & Purchase Cost History**.

Repository branch:
`tsk-003-ingredients-purchase-cost-history`

Read first:
- `docs/TSK-003-INGREDIENTS-PURCHASE-COST-HISTORY.md`
- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- `docs/design/README.md`
- `docs/design/tokens/tokens.json`
- `docs/design/components/SPECIFICATION.md`
- `docs/design/components/registry.json`
- `docs/design/screens/flows.json`
- `docs/design/implementation-guide/IMPLEMENTATION.md`
- current Laravel/Inertia/React shell

Do not reinterpret the accepted brand. Direction A — Cocina cálida artesanal is already accepted.

## Implementation target

Implement a complete vertical slice for ingredients and purchases.

### Persistence
Use two domain records:
- Ingredient
- IngredientPurchase

Ingredient should retain:
- name
- canonical unit `g|ml|piece`

IngredientPurchase should retain the human purchase facts plus exact derived facts.

Prefer integer-scaled authoritative values:
- `total_paid_minor`: MXN cents
- `purchase_quantity_milli`: thousandths of entered unit
- `normalized_quantity_milli`: thousandths of canonical unit
- `normalized_unit_cost_micros`: millionths of MXN per canonical unit

Also retain:
- purchase_unit
- presentation
- purchased_on
- optional store
- optional note
- unique request_key UUID
- timestamps

No PHP/JavaScript floating point may be the authoritative source of persisted money or normalized unit cost.

Implement deterministic decimal-string parsing and integer arithmetic.

For normalized unit cost in micros:
- derive from cents and normalized thousandths using integer arithmetic
- use a documented deterministic rounding rule
- add exact tests for representative values

### Semantics
Conversions:
- kg -> g ×1000
- l -> ml ×1000
- g/ml/piece unchanged

Existing ingredient measurement dimension cannot silently change.

Current cost:
- select latest purchase by `purchased_on DESC`
- deterministic persisted tie-break after date
- a newly inserted older purchase must not become current

Append-only:
- expose no update/delete purchase endpoint or UI
- do not mutate an older purchase when a new price is recorded

Idempotency:
- request_key UUID unique
- duplicate same request must not create a second purchase

### Web surface
Implement:
- GET `/ingredientes`
- GET `/ingredientes/{ingredient}`
- GET `/compras/nueva`
- POST `/compras`

Use idiomatic named routes.

The purchase form should support typing/selecting an existing ingredient name and naturally creating a new ingredient from its first purchase without introducing supplier/master-data CRUD.

After successful persistence, redirect to a useful ingredient/history surface with clear Spanish success feedback.

### UX/UI
Implement the accepted purchase flow, not the old shell aesthetic.

Use exact Direction A values and the local accepted fonts.

Required:
- es-MX visible copy
- mobile-first 320/390
- accessible labels
- numeric inputMode where appropriate
- 48px touch minimum
- 52px primary controls
- focus/error/disabled/loading states
- no hover dependency
- preserve valid data after 422 validation
- saving state prevents ordinary duplicate tap
- optional store/note progressively disclosed
- server-confirmed success
- clear current cost and historical cost presentation

A lightweight reusable AppShell/Field/Money/Icon subset is encouraged, but do not build a giant component library in this TSK.

Update the Home page only as necessary to align its visible design tokens and route `Registrar compra` to the real flow.

Copy only the production font assets required by the implementation from `docs/design/assets/fonts`. Do not duplicate the entire design package into application assets.

### Tests
Use Pest and `RefreshDatabase`.

Cover:
- 1 kg -> 1000 g
- 1 l -> 1000 ml
- exact cost, e.g. MXN 42.00 / 1000 g = MXN 0.042000/g
- latest purchase determines current cost
- backdated purchase does not supersede newer current cost
- duplicate request_key is idempotent
- incompatible unit dimension rejected
- history rendered in descending effective order
- form validation preserves submitted fields via Inertia/validation behavior where practical

Add a real Playwright mobile test for the purchase flow.

Update `Dockerfile` to include `pdo_sqlite` for test/dev only.

Update `playwright.config.ts` so its default managed webServer:
- uses an isolated SQLite file inside the ephemeral Docker container
- creates/touches that DB
- runs `migrate:fresh --force`
- serves the application
- cannot accidentally use production/developer MySQL

Do not weaken production MySQL/Percona compatibility.

## Hard scope guardrail
The final repository state for TSK-003 must contain **<= 30 visible changed paths total**, counting:
- this TSK document
- its prompt
- its execution contract
- all implementation/test files

Before finishing, inspect `git status --short` and count visible paths.

If the count would exceed 30:
- consolidate files/components;
- do not remove required behavior/tests;
- if still impossible, stop and report BLOCKED.

Do not touch `docs/design/**`.

Do not update package/composer dependencies unless absolutely required; no new framework is authorized.

Do not commit, push, create PRs, or merge. Foundry handles repository boundaries after validation.

