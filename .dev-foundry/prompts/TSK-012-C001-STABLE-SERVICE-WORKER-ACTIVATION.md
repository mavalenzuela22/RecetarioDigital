# TSK-012 C001 — Stable Service Worker Activation Assertion

Operate as a bounded corrective for TSK-012.

## Failure classification

The TSK-012 implementation itself completed successfully:
- release builder PASS;
- release ZIP produced;
- release verifier PASS;
- checksum PASS;
- backend Pest PASS;
- TypeScript PASS;
- Vite PASS;
- path policy PASS.

Only the inherited PWA Playwright assertion failed because `navigator.serviceWorker.ready` returned a registration whose active worker was still transitioning through `activating` when its state was sampled.

Observed failure:
- expected `activated`
- received `activating`

This is a browser-test synchronization race. It is not evidence of a release-packaging, service-worker, or application runtime defect.

## Required corrective

Modify only `e2e/pwa.spec.ts` as production/test code:
- retain `navigator.serviceWorker.ready` to obtain the registration;
- if the active worker is not yet `activated`, wait for that worker's `statechange` event until it reaches `activated`;
- resolve immediately if already activated;
- reject/fail clearly if no active worker exists or if it becomes redundant before activation;
- after the bounded wait, return scriptURL, scope and final state;
- preserve all manifest, icon, login, business-route and Cache Storage assertions unchanged.

Do not modify:
- public/sw.js;
- manifest/icons;
- application/runtime/domain/auth code;
- release builder/verifier/smoke scripts unless a new validation failure specifically demonstrates a defect there;
- dependencies or migrations.

## Validation

Rerun the complete TSK-012 matrix, including:
- backend tests;
- TypeScript/Vite;
- full mobile Playwright;
- build release ZIP;
- verify release ZIP;
- shell syntax;
- git diff check.

Do not promote. Stop on any remaining failure and preserve evidence.
