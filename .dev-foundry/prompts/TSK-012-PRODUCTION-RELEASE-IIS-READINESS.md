# TSK-012 — Production Release & IIS Deployment Readiness — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

Read:
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-012-PRODUCTION-RELEASE-IIS-READINESS.md`
3. `docs/ARCHITECTURE-v1.md`
4. `README.md`
5. current `public/web.config`, filesystem/session/cache/database/logging configuration.

Expected branch:
`tsk-012-production-release-iis-readiness`

Expected baseline:
`f016f3cd6f0a4c461b481913c830d6021a346aa4`

Implement only the governed release-engineering boundary.

Critical:
- no product/domain/auth changes;
- no migrations/dependencies;
- no secrets;
- ZIP must contain vendor + compiled public/build;
- ZIP must exclude .env, dev/test/governance/tooling artifacts and mutable storage data;
- storage/app/private is persistent production data and must never be packaged/overwritten;
- IIS document root is public/;
- no Node runtime on production;
- build and verify the release artifact during validation;
- do not commit generated release artifacts.
