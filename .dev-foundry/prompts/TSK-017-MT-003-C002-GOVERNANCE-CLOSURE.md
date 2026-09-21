# TSK-017 MT-003 C002 — Governance Closure Without Repeating Green Functional Matrix

## Authority and classification

MT-003 functional implementation and regression were already validated by:
- initial MT-003 execution: all validation commands PASS, path policy failed only due inherited validated MT-001 controller;
- C001 execution `execution_6cadfafe4ac4d18a4632ec48295607e0599a35e62c6ed3de21f1e501e66a9085`: all validation commands PASS again, path policy failed only because two future MT-004 governance files were created by the Governance Author after C001 had started.

C001 functional matrix is authoritative:
- TypeScript PASS
- Vite build PASS
- Playwright order-capture: 3/3 PASS including stale inactive-line recovery
- OrderCapture Pest PASS
- git diff check PASS

No MT-003 product/test source changed after C001 validation.

Current expected SHA-256 fingerprints:
- resources/js/Pages/Orders/Create.tsx = 507de81e54581cfc34422771aa14552e90ce0779bd911c3935ac87fffae957df
- resources/js/Components/OrderUI.tsx = 9d7e9b2b9ec80e6b3bb5291065ffc69dc4155407c5a19bbad7bf3e9b63f8cff4
- e2e/order-capture.spec.ts = 027d2a1d0d987450ef45cb4c236cfdb0f423ab793abf5478aaaf3263f43530fa

## Objective

Close the path-policy bookkeeping gap only.

Do not mutate product or test source.

Verify the three SHA fingerprints above exactly and fail if any differs.
Run git diff --check.
Path policy must PASS with the entire current inherited dirty boundary, including the already prepared MT-004 governance files, explicitly allowlisted.

This C002 does not replace C001 functional validation; it binds the unchanged current source to the already-green C001 evidence and establishes clean path-policy closure.
