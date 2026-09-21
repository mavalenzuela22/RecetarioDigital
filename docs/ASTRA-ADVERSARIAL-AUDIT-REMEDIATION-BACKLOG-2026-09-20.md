# Astra Adversarial Audit — Remediation Backlog

Date: 2026-09-20
Product: EmprendimientoOS v1.0 / RecetarioDigital
Source audit: independent adversarial product / UX / observable-security audit executed with Playwright on 320 px and 390 px mobile viewports, SQLite and sampled Percona 8.4 flows.
Status: remediation planning authority; no finding is considered fixed merely because it is listed here.

## Purpose

Persist the complete actionable outcome of the Astra adversarial audit so remediation does not depend on chat history or model memory.

This document is deliberately organized into a small number of TSKs. The individual findings remain independently reproducible and must not be collapsed into vague "UX cleanup" work.

The next available task number was observed as TSK-017 at the time this backlog was created. Reconfirm task numbering through the governed repository authority at activation time.

## Audit result snapshot

Confirmed by the audit:

- 0 S0
- 5 S1
- 11 S2
- 2 S3
- 3 OBS
- plus AUD-19, added by product review after inspecting audit screenshots: cross-cutting excessive monetary decimal precision;
- plus AUD-20, added by product review after re-observing the formally accepted Astra design package: cross-cutting accepted-design conformance failure.

Google OAuth end-to-end remains UNVERIFIED because OAuth credentials are not configured in the local audit environment. This is not a defect finding by itself.

The audit demonstrated a complete economic path using real browser interaction, including purchases, recipe costing, pricing, orders, production, delivery, collections, history, access administration, mobile behavior, and sampled Percona behavior.

## Governing remediation principles

1. Every confirmed defect that is corrected must first gain a regression test reproducing the observed failure.
2. Preserve internal numeric precision. Fix display semantics, not authoritative arithmetic.
3. Do not silently change product decisions recorded as OBS findings.
4. Prefer observable user behavior over merely making existing tests green.
5. Validate on 320 px and 390 px for every affected mobile surface.
6. Where concurrency is the defect, validation must use genuine concurrent requests against Percona/MySQL-compatible storage; sequential tests are insufficient.
7. No corrective may weaken authentication, authorization, historical snapshots, append-only economic facts, idempotency, or exact/scaled-integer financial rules.
8. UI-affecting remediation must preserve conformance with the formally accepted `docs/design/**` authority. Functional Playwright/Pest PASS alone is not sufficient evidence of visual/design acceptance for affected surfaces.

---

# Proposed remediation structure — only 3 TSKs

## TSK-017 — Pilot Blocker Correctness and Economic Trust

Goal: remove all confirmed S1 blockers plus the cross-cutting monetary-display defect before an autonomous pilot.

Use bounded MTs inside this one TSK rather than creating one TSK per finding.

### MT-001 — AUD-01: concurrent administrator invariant

Severity: S1

Problem:
Two active administrators can concurrently deactivate each other under Percona, leaving zero active administrators.

Required outcome:
The invariant "at least one active administrator remains" must hold atomically under concurrent requests.

Regression requirement:
A real concurrent Percona test with two authenticated admin sessions must prove that no legal interleaving can commit zero active administrators.

Do not merely add another sequential count assertion.

### MT-002 — AUD-02: Production selected-range/action consistency

Severity: S1

Problem:
After changing the displayed production date range, "Iniciar preparación" can submit the previous range and mutate orders from the wrong day.

Required outcome:
The range shown to the user and the range acted on must be the same source of truth.

Regression requirement:
Load day A, change to day B, verify day-B orders are displayed, start production, and prove only day-B eligible orders transition.

### MT-003 — AUD-03: stale inactive order line remains economically active while visually disappearing

Severity: S1

Problem:
If a product in an open order draft becomes inactive elsewhere, the line can disappear visually while remaining in form data and totals, leaving visible lines and total inconsistent and blocking recovery.

Required outcome:
Every amount included in an order draft total must have an explicit visible line/state. A newly invalid line must remain visible with a human-readable resolution path or be removed consistently from both form data and total.

Regression requirement:
Two-tab scenario: draft mixed order, deactivate one product, submit/revalidate, verify the UI remains internally explainable and recoverable.

### MT-004 — AUD-04: first-use navigation to recipe/product catalog

Severity: S1

Problem:
A new user can register purchases but cannot discover the route to create recipes/products through normal navigation. Home can offer "Tomar pedido" even when no product exists.

Required outcome:
The first-use path purchase -> recipe -> product -> price -> order must be discoverable without typing known URLs.

Regression requirement:
Start with an empty business database and complete the first catalog setup using only visible navigation.

### MT-005 — AUD-05: manual price confirmation displays a different amount

Severity: S1

Problem:
Manual price input such as 5 or 15 can display $0.05 / $0.15 in confirmation while the saved price is $5.00 / $15.00; decimal input such as 4.50 can render malformed text.

Required outcome:
The confirmation must display exactly the amount that will be persisted and used for future orders.

Regression requirement:
Cover integer and decimal manual prices and assert both confirmation text and final persisted/displayed price.

### MT-006 — AUD-19: global monetary display precision

Severity: S2, cross-cutting

Observed after reviewing Astra screenshots.

Problem:
User-facing money leaks backend/database precision, including examples such as:

- $28.700000 MXN
- $32.800000 MXN
- $10.000000 MXN
- $1.000000 MXN por pieza

This is not an arithmetic defect. It is a presentation defect.

Required outcome:
Adopt a single global user-facing money formatting policy.

Default policy:
- ordinary currency amounts, totals, prices, collections, balances, revenue, cost and profit: exactly 2 decimals;
- percentages: at most 1–2 meaningful decimals;
- if a specialized unit-cost surface genuinely requires sub-cent precision, use an explicitly approved formatter with bounded precision and trim meaningless trailing zeros;
- never reduce backend/domain precision merely to make the UI prettier.

Implementation constraint:
Do not scatter ad-hoc toFixed(2) calls throughout pages. Establish/reuse a central formatter or design-system primitive and migrate affected surfaces.

Regression requirement:
Scan/cover primary economic surfaces: Today, Production, recipe cost, product cost/pricing, order capture/detail, history and access to monetary values. Assert no ordinary MXN display leaks six-decimal storage precision.

### TSK-017 exit criteria

- AUD-01 through AUD-05 reproducibly fixed.
- AUD-19 global formatting rule applied.
- Regression coverage added for every MT.
- Full SQLite suite PASS.
- Full Percona gate PASS, including concurrent admin test.
- 320/390 Playwright PASS for affected flows.
- Existing exact arithmetic and historical snapshot behavior unchanged.
- Independent semantic review before promotion.

---

## TSK-018 — User Recovery, Mobile Reliability and Workflow Clarity

Goal: address confirmed S2 operational/UX defects that make the application unpredictable or difficult to recover from during real use.

The work should be split into bounded MTs by surface, not one corrective per screenshot.

### Production workflow MT

Covers:

- AUD-06 S2 — "Marcar listo" shown inside one product group actually completes the whole mixed order without making scope clear.
- AUD-13 S2 — invalid/reversed production range retains old data without showing the validation failure.

Required direction:
Make action scope explicit and make rejected filters visibly rejected. Do not invent line-level fulfillment if the domain remains order-level.

### Access/auth recovery MT

Covers:

- AUD-07 S2 — create invitation then refresh leads to 405 because successful POST leaves browser at a POST-only URL.
- AUD-09 S2 — browser Back after logout can restore previously rendered private commercial data until refresh.
- AUD-10 S2 — recovery password errors expose translation keys such as validation.min.string / validation.confirmed and poor focus/recovery behavior.
- AUD-15 S2 — normal invitation email can overflow the 320 px viewport and partially hide Revocar.

Required direction:
Use navigable post-success URLs, human Spanish validation, sensible focus, mobile-safe wrapping/layout, and appropriate private-page history/cache behavior without weakening logout/session invalidation.

### Order reliability MT

Covers:

- AUD-08 S2 — visible "Volver" path discards dirty order draft without warning.
- AUD-12 S2 — failed/offline payment submission provides no visible confirmation failure/retry guidance.

Required direction:
Protect unsaved work across the actual SPA navigation controls and make collection attempts explicitly confirmed or explicitly unconfirmed. Do not claim offline business capability.

### Recipe recovery/clarity MT

Covers:

- AUD-11 S2 — stale concurrent recipe edit becomes a generic English 409/"Something is broken" instead of a recoverable conflict.
- AUD-14 S2 — recipe cost card confuses not-yet-calculated/incomplete/saved-version cost and can display stale saved cost while editing a different yield.

Required direction:
Preserve immutable/versioned recipe semantics while translating concurrency conflicts into user-recoverable UI. Clearly distinguish saved cost from draft/not-yet-calculated/incomplete states.

### Today completion-state MT

Covers:

- AUD-16 S2 — after the day's only order is completed, Home says "No tienes pedidos para hoy" while still showing non-zero sales/cost/profit.

Required direction:
Differentiate "no orders existed" from "today's work is complete".

### TSK-018 exit criteria

- AUD-06 through AUD-16 resolved except findings explicitly deferred by human decision.
- User-visible errors are natural es-MX and recoverable.
- 320/390 mobile checks include populated real-world data, not only empty fixtures.
- Browser sequencing is tested: save->refresh, back, offline/retry, concurrent edit, filter->action.
- Full regression suites remain green.

---

## TSK-019 — Pilot Polish and Explicit Product Decisions

Goal: close low-severity polish and deliberately decide the audit observations without silently changing product semantics.

### Confirmed defects

- AUD-17 S3 — recipe detail leaks "piece", "Snapshot", "inmutable" technical/mixed-language terminology.
- AUD-18 S3 — Logout touch target measured approximately 29 x 20 px on mobile.

Required direction:
Natural es-MX language and practical mobile touch targets consistent with the product UX authority.

### Product decision records — DO NOT AUTO-FIX

OBS-01 — Per-order cost allocation uses configured reference quantity rather than actual order size.
Current behavior matches TSK-005. Human product decision required.

OBS-02 — history comparison is end-of-day/effective-date oriented and does not show intraday changes when comparing the same date.
Current behavior matches TSK-009. Human product decision required.

OBS-03 — yesterday's unpaid balance is not shown in "Por cobrar hoy".
Current behavior matches TSK-008 and its explicit scope. Human product decision required.

For each OBS, record one of:
- keep current v1 behavior;
- clarify UX/copy only;
- schedule a later product enhancement.

Do not reinterpret an OBS as a defect without explicit human approval.

### TSK-019 exit criteria

- S3 findings dispositioned.
- OBS-01/02/03 each have an explicit product decision recorded in repository authority.
- No accidental scope expansion into accounting, intraday event history, or global receivables unless separately authorized.

---

## TSK-020 — Accepted Design Conformance

Goal: make the implemented product visually and interactionally conform to the formally accepted Astra design authority, **A — Cocina cálida artesanal**, without changing domain semantics merely to imitate a mockup.

### AUD-20 — accepted design conformance failure

Classification: pilot acceptance blocker / cross-cutting UX defect.

Observed by product review after the adversarial audit.

Authority:
- `docs/design/README.md` records formal acceptance by the primary user / product owner ("la patrona") of **A — Cocina cálida artesanal**.
- `docs/design/CLOSURE.md` records the same accepted direction and a complete implementation-ready handoff.
- `docs/design/implementation-guide/IMPLEMENTATION.md`, `HANDOFF-CHECKLIST.md`, component specifications, tokens, flows and rendered references are the accepted implementation authority.
- Earlier implementation TSKs explicitly cited `docs/design/**` as design authority.

Problem:
The current application inherits some low-level visual foundations from the accepted system (including core tokens, colors, typography families and radii), but the implemented screens do not consistently reproduce the accepted composition, hierarchy, navigation, component treatment, imagery, spacing and overall look-and-feel of the Astra reference. The result is recognizably related to the accepted design but materially less faithful than the experience shown to and accepted by the product owner.

This is not cosmetic preference and must not be reduced to generic "polish". The accepted design was part of the product expectation presented before implementation.

Required outcome:
- preserve domain behavior and corrected UX semantics;
- systematically compare each implemented core journey against the applicable `docs/design/**` references;
- migrate shared layout/navigation/component primitives first so fixes are systemic rather than page-specific CSS patches;
- restore accepted visual hierarchy, spacing, component shapes/states, navigation structure, typography use, imagery where authorized, empty/error/confirmation treatment and mobile composition;
- retain responsive correctness at 320/390 and larger documented breakpoints;
- do not copy prototype business logic or fictitious fixture behavior into production;
- when current product semantics legitimately differ from an old mockup, preserve product truth and adapt the accepted visual language around it rather than falsifying behavior.

Mandatory visual evidence:
For each core flow (Hoy, compra, receta, producto/precio, pedido, cobro/entrega, producción, histórico), capture current implementation and compare it side-by-side with the accepted reference. Record deviations and disposition each as:
- conform;
- implementation defect to fix;
- intentional semantic divergence with documented rationale.

Validation:
- functional regression remains green;
- 320 px and 390 px browser evidence for affected flows;
- visual comparison against the accepted rendered reference, not only token inspection;
- shared component/design-system conformance review;
- final product-owner visual acceptance before autonomous pilot.

### TSK-020 exit criteria

- AUD-20 resolved across all eight accepted core journeys.
- No page is considered conformant merely because it uses the correct palette/fonts.
- Shared primitives and navigation match the accepted design language.
- Any intentional visual/semantic departures are explicitly documented.
- Final visual evidence is inspectable from the repository.
- Product-owner acceptance is recorded separately from mechanical validation.

---

# Original Astra findings inventory

This section exists so no finding disappears when work is split.

| ID | Severity | Short title | Assigned TSK |
|---|---|---|---|
| AUD-01 | S1 | concurrent admins can leave zero active admins | TSK-017 |
| AUD-02 | S1 | production acts on previous date range | TSK-017 |
| AUD-03 | S1 | inactive order line disappears but remains in total | TSK-017 |
| AUD-04 | S1 | first-use navigation cannot reach recipe/product setup naturally | TSK-017 |
| AUD-05 | S1 | manual price confirmation displays wrong amount | TSK-017 |
| AUD-06 | S2 | mark-ready scope ambiguous for mixed orders | TSK-018 |
| AUD-07 | S2 | invitation create + refresh yields 405 | TSK-018 |
| AUD-08 | S2 | Back discards order draft without warning | TSK-018 |
| AUD-09 | S2 | browser Back after logout restores private rendered data | TSK-018 |
| AUD-10 | S2 | recovery errors expose technical translation keys / poor focus | TSK-018 |
| AUD-11 | S2 | stale recipe edit appears as generic English 409 | TSK-018 |
| AUD-12 | S2 | failed/offline payment has no visible failure guidance | TSK-018 |
| AUD-13 | S2 | invalid production range keeps old data silently | TSK-018 |
| AUD-14 | S2 | recipe draft cost card is misleading/stale | TSK-018 |
| AUD-15 | S2 | invitation content overflows 320 px | TSK-018 |
| AUD-16 | S2 | completed day says no orders despite economic totals | TSK-018 |
| AUD-17 | S3 | recipe detail exposes technical/mixed-language terminology | TSK-019 |
| AUD-18 | S3 | logout touch target too small | TSK-019 |
| AUD-19 | S2 | six-decimal monetary presentation leaks internal precision | TSK-017 |
| AUD-20 | Pilot acceptance blocker | implemented UI materially diverges from accepted Astra design authority | TSK-020 |
| OBS-01 | OBS | reference-quantity order-cost allocation | TSK-019 decision |
| OBS-02 | OBS | end-of-day history granularity | TSK-019 decision |
| OBS-03 | OBS | old receivables excluded from Today | TSK-019 decision |

Total persisted inventory: 20 confirmed defect findings plus 3 product observations.

---

# Evidence location from Astra audit

Astra reported a durable audit-output tree under its Work environment with screenshots and JSON evidence, including evidence IDs E001–E136 and repository-integrity verification.

Reported source path at audit time:

/Users/martin.valenzuela/.codex/.chatgpt-projects/g-p-6aad67cddaf0819194332ff95db7cb42/audit-output/2026-09-20-adversarial/

Do not assume this external path is permanent. At the beginning of remediation, verify availability and copy only the necessary evidence references into governed remediation artifacts if required.

Important named evidence includes:
- E116/E117/E118/E119 — concurrent zero-admin reproduction
- E097/E098/E099/E100 — wrong Production date action
- E094/E095/E096 — hidden inactive order line / inconsistent total
- E004/E005/E006/E011/E013/E017 — first-use navigation dead end
- E021/E023/E092 — incorrect manual price confirmation
- E065/E066 — recovery translation errors
- E088/E88b — stale recipe 409
- E129/E130 — access overflow / invitation refresh 405
- E133/E134/E135 — dirty order back-navigation data loss
- E136 — repository integrity verification

AUD-19 is additionally supported by screenshots manually reviewed after the audit showing six-decimal user-facing MXN values on Production economy and recipe cost surfaces.

---

# Recommended execution order

1. Resolve/complete TSK-016 promotion mechanics if still pending; do not lose this remediation backlog.
2. Activate TSK-017 and finish all pilot blockers plus AUD-19.
3. Re-run a focused independent adversarial regression against the corrected flows.
4. Activate TSK-018 and resolve recovery/mobile reliability defects.
5. Run another product-level regression, emphasizing mobile sequencing/recovery.
6. Activate TSK-020 and perform accepted-design conformance across the eight core journeys, using repository-visible side-by-side evidence against `docs/design/**`.
7. Run a focused visual/product regression and obtain explicit product-owner visual acceptance.
8. Activate TSK-019 and record low-severity/product decisions without allowing them to dilute AUD-20.
9. Configure Google OAuth test credentials and execute the previously UNVERIFIED real OAuth/linking/bootstrap acceptance boundary.
10. Only then consider an autonomous pilot with the intended real user.
