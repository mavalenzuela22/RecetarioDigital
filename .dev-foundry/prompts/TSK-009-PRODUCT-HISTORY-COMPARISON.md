# TSK-009 — Product History Comparison — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

Read first:
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-009-PRODUCT-HISTORY-COMPARISON.md`
3. `docs/PRODUCT-DEFINITION-v1.md`
4. `docs/ARCHITECTURE-v1.md`
5. `docs/UX-PRINCIPLES-v1.md`
6. accepted `historial` contract in `docs/design/screens/flows.json`
7. `docs/design/implementation-guide/IMPLEMENTATION.md`

Expected branch:
`tsk-009-product-history-comparison`

Expected baseline:
`ed5a409babe76f1fe89c22fc14dce4c97a14e657`

Implement exactly the smallest safe complete TSK-009:
- product History page and route;
- exact historical product-economics service;
- local-date as-of selection;
- ingredient drivers only when economically comparable;
- provenance;
- product-detail history link;
- Pest + focused mobile Playwright.

Constraints:
- no design docs changes;
- no migrations/dependencies unless truthful history is proven impossible otherwise;
- no binary floats;
- do not recalculate historical orders;
- domain calculations live in service code, not controller/React;
- reuse ProductCosting exact helpers where safe, but do not alter current pricing semantics;
- <=30 total visible paths.

Failure policy:
- run full matrix;
- preserve evidence on failure;
- no blind retry;
- stop for classification before any corrective.
