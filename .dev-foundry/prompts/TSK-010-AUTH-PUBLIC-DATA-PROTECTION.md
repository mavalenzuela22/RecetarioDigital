# TSK-010 — Authentication & Public Data Protection — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

Read first:
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-010-AUTH-PUBLIC-DATA-PROTECTION.md`
3. `docs/ARCHITECTURE-v1.md`
4. `docs/UX-PRINCIPLES-v1.md`

Expected branch:
`tsk-010-auth-public-data-protection`

Expected baseline:
`3fcca95556669853139f6d2169f6f2e4e9fd3a9a`

Implement exactly the governed TSK:
- Laravel native session login/logout;
- throttle failed logins;
- auth-protect every business route;
- operator-only Artisan user provisioning;
- private authenticated recipe image delivery;
- shared Feature-test authentication;
- real UI login helper for all existing Playwright business journeys;
- dedicated mobile auth E2E.

Constraints:
- no auth package;
- no dependency or migration change;
- no registration/RBAC/password reset;
- no test-only auth bypass;
- preserve all domain semantics;
- <=30 visible paths.

Failure policy:
Run the full matrix. Preserve evidence and stop for classification on failure. No blind retry.
