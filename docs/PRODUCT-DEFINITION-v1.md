# EmprendimientoOS v1.0 — Product Definition

## Identity
- Repository/project name: **RecetarioDigital**
- Product name: **EmprendimientoOS**
- Product stage: **v1.0 greenfield**
- Primary device: **mobile phone**
- Delivery model: **mobile-first responsive web application / PWA**
- Primary user: a small food-business owner who needs to run day-to-day operations without spreadsheets or notebooks.

## Core promise
EmprendimientoOS must answer four practical questions with minimal effort:

1. **How much does this product really cost me to make?**
2. **How much should I sell it for and how much do I earn?**
3. **What do I need to prepare, deliver, and collect today?**
4. **How have my costs, prices, and margins changed over time?**

The application must preserve enough historical data to answer questions such as:
- How much did this product cost me a year ago?
- What did I sell it for then?
- Which ingredient caused the increase?
- What would happen to my margin if I keep the same sale price?
- How much did I actually earn on completed orders?

## v1 scope

### Ingredients / raw materials
Capture:
- name
- purchase presentation
- purchase quantity
- unit of measure
- total purchase price
- purchase date
- optional supplier/store and notes

The system derives normalized unit cost and retains **append-only purchase/price history**.

### Recipes
Capture:
- name and optional image
- ingredients and quantities
- preparation instructions
- expected yield
- preparation notes

Recipe changes that affect costing must be historically reproducible through recipe versioning or equivalent immutable cost snapshots.

### Products
A sellable product references a recipe and defines:
- sale unit
- current sale price
- optional additional costs
- configurable pricing scenarios
- active/inactive state

Additional costs may include:
- electricity
- gas
- water
- packaging
- labels
- delivery/fuel allocation
- effort/labor
- other configurable costs

Supported allocation semantics in v1:
- per batch
- per unit
- per order/delivery

### Costing
Core formulas:

Ingredient usage cost:
`normalized ingredient unit cost × recipe quantity used`

Recipe batch cost:
`sum(ingredient usage costs) + batch-level additional costs`

Unit production cost:
`total batch cost / actual or expected yield`

Unit profit:
`sale price - unit production cost - unit-level allocated costs`

Batch/order profit:
`revenue - attributable cost`

The application must clearly distinguish:
- **markup multiplier** (for example x3)
- **profit margin on sale price**

Money calculations must never rely on binary floating-point approximations for persisted financial values.

### Pricing scenarios
Allow configurable scenarios such as:
- x2
- x2.5
- x3
- x3.5

For each scenario show:
- suggested sale price
- unit profit
- expected batch profit
- margin percentage

### Orders
Capture:
- customer
- requested delivery date/time
- products and quantities
- price snapshot per line
- notes
- fulfillment state
- payment state
- amount paid / balance due where applicable

Initial fulfillment states:
- New
- Confirmed
- In preparation
- Ready
- Delivered
- Cancelled

Initial payment states:
- Pending
- Partial
- Paid

### Production view
Aggregate active orders for a selected date/window and answer:
- how many units of each product must be produced
- what orders those units belong to
- estimated revenue
- estimated cost
- estimated profit

### Today / Home
The opening screen should prioritize actionable information:
- orders due today
- pieces to prepare
- deliveries pending
- outstanding balances
- estimated revenue and profit for the day

### Historical analytics
The data model must preserve:
- ingredient purchase/cost history
- recipe/cost history
- product sale-price history
- order line price snapshots
- order/profit history

The app should support an "as of" view that can reconstruct or faithfully report:
- production cost
- sale price
- margin
- major cost drivers

for a prior date.

## Deliberately out of scope for v1
Do **not** add these unless separately authorized:
- full inventory management
- procurement workflows
- supplier management beyond optional purchase metadata
- accounting or tax filing
- invoicing/fiscal integration
- route optimization
- marketing automation
- loyalty programs
- advanced CRM
- AI-generated recipes
- complex multi-user permissions
- native iOS/Android applications

## Product success criterion
The primary v1 success test is behavioral:

> After one week of real use, the user prefers opening EmprendimientoOS over using a notebook, loose paper, or spreadsheet.

## Product rule
Every feature must justify itself against the primary workflow. If it does not reduce effort, clarify money, simplify production, or prevent forgotten work, it does not belong in v1.
