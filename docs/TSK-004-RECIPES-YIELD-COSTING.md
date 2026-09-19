# TSK-004 — Recipes & Yield Costing

## Status
Authorized end-to-end by the operator on 2026-09-18.

## Objective
Implement the smallest safe complete Recipes domain for EmprendimientoOS v1 so the primary user can create and revise a recipe from a phone, capture its ingredient quantities and expected yield, see an authoritative current batch/unit cost, and retain an immutable historical recipe/economic reference.

The task must remain practical for a small food business: capture human facts, calculate the rest, and do not expand into product pricing, inventory, orders, or production planning.

## Authority
Read and follow:
- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- `docs/TSK-003-INGREDIENTS-PURCHASE-COST-HISTORY.md`
- `docs/design/**`, especially accepted Direction A
- `docs/design/screens/flows.json`, flow `receta`
- `docs/design/implementation-guide/IMPLEMENTATION.md`
- `docs/design/components/SPECIFICATION.md`
- `docs/design/brandbook/microcopy.json`

Do not modify `docs/design/**`.

## User need
The user must be able to express facts such as:
- “Esta receta usa 320 g de harina.”
- “También usa 250 ml de leche.”
- “Esta tanda rinde 12 piezas.”

The server answers:
- cost of each ingredient used;
- current recipe batch cost;
- current cost per yielded unit;
- whether cost is incomplete because an ingredient has no purchase cost.

## Domain model

### Recipe
A stable identity for one recipe. It owns an ordered sequence of immutable versions.

### RecipeVersion
Every successful save creates a new immutable recipe version, including the first save as version 1.

Persist at minimum:
- recipe identity;
- monotonic version number;
- name, max 120;
- expected yield, integer >= 1;
- optional preparation instructions, max 10000;
- optional notes, max 2000;
- optional image path;
- immutable snapshot batch cost when complete, otherwise null;
- immutable snapshot cost per yielded unit when complete, otherwise null;
- timestamps sufficient to establish creation order.

An edit is based on a current version identity. If a newer version already exists at save time, reject the stale save rather than silently forking/overwriting.

### Recipe ingredient line
Each version contains at least one ingredient line.

Persist at minimum:
- recipe version;
- ingredient;
- explicit stable display/order position;
- entered quantity at scale 3;
- entered unit from `g|kg|ml|l|piece`;
- normalized quantity at scale 3 of canonical `g|ml|piece`;
- snapshot ingredient purchase identity used at save time when available;
- snapshot normalized ingredient unit cost in micros when available;
- snapshot ingredient usage cost in micros when available.

Lines are immutable because their parent RecipeVersion is immutable.

Do not create an ingredient master workflow here. Recipe lines select existing TSK-003 ingredients.

## Units and validation
Reuse TSK-003 dimensional semantics:
- mass canonical: `g`, compatible input `g|kg`;
- volume canonical: `ml`, compatible input `ml|l`;
- count canonical: `piece`, compatible input `piece`.

Recipe quantity input:
- decimal string;
- > 0;
- at most 3 fractional digits;
- no binary floating-point authority;
- reject incompatible dimensions.

Expected yield:
- integer >= 1.

A version must contain at least one line.
Reject duplicate ingredient lines within one version; the user should edit the existing line instead of creating ambiguous duplicates.

## Cost semantics

### Current cost
The current recipe cost is dynamic:
1. use the current RecipeVersion definition;
2. for every ingredient line, resolve the current ingredient purchase using TSK-003 semantics: `purchased_on DESC, id DESC`;
3. compute exact usage cost on the server;
4. sum exact usage costs into batch cost;
5. divide by expected yield using explicit deterministic half-up rounding.

A later ingredient purchase may therefore change the current recipe cost without creating a new RecipeVersion.

### Immutable version snapshot
When a RecipeVersion is saved, snapshot the ingredient purchase/cost basis used at that moment for each line plus batch and per-yield-unit cost when all costs are available.

Those snapshot values never change after the version is created.

If one or more ingredients have no current purchase cost:
- saving the recipe is still allowed;
- the version snapshot cost remains incomplete/null;
- identify the missing ingredient(s);
- never substitute zero;
- future current-cost queries may become complete when purchases later exist, while the original version snapshot remains an honest record of what was knowable when saved.

This TSK does not create product/order cost snapshots. Later TSKs must consume recipe/version economics without rewriting this history.

## Exact arithmetic
Continue TSK-003 exactness:
- quantities use scaled integers;
- ingredient unit costs use integer micros of MXN per canonical unit;
- usage, batch, and per-yield costs use integer micros;
- no float/double is authoritative for persisted or server-side economic calculations;
- JSON values that can exceed JavaScript safe integer range are serialized as strings.

Cost formula:
`usage micros = round_half_up(unit_cost_micros × normalized_quantity_milli / 1000)`.

Implementation must avoid intermediate signed-64-bit overflow. Use mathematically equivalent quotient/remainder reduction or another exact bounded technique. Detect an unrepresentable final value and return a validation/domain error; never overflow, cast through float, or silently clamp.

Batch summation must likewise detect overflow before addition.

## UX
Implement the accepted Direction A `receta` journey in production-quality Spanish (`es-MX`).

Required surfaces:
- recipe list / “Recetario”;
- create recipe;
- recipe detail/current cost;
- edit current recipe, which saves a new version.

The form includes:
- name;
- existing ingredient selector/search;
- quantity + compatible unit;
- add/remove ingredient line;
- expected yield;
- preparation;
- notes;
- optional image;
- server-authoritative cost disclosure;
- immutable-version notice;
- sticky primary action `Guardar receta`.

Use:
- 48px minimum touch targets;
- approximately 52px primary action;
- numeric-friendly quantity/yield inputs;
- valid-field preservation after validation failure;
- first-invalid-field focus;
- dirty-back confirmation;
- saving/disabled duplicate-submit protection;
- explicit missing-cost state.

Required copy includes the accepted semantics:
- `Agregar ingrediente`
- `Rendimiento esperado`
- `Costo de esta receta`
- `Falta el costo de un ingrediente. No podemos calcular el total.`
- `Receta guardada como nueva versión.`

New recipe starts empty. Edit shows the current version and its number.

### Image
Image is optional and never blocks a recipe.
Accept JPEG/PNG/WebP, maximum 5 MiB.
Use Laravel storage abstraction; do not store image binaries in relational tables.
A historical version's image reference must not be overwritten by a later version.

## Idempotency / concurrency
Do not allow accidental duplicate saves from double-tap.
Use a server-authoritative request/idempotency mechanism or equivalent atomic protection.
A replay with the same key and identical payload must not create another version.
Reuse with different payload must be rejected.
Concurrent/stale editing of the same recipe must not silently overwrite or fork from an obsolete base version.

## Testing
At minimum cover with Pest:
- compatible unit normalization;
- incompatible unit rejection;
- exact usage-cost half-up rounding;
- arithmetic overflow rejection;
- exact batch cost;
- exact per-yield cost;
- incomplete cost without zero substitution;
- current costing follows latest TSK-003 purchase;
- backdated purchase does not replace a newer current cost;
- version 1 creation;
- edit creates version 2 and leaves version 1 immutable;
- snapshot remains unchanged after later ingredient purchase;
- current cost changes after later ingredient purchase;
- stale edit rejection;
- duplicate request/idempotency behavior;
- recipe surface/Inertia behavior;
- image validation/storage behavior.

Playwright must exercise the real primary recipe flow at 320px and 390px against the isolated SQLite E2E server:
- establish ingredient purchase prerequisites;
- create recipe;
- add at least two ingredient lines;
- set yield;
- save successfully;
- verify server-confirmed batch/unit cost;
- edit and create a new version;
- verify no horizontal overflow and production Spanish copy.

Validation:
- `docker compose build app`
- Composer install
- `npm ci`
- full Pest
- TypeScript no-emit check
- production Vite build
- real Playwright recipe flow
- `git diff --check`

## Scope guardrail
Target <= 30 visible changed paths total including governance files because Runner durable transaction records are known to become unsafe/noisy with large repeated path arrays.

Prefer approximately 18–24 paths.
If the smallest safe complete implementation cannot stay <= 30, stop and report BLOCKED rather than silently expanding.

## Explicit non-goals
Do not implement:
- inventory/depletion;
- procurement;
- supplier master;
- products;
- sale prices or pricing scenarios;
- additional product/order costs;
- margin/markup;
- orders/payments/fulfillment;
- production planning;
- analytics/as-of UI beyond recipe-version facts required here;
- accounting/tax;
- advanced auth/RBAC;
- additional languages;
- weighted-average ingredient costing;
- complex offline sync;
- general refactors unrelated to this task.

## Completion
TSK-004 is technically ready for promotion only when the bounded implementation exists, all required validation is green, evidence is complete, the path budget is respected, and historical/current economics behave as defined above.

## Bounded implementation record — 2026-09-18

Implementation is present; completion/acceptance remains **BLOCKED on executable backend and mobile validation in this sandbox**. No commit, push, PR, merge, or promotion was performed.

### Implemented decisions

- Recipe identity, immutable versions, and immutable ingredient lines are persisted in one migration. Every successful save creates a version, including version 1; edits require the current version identity.
- Recipe lines select only existing TSK-003 ingredients, normalize compatible `g|kg`, `ml|l`, or `piece` quantities to canonical thousandths, reject duplicates, and preserve entered unit/quantity facts.
- Current costing resolves each ingredient using TSK-003 `purchased_on DESC, id DESC` semantics. Version snapshots store purchase identity, unit cost, usage cost, batch cost, and per-yield cost; incomplete costs remain null and never become zero.
- Usage cost uses quotient/remainder decomposition for `unit_cost_micros × quantity_milli / 1000`; safe multiply/add guards reject an unrepresentable result before any signed-64-bit overflow. Current-cost JSON is string-serialized.
- Recipe request keys and payload hashes provide duplicate replay without a second version and reject reuse with changed data. A locked recipe row rejects stale edit bases with HTTP 409. Optional JPEG/PNG/WebP images use the Laravel public storage abstraction and unique paths; later versions never overwrite a prior image reference.
- Production surfaces include Spanish list, create, detail/current-cost, and edit/new-version flows. The form has existing-ingredient search/selection, compatible units, yield, preparation, notes, optional image, server-confirmed cost disclosure, immutable-version copy, dirty-back confirmation, and duplicate-submit protection.
- Focused Pest coverage and a real mobile Playwright flow were added for exact arithmetic, overflow, incomplete/current/snapshot costing, versions, stale/idempotent saves, image storage, Inertia surfaces, two ingredient lines, 320px/390px recipe creation, cost confirmation, and version 2 editing.

### Validation evidence and limits

- PASS: `git diff --check` on the final visible delta.
- PASS: TypeScript no-emit and Playwright discovery were observed before the final dependency-environment failure; the final contract rerun could not repeat them because `npm ci` removed the local executable installation and registry access was unavailable. No final frontend build PASS is claimed.
- BLOCKED: `docker compose build app` cannot write Docker buildx activity under the sandbox (`operation not permitted`); Docker also cannot access `/Users/martin.valenzuela/.docker/run/docker.sock`.
- BLOCKED: `docker compose run --rm app composer install --no-interaction --prefer-dist` and `docker compose run --rm app php artisan test` because Docker socket access is denied. Host PHP is unavailable, so no backend PASS is claimed.
- BLOCKED: clean `npm ci` verification. The command ended with npm's `Exit handler never called` error; offline recovery then stopped at an uncached `@types/estree` response, and online commands failed with `ENOTFOUND registry.npmjs.org`. Neither dependency manifest nor lockfile was changed.
- BLOCKED: `npm run build` because the post-`npm ci` local Vite executable is absent; `npx playwright test e2e/recipe-costing.spec.ts --project=mobile` cannot resolve Playwright without registry access. No mobile or visual QA PASS is claimed.
- PASS: final visible changed-path count is **18**, including this document, its prompt, and its execution contract; no `docs/design/**` changes and no blocked-path changes were made.

Foundry must rerun the execution contract with Docker, PHP/Composer, a clean npm registry/cache, and the isolated SQLite Playwright server before accepting this TSK. Physical-device, assistive-technology, public-deployment, and live MySQL runtime verification are not claimed by this local implementation.
