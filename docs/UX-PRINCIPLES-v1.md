# EmprendimientoOS v1.0 — UX Principles

## North star
The product is operated primarily from a phone, often while the user is cooking, packaging, shopping, or coordinating deliveries.

The interface must feel easier than writing the same information in a notebook.

## Mobile-first rules
- Design for a phone viewport first.
- Desktop is an expansion of the mobile experience, not the source layout.
- No workflow may depend on hover.
- Avoid horizontal data tables for everyday mobile tasks.
- Use comfortably tappable controls and clear spacing.
- Keep primary actions reachable with one hand where practical.
- Common actions should require the minimum reasonable number of decisions.

## Capture human facts; calculate the rest
The user should enter facts such as:
- "I bought 1 kg of flour for $42"
- "This recipe uses 320 g"
- "This batch made 9 pieces"
- "Fulanita ordered 3 strawberry and 2 guava"

The system should calculate:
- normalized unit cost
- recipe cost
- cost per piece
- price scenarios
- profit
- margin
- production totals
- balances due

Do not ask the user to perform accounting math before using the app.

## Language
Use plain, familiar language.

Prefer:
- Cost per piece
- You earn
- Pending to collect
- You need to prepare

Expose terms such as margin and markup with short contextual explanations rather than assuming accounting knowledge.

## Progressive disclosure
Show the answer first and the accounting detail second.

Example product cost card:
- Cost per piece
- Current sale price
- Profit per piece
- Margin

Then allow the user to open a breakdown.

## Speed expectations
High-frequency workflows should feel nearly instantaneous:
- record a new ingredient purchase
- adjust recipe yield
- inspect cost impact
- create an order
- mark paid
- mark delivered

Avoid long multi-page wizards when a compact staged form or bottom sheet is clearer.

## Forms
- Use sensible defaults.
- Keep units next to quantities.
- Use numeric keyboards for numeric inputs.
- Preserve unfinished work where practical.
- Validate near the field that needs attention.
- Never erase valid data because another field failed validation.

## Orders
Order capture should behave more like taking an order than filling an enterprise form.

Target interaction:
1. choose/create customer
2. tap products
3. adjust quantities with + / -
4. choose delivery date/time
5. capture payment/notes if needed
6. save

## Today screen
The home screen is an action surface, not a reporting dashboard.

Prioritize:
- what must be prepared
- what must be delivered
- who still owes money
- today's expected money

Historical charts belong below or in dedicated analytics views.

## Visual design
The product should feel:
- warm
- trustworthy
- modern
- calm
- handcrafted rather than corporate

Avoid:
- "enterprise admin panel" aesthetics
- dense grids
- excessive borders
- decoration that competes with operational information

## UX review policy
Before locking major v1 flows, perform a high-effort UX review focused on:
- unnecessary taps
- information hierarchy
- clarity without training
- one-handed mobile use
- empty/error/loading states
- accessibility
- emotional polish

Visual polish is important, but usability wins any conflict.

## Usability acceptance rule
For a primary workflow:

> If the intended user needs a verbal explanation to know what to tap next, treat that as a design defect first.

## v1 core journeys
The first flows to design and validate are:
1. record an ingredient purchase
2. create a recipe with yield
3. see product cost and price scenarios
4. create an order
5. see today's production and collections
6. compare today's product economics with a prior date
