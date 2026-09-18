# EmprendimientoOS v1.0 — Architecture Baseline

## Architectural intent
Build a small, maintainable, mobile-first PWA that can grow without turning v1 into an ERP.

The architecture should optimize for:
- fast mobile interaction
- correctness of money and quantity calculations
- historical reproducibility
- simple deployment
- low operational overhead
- clear domain boundaries

## Initial stack
The initial implementation target is:

- **Next.js** with App Router
- **TypeScript**
- **React**
- **Tailwind CSS**
- accessible headless UI primitives where useful
- **PostgreSQL**
- **Prisma ORM**
- **Vitest** for fast unit/domain tests
- **Playwright** for critical mobile end-to-end flows
- installable **PWA** shell with web app manifest and service-worker strategy introduced in a bounded step

Hosting/auth/storage vendors remain replaceable and are not hard-bound in the initial bootstrap.

## Application shape
Prefer one deployable web application for v1.

Suggested internal boundaries:
- `ingredients`
- `recipes`
- `products`
- `costing`
- `orders`
- `production`
- `analytics`
- `shared`

Business rules belong in domain/service modules, not inside React components.

## Money
Persist money in integer minor units or a database decimal representation with explicit scale.

Rules:
- never use JS floating-point values as authoritative persisted money
- formatting is a presentation concern
- currency must be explicit in domain configuration even if v1 initially uses one currency

## Quantities and units
Use canonical units per measurement dimension.

Initial dimensions:
- mass: g
- volume: ml
- count: piece

Examples:
- 1 kg -> 1000 g
- 1 L -> 1000 ml
- dozen eggs -> purchase presentation of 12 pieces

The purchase presentation and the normalized quantity are both valuable and should remain distinguishable.

## Historical model
History is a first-class requirement, not an analytics afterthought.

Use append-only/effective-dated records or immutable snapshots for economically meaningful facts.

At minimum preserve:

### Ingredient purchase
- ingredient
- purchase date
- purchased normalized quantity
- total paid
- derived normalized unit cost

### Recipe economics
If ingredient composition or yield changes materially, retain the previous economic state through:
- recipe versioning, or
- immutable recipe/cost snapshots

### Product price
Price changes must create history rather than overwriting the only known price.

### Orders
Order lines must snapshot:
- product identity/display name
- quantity
- agreed sale price
- relevant cost basis or cost snapshot

This prevents future ingredient-price changes from rewriting historical profit.

## Cost-basis strategy
v1 default for "current cost" should use the **latest recorded purchase cost** for each ingredient unless explicitly changed by product requirements.

Design the domain so that weighted-average or another strategy can be added later without rewriting order history.

## Additional costs
Represent non-ingredient cost components with an allocation type:
- batch
- unit
- order/delivery

Keep the model generic enough for gas, electricity, packaging, fuel, labor/effort, and miscellaneous costs without creating a database table per cost category.

## Analytics
Analytics should derive from operational facts rather than a parallel manually-maintained reporting store.

Initial time-series inputs:
- ingredient purchases
- recipe/cost snapshots
- product price history
- completed/active orders

Precomputed aggregates may be added only when actual performance requires them.

## Authentication
v1 is expected to have a very small user population.

Requirements:
- public deployment must not expose business data anonymously
- auth provider must remain outside core domain logic
- no complex RBAC in initial v1 unless separately required

## Images
Recipe/product images should use an abstraction compatible with object storage. Do not store large binary images directly in relational tables.

## Testing strategy
Mandatory layers:
- unit tests for money, unit conversion, costing, margin/markup, yield and order totals
- integration tests for persistence boundaries
- Playwright tests for a very small set of critical mobile flows

Critical mobile flows eventually include:
1. update ingredient purchase price
2. create/edit recipe and yield
3. inspect product cost and pricing scenarios
4. create an order
5. inspect today's production/payment summary

## Non-goals
Do not introduce:
- microservices
- event buses
- CQRS infrastructure
- distributed caches
- native apps
- speculative abstraction layers

until measured need exists.
