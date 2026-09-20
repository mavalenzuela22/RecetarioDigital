# TSK-011 — PWA Completion & Installability — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

Read first:
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-011-PWA-COMPLETION-INSTALLABILITY.md`
3. `docs/ARCHITECTURE-v1.md`
4. `docs/design/implementation-guide/manifest.example.json`
5. `docs/design/implementation-guide/IMPLEMENTATION.md`

Expected branch:
`tsk-011-pwa-completion-installability`

Expected baseline:
`c0d2735819b34217aa4cf39db682dcf54bbac319`

Implement exactly the governed task.

Important:
- copy approved design icon binaries byte-for-byte; do not modify docs/design;
- no dependencies or migrations;
- service worker caches only manifest/favicon/icons/build;
- never cache navigation, login, Inertia/business responses or private recipe images;
- do not add offline mutations/sync;
- run full validation matrix;
- no blind retries.
