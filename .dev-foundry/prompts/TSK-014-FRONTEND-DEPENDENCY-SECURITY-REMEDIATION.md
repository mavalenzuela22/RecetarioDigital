# TSK-014 — Frontend Dependency Security Remediation — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

Expected branch:
`tsk-014-frontend-dependency-security-remediation`

Expected baseline:
`0e51f8a63b362fb4d055622be002f88617f72b64`

Read:
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-014-FRONTEND-DEPENDENCY-SECURITY-REMEDIATION.md`
3. `package.json`
4. `package-lock.json`

Before changing anything, run `npm audit --json` and classify every current moderate/high/critical advisory, including package, dependency chain, direct/transitive status, runtime-vs-tooling relevance, and available fix.

Then apply the smallest supported remediation.

Hard rules:
- no `npm audit fix --force`;
- no application-code changes;
- no PHP/Composer changes;
- no overrides/resolutions unless governance is explicitly reopened;
- no new dependencies;
- avoid major upgrades unless they occur inside the already-supported declared semver range;
- final `npm audit --audit-level=moderate` must exit 0;
- preserve the complete SQLite + Percona + Playwright + release regression matrix.

If the only available fix requires breaking scope, stop BLOCKED with evidence rather than expanding.
