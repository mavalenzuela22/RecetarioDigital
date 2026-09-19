# TSK-003 — Ingredients & Purchase Cost History

## Status
Authorized end-to-end by the operator.

## Objective
Implement the first production domain of EmprendimientoOS v1: ingredients and append-only ingredient purchase/cost history.

The task must allow the primary user to record a real-world ingredient purchase in Spanish from a phone, preserve the original purchase facts, derive an exact canonical unit cost on the server, inspect ingredient history, and expose the current cost using the latest effective purchase.

## Authority
Read and follow:
- `docs/PRODUCT-DEFINITION-v1.md`
- `docs/ARCHITECTURE-v1.md`
- `docs/UX-PRINCIPLES-v1.md`
- `docs/design/**`, especially the accepted Direction A design package
- `docs/design/screens/flows.json`, flow `compra`
- `docs/design/implementation-guide/IMPLEMENTATION.md`
- `docs/design/implementation-guide/HANDOFF-CHECKLIST.md`

The formally accepted visual direction is **A — Cocina cálida artesanal**.

The product is Spanish-first with initial locale **es-MX**.

## Scope

### Ingredient
Persist:
- name
- canonical unit: `g`, `ml`, or `piece`
- timestamps

An ingredient is created naturally from the first recorded purchase; do not add a separate enterprise-style master-data workflow unless implementation requires a minimal reusable entry point.

### Ingredient purchase
Capture:
- ingredient
- purchase presentation
- purchased quantity
- purchase unit: `g`, `kg`, `ml`, `l`, `piece`
- total paid
- purchase date
- optional store/provider text
- optional note
- request/idempotency key

Persist immutable economic facts sufficient to reproduce the normalized cost without binary floating-point authority.

Recommended authoritative persisted scales:
- total paid: integer minor currency units (MXN cents)
- entered quantity: integer thousandths of the entered unit
- normalized quantity: integer thousandths of the canonical unit
- normalized unit cost: integer millionths of MXN per canonical unit

The exact schema may vary only if it preserves equal or stronger exactness and historical reproducibility.

### Unit normalization
Canonical dimensions:
- mass: `g`
- volume: `ml`
- count: `piece`

Conversions:
- `1 kg = 1000 g`
- `1 l = 1000 ml`
- `g -> g`
- `ml -> ml`
- `piece -> piece`

A later purchase for an existing ingredient must use a compatible measurement dimension.

### Current cost
v1 current ingredient cost is the **latest recorded purchase by effective purchase date**, with stable tie-breaking by persisted identity/order.

Entering an older/backdated purchase must preserve history but must not replace a newer current cost.

### History
Provide a usable ingredient history view showing purchases in descending effective order with:
- date
- presentation / entered quantity
- total paid
- normalized quantity
- normalized unit cost
- optional store/provider and note when present

There are no edit/delete purchase actions in TSK-003. Purchase history is append-only.

### Idempotency
Submitting the same purchase request key twice must not create duplicate purchases.

The UI must block ordinary duplicate submission while saving, and the server remains authoritative.

## UX
Implement the accepted `compra` flow in Spanish using Direction A.

Required pages:
- ingredient list / cost overview
- ingredient purchase form
- ingredient detail / purchase history

The purchase form must:
- be mobile-first
- use realistic es-MX copy
- use numeric-friendly inputs
- preserve valid form values after validation errors
- expose optional store/note without clutter
- explain the normalized quantity/cost clearly
- show success after confirmed server persistence
- avoid optimistic success for money
- support a dirty-form back/discard pattern where practical

The home shell may be minimally updated so `Registrar compra` leads to the real flow and the visible palette/type system aligns with Direction A.

## Design implementation
Use the accepted design package as implementation authority.

At minimum:
- Direction A exact palette/tokens
- DM Sans body
- Fraunces display
- 48px minimum touch targets
- 52px primary controls
- accepted radii/focus/semantic color behavior
- Spanish copy
- mobile-first 320/390 behavior

Do not add a new UI framework.

Use only the subset of icons/assets needed by this flow. Preserve Lucide attribution already present in the repository design package.

## Testing
Required:
- exact unit-normalization tests
- exact money/unit-cost tests
- persistence test for append-only purchases
- latest-cost test
- backdated-purchase test
- idempotency test
- incompatible-unit validation test
- Inertia feature coverage
- production frontend build
- real Playwright mobile flow for registering a purchase against an isolated test database
- `git diff --check`

The Docker development/test image may add `pdo_sqlite` because the repository already declares SQLite in-memory testing. Production remains MySQL-compatible Percona.

Playwright must use an isolated test SQLite database and must never point the E2E flow at a developer or production database.

## Change-size guardrail
The Runner currently has a known transaction-state defect when a safe observed string array exceeds 32 entries.

Therefore TSK-003 must finish with **no more than 30 visible changed paths total, including governance files**.

Consolidate implementation rather than exceeding this limit.

Do not split the business behavior artificially merely to satisfy this rule; if the complete smallest-safe implementation genuinely cannot fit in 30 paths, stop and report BLOCKED instead of silently exceeding the boundary.

## Explicit non-goals
Do not implement:
- inventory on-hand
- stock depletion
- procurement
- supplier master data
- supplier CRUD
- recipes
- product pricing
- orders
- accounting/tax
- full authentication/RBAC
- translations beyond es-MX
- weighted-average costing
- editing/deleting historical purchases

## Completion
TSK-003 is complete only when:
1. required domain behavior is implemented;
2. purchase history is durable and exact;
3. current-cost semantics are tested;
4. Direction A purchase UX is implemented;
5. full validation is green;
6. visible changed paths are <= 30;
7. repository transaction can commit the bounded change;
8. authorized promotion is carried through as far as repository tooling permits without falsifying transaction lineage.


## Bounded implementation record — 2026-09-18

Implementation is present; **completion/acceptance is BLOCKED on executable backend and mobile validation in this sandbox**. No commit, push, PR, merge, or promotion was performed. The operator's implementation-only instruction supersedes the promotion wording above.

### Implemented decisions

- Named `ingredients.index`, `ingredients.show`, `purchases.create`, and `purchases.store` routes implement the four authorized surfaces. The Home purchase action reaches the real form.
- A transaction creates/reuses an ingredient and appends one purchase. Name identity uses a SHA-256 key of trimmed, whitespace-collapsed, lowercased UTF-8 text, avoiding database-collation-dependent identity. Existing canonical dimensions cannot change through the model or purchase flow.
- Purchases retain all entered facts and exact integer scales. Decimal strings accept a period or comma, without thousands separators, with at most nine integral digits, two fractional payment digits, and three fractional quantity digits. Non-string numbers, exponents, negatives, zero, and out-of-range values are rejected before writes.
- Normalized micros = `round_half_up(total_paid_minor * 10000000 / normalized_quantity_milli)`. Division uses integer quotient/remainder; a remainder at least half the divisor increments the quotient. The bounded numerator is at most `999999999990000000`, within signed 64-bit PHP and SQL BIGINT. Stored scales are serialized as strings so JavaScript cannot lose large integer precision. Six-decimal costs below one micro can round to zero; the current-cost summary explicitly explains this case.
- Current purchase and paginated history both use `purchased_on DESC, id DESC`. Backdated insertions never supersede a later effective date. No purchase update/delete route or action exists; model updates/deletes also reject mutation.
- UUID uniqueness is enforced by the database. Identical retries return the existing purchase; reuse with different facts is rejected. Concurrent unique-key conflicts roll back before replay/retry, including fresh snapshots for MySQL repeatable-read behavior.
- The compact purchase form uses the accepted Direction A tokens and local DM Sans/Fraunces with their OFL licenses. It includes existing-name suggestions, progressive optional fields, local calendar dates, decimal keyboards, server errors without clearing valid data, focus on the invalid field, saving/disabled states, a safe retry message, explicit dirty-back confirmation, and server-confirmed Spanish success on durable history. The form explains normalization; the persisted result supplies the authoritative cost.
- The development/test Dockerfile adds SQLite while retaining `pdo_mysql`. The default Playwright server forces SQLite and an empty database URL, bypasses cached configuration, touches `/tmp/eo-e2e.sqlite` inside the ephemeral container, runs `migrate:fresh --database=sqlite --force`, and refuses to reuse an existing server. Production database configuration is unchanged.

### Validation evidence and limits

- PASS: `npx tsc --noEmit` for the application.
- PASS: production `npm run build` with local fonts; Vite reports its existing large-chunk advisory.
- PASS: Playwright discovery includes the real purchase tests at 320px and 390px. The tests exercise validation retention, dirty-back confirmation, actual save, duplicate UUID replay, incompatible units, backdating, durable history and the ingredient overview.
- BLOCKED: `docker compose build app` cannot write Docker buildx activity under the sandbox; Docker commands cannot access `/Users/martin.valenzuela/.docker/run/docker.sock` (permission denied).
- BLOCKED: `docker compose run --rm app php artisan test`; Pest cases were authored with `RefreshDatabase` but could not execute without Docker/PHP. No backend PASS is claimed.
- BLOCKED: `npx playwright test e2e/ingredient-purchase.spec.ts --project=mobile`; the managed webServer exits before tests because Docker socket access is denied. No mobile or visual QA PASS is claimed.
- BLOCKED: clean `npm ci` verification. Offline installation lacks cached registry metadata; online installation encounters `ENOTFOUND` for registry.npmjs.org. The interrupted `node_modules` installation was restored from locally cached tarballs for the existing lockfile (621 platform-applicable packages; no missing tarballs), and TypeScript/build were rerun. Neither dependency manifest nor lockfile was changed. This recovery is not a clean-install PASS.
- PASS: `git diff --check`. Final expanded `git status --short --untracked-files=all` contains **24 visible changed paths**, including this document, its prompt, and its execution contract (limit: 30). No `docs/design/**` changes.

Foundry must rerun the execution contract with Docker and registry access before accepting this TSK. Physical-device, assistive-technology, public-deployment, and live MySQL runtime verification are not claimed by this local implementation.
