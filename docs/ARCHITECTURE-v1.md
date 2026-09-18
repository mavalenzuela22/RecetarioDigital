# EmprendimientoOS v1.0 — Architecture Baseline

## Architectural intent
Build a small, maintainable, mobile-first PWA that can grow without turning v1 into an ERP.

The architecture optimizes for:
- fast mobile interaction
- correctness of money and quantity calculations
- historical reproducibility
- simple deployment to the existing GoDaddy Windows Hosting environment
- low operational overhead
- clear domain boundaries

## Deployment target
The initial production target is the existing GoDaddy Windows Hosting / IIS environment observed by the operator.

Observed production capabilities:
- Microsoft IIS 10
- PHP 8.3
- MySQL-compatible Percona Server 8.4
- phpMyAdmin
- no assumption of a persistent Node.js application runtime

Node.js may be used locally or in CI for frontend build tooling and tests, but production must not require a long-running Node.js server.

TLS certificate installation/renewal is an operator-owned hosting concern and is outside application runtime logic.

## Initial stack
The implementation target is:

- **Laravel** as the server-side application framework
- **PHP 8.3 compatible code**
- **Inertia.js**
- **React**
- **TypeScript**
- **Tailwind CSS**
- accessible headless UI primitives where useful
- **MySQL-compatible Percona Server 8.4** as the production database target
- **Eloquent ORM** and Laravel migrations
- **Pest** for domain/backend tests
- **Playwright** for critical mobile end-to-end flows
- installable **PWA** shell with web app manifest and bounded service-worker strategy

Do not introduce Prisma, PostgreSQL, or a Next.js runtime in v1.

## Application shape
Prefer one deployable Laravel application for v1.

Suggested internal domain boundaries:
- `ingredients`
- `recipes`
- `products`
- `costing`
- `orders`
- `production`
- `analytics`
- `shared`

Business rules belong in domain/service classes and tested application logic, not in React components or controllers.

Inertia pages are the application delivery mechanism; avoid duplicating the application as a separate REST backend plus independent SPA unless a later requirement justifies it.

## Frontend delivery
React/TypeScript/Tailwind assets are built ahead of deployment and served by Laravel/IIS.

Production must not require:
- `npm run dev`
- a persistent Vite process
- a persistent Node.js application server

## Money
Persist authoritative monetary values using database decimal values with explicit scale or integer minor units where appropriate.

Rules:
- never use JavaScript binary floating point as the authoritative persisted money representation
- server-side cost calculations are authoritative
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

The human purchase presentation and normalized quantity remain distinguishable.

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
If ingredient composition or yield changes materially, retain the previous economic state through recipe versioning or immutable recipe/cost snapshots.

### Product price
Price changes create history rather than overwriting the only known price.

### Orders
Order lines snapshot:
- product identity/display name
- quantity
- agreed sale price
- relevant cost basis or cost snapshot

Future ingredient-price changes must never rewrite historical order profit.

## Cost-basis strategy
v1 default for current ingredient cost is the **latest recorded purchase cost** unless product requirements explicitly change that rule.

Keep the domain boundary capable of supporting a future weighted-average strategy without rewriting order history.

## Additional costs
Represent non-ingredient cost components with an allocation type:
- batch
- unit
- order/delivery

Keep the model generic enough for gas, electricity, packaging, fuel, labor/effort, and miscellaneous costs without creating a table per cost category.

## Analytics
Analytics derive from operational facts rather than a parallel manually-maintained reporting store.

Initial time-series inputs:
- ingredient purchases
- recipe/cost snapshots
- product price history
- completed/active orders

Precomputed aggregates may be added only when measured performance requires them.

## Authentication
v1 is expected to have a very small user population.

Requirements:
- public deployment must not expose business data anonymously
- authentication infrastructure stays outside core domain logic
- no complex RBAC in initial v1 unless separately required

## Images
Recipe/product images should use filesystem/object-storage abstractions exposed through Laravel storage.

Do not store large binary images directly in relational tables.

The initial implementation may use local/public application storage while keeping the domain independent of a specific cloud storage vendor.

## Testing strategy
Mandatory layers:
- Pest tests for money, unit conversion, costing, margin/markup, yield, order totals, and persistence behavior
- focused frontend tests only where component logic materially benefits from them
- Playwright tests for a very small set of critical mobile flows

Critical mobile flows eventually include:
1. record an ingredient purchase
2. create/edit recipe and yield
3. inspect product cost and pricing scenarios
4. create an order
5. inspect today's production/payment summary

## PWA
v1 is a responsive web application designed to be installable from a phone home screen.

The PWA requirement includes:
- manifest
- app metadata
- suitable mobile icons when branding is available
- installable standalone presentation where browser/platform permits

Complex offline synchronization is explicitly deferred.

## Non-goals
Do not introduce:
- microservices
- event buses
- CQRS infrastructure
- distributed caches
- native apps
- persistent Node.js production services
- speculative abstraction layers

until measured need exists.
