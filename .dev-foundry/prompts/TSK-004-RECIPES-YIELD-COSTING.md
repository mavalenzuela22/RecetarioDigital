# EmprendimientoOS — TSK-004 IMPLEMENTATION

Operate as the bounded implementation executor for **TSK-004 — Recipes & Yield Costing**.

## Read first
Read, in order:
1. `docs/TSK-004-RECIPES-YIELD-COSTING.md`
2. `docs/PRODUCT-DEFINITION-v1.md`
3. `docs/ARCHITECTURE-v1.md`
4. `docs/UX-PRINCIPLES-v1.md`
5. `docs/TSK-003-INGREDIENTS-PURCHASE-COST-HISTORY.md`
6. `docs/design/screens/flows.json` flow `receta`
7. `docs/design/implementation-guide/IMPLEMENTATION.md`
8. `docs/design/components/SPECIFICATION.md`
9. `docs/design/brandbook/microcopy.json`

Inspect the existing TSK-003 models, migration, service, tests, Playwright harness and production UI before changing code.

## Required result
Implement the smallest safe complete production Recipes domain described by the task specification.

Key invariants:
- every recipe save creates an immutable version;
- current cost dynamically uses each ingredient's current TSK-003 purchase;
- every version preserves an immutable economic snapshot from save time;
- missing ingredient cost means incomplete, never zero;
- no binary floats as economic authority;
- exact server arithmetic must avoid signed-64-bit intermediate overflow;
- current recipe edits reject stale base versions;
- duplicate submissions are server-idempotent;
- existing ingredients only;
- Spanish-first mobile UX;
- optional image through Laravel storage abstraction;
- no product/pricing/order/inventory scope.

## Implementation discipline
Do not redesign the application.
Do not change `docs/design/**`.
Do not add dependencies or modify Composer/npm manifests.
Do not weaken any TSK-003 invariant or test.
Do not mutate historical ingredient purchases.
Do not use JavaScript calculations as monetary authority.

Consolidate files where sensible. The complete task, including the three governance files already present, must remain at **<= 30 visible changed paths**.

If implementing the authorized behavior safely requires exceeding 30 visible paths or touching a blocked path, stop and report BLOCKED with the exact reason.

## Validation
Run every validation command from the execution contract and preserve truthful evidence.
A command failure is not permission for unrelated mutation. Classify it first as product defect, test defect, validation harness defect, infrastructure/tooling defect, or governance defect.

Do not claim PASS without actual execution evidence.
