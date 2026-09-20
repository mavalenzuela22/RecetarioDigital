# TSK-009 C001 — Restore Purchase Replay & Correct Local-Day Boundary Test

Operate as a bounded corrective for TSK-009 after the initial classified failure.

## Classification

### Legitimate contract reconciliation
The initial implementation used these paths outside the original allowlist:
- app/Services/ProductHistoryComparison.php
- resources/js/Components/ProductUI.tsx
- resources/css/app.css

These are accepted implementation choices:
- ProductHistoryComparison is the bounded historical domain service and replaces the originally anticipated name HistoricalProductEconomics.
- ProductUI contains shared history payload/types and exact money rendering already reused by the new History page.
- app.css contains only bounded history-screen responsive classes using the existing design tokens.

Do not rename/refactor these merely to satisfy the old path names.

### Product regression
The implementation changed app/Models/IngredientPurchase.php to cast purchased_on as a Carbon date solely so ProductHistoryComparison could call ->format().

That global cast breaks the pre-existing TSK-003 idempotent replay comparison because RecordIngredientPurchase compares persisted purchase facts to normalized string payloads. Identical replay now conflicts.

Required repair:
1. Restore IngredientPurchase::casts() exactly to the prior TSK-003 semantics: only the exact numeric persisted fields are string-cast; do not add a purchased_on date cast.
2. In ProductHistoryComparison, treat purchased_on locally as its persisted Y-m-d value for payload/provenance. Do not require or mutate a global model cast.
3. Preserve deterministic historical purchase selection by purchased_on DESC, id DESC.

### Test defect
The ProductHistory test named "uses a timestamp at the local business-day close..." currently moves recipe/profile/price timestamps to 2026-09-18 23:59:59 and expects them to be unavailable as of 2026-09-18.

That contradicts the governed rule:
timestamped facts at or before the end of the selected local calendar day are included.

Correct the test boundary rather than the product:
- a fact exactly at 2026-09-18 23:59:59 local MUST be included on 2026-09-18;
- add/use a just-after-boundary case (2026-09-19 00:00:00 local) to prove it is excluded from 2026-09-18 and included on 2026-09-19.
Keep the test deterministic and explicit about America/Monterrey business time.

## Preserve semantics
Do not change:
- historical formulas;
- ingredient purchased_on as effective date;
- recipe/profile/price as-of selection;
- driver comparability;
- profile-change disclosure;
- historical-order non-recalculation;
- ProductPricing behavior;
- design authority.

## Validation
Run full TSK-009 matrix:
1 docker compose build app
2 composer install
3 npm ci
4 full php artisan test
5 npx tsc --noEmit
6 npm run build
7 known E2E container cleanup
8 product-pricing + product-history Playwright mobile
9 git diff --check

If another failure remains, preserve evidence and stop for classification.
