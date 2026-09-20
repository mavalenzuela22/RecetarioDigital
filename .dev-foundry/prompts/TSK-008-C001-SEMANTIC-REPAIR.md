# TSK-008 C001 — Contract Reconciliation, Semantic Repair & Deterministic Validation

Operate as a bounded corrective for TSK-008 after classified initial failure.

## Classification

### CONTRACT / PATH
The initial executor produced three outside-allowlist paths:
- app/Http/Requests/TransitionOrderFulfillmentRequest.php
- app/Services/OperationalSummary.php
- resources/js/Pages/Production.tsx

Disposition:
- OperationalSummary is semantically accepted as the shared Today/Production read model and is allowed by this corrective.
- Production page should follow the established page-directory convention and the original intended path: move it to resources/js/Pages/Production/Index.tsx and render Inertia component Production/Index.
- TransitionOrderFulfillmentRequest must be restored to the TSK-007 request-key-only contract; production range navigation is not fulfillment-command payload.

### TEST DEFECTS
1. HomeTest now hits a DB-backed Home but lacks RefreshDatabase; runtime evidence confirms sqlite ":memory:" error "no such table: orders".
2. TodayProductionTest incorrectly tries to create in_preparation by calling TransitionOrderFulfillment with toState=in_preparation. TSK-008 defines confirmed->in_preparation as Start Production, not an individual fulfillment transition.
3. Playwright 320/390 cases share one business-day aggregate and ran concurrently. This caused one worker to transition the other worker's order, producing duplicate "En preparación" rows and removing the other worker's Start Production button. The TSK explicitly permits local serialization of these viewport cases. Keep global Playwright parallelism unchanged.
4. Scope ambiguous status assertions to the current unique order/customer where practical rather than relying on a globally unique label.

### PRODUCT / SEMANTIC DEFECTS
1. TransitionOrderFulfillment was changed from TSK-007 request-hash conflict checking to replay based only on order_id + to_state. Restore strict request-key/hash semantics and extend them correctly for ready:
   - new event hash describes actual order_id + from_state + to_state;
   - same request key replay for the same recorded transition succeeds;
   - same request key reused for another order or another target transition rejects;
   - do not weaken delivered/cancelled behavior.
2. Start Production mutation currently lives inside OperationalSummary. Keep OperationalSummary query/read-only. Create app/Services/StartProduction.php and move the atomic confirmed->in_preparation command there.
3. Production grouping by stable product_id is correct, but each constituent order fact must preserve its own order-line product_name snapshot. Include product_name on constituent order payloads and test it.

## Required changes

Allowed product changes are bounded to the TSK-008 implementation already present plus:
- app/Services/StartProduction.php
- resources/js/Pages/Production/Index.tsx

### OperationalSummary
- keep Today and Production aggregation/read model;
- remove startPreparation mutation and mutation-only imports;
- preserve exact arithmetic and current inclusion rules;
- constituent production order facts include the line snapshot product_name.

### StartProduction
Implement the existing governed command:
- inclusive normalized range supplied by controller;
- one DB transaction;
- deterministic ID ordering;
- lock confirmed candidates;
- re-check delivery date range and state;
- append immutable confirmed->in_preparation fulfillment event per actual transition;
- UUID event request key;
- request hash over actual order_id/from_state/to_state;
- update only fulfillment_state;
- return transition count;
- safe no-op when no confirmed candidates remain;
- preserve payment and economic facts.

### TransitionOrderFulfillment
Authorized target states remain delivered, cancelled, ready.
- ready only from in_preparation;
- delivered/cancelled retain TSK-007 permitted origins;
- immutable event hash must be validated on replay;
- request-key conflict must reject different order/target payload;
- same request key same actual recorded transition replays;
- do not authorize confirmed->in_preparation here.

### Requests/controllers
- restore TransitionOrderFulfillmentRequest to request_key only;
- ProductionController start uses StartProduction;
- Production ready may read from/to as navigation/range context and normalize it separately; they are not passed as validated fulfillment payload;
- render Production/Index.

### Tests
- HomeTest uses RefreshDatabase.
- TodayProductionTest prepares the ready-case order using StartProduction (or otherwise the governed start-production boundary), then exercises in_preparation->ready.
- Add/assert request-key conflict protection for ready without weakening prior TSK-007 semantics.
- Assert constituent order product_name snapshot is exposed.
- E2E Today/Production viewport cases run serially inside that describe only; global config unchanged.
- Scope "En preparación"/"Listo para entregar" assertions to the unique order row where reasonable.

## Do not
- add migrations/dependencies;
- modify docs/design;
- modify TSK-001..TSK-007 implementation tests;
- weaken existing order operations E2E;
- change money/state inclusion semantics;
- add features.

## Validation
Run the full TSK-008 matrix:
1 docker compose build app
2 composer install
3 npm ci
4 full php artisan test
5 npx tsc --noEmit
6 npm run build
7 known E2E container cleanup
8 Playwright home + order-operations + today-production mobile
9 git diff --check

If another failure remains, preserve evidence and stop for classification.
