# TSK-018 MT-002 — Access/Auth recovery (AUD-07 + AUD-09 + AUD-10 + AUD-15)

Operate as the bounded implementation executor for active TSK-018 MT-002.

Repository:
- branch: tsk-018-user-recovery-mobile-workflow-clarity
- base HEAD remains 369baf5841cdc3a9dcab8a16a14fc60ce380acec with inherited MT-001 dirty changes
- task authority: docs/TSK-018-USER-RECOVERY-MOBILE-WORKFLOW-CLARITY.md
- remediation authority: docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md

Fix exactly AUD-07, AUD-09, AUD-10, AUD-15.

AUD-07 — invitation create + refresh 405
Observed cause: AccessAdminController::storeInvitation() returns an Inertia render directly from the successful POST.
Required outcome:
- successful invitation creation must use PRG and land on a stable GET route;
- the one-time raw invitation link must still be available to the immediately following GET without persisting secrets beyond the minimum session flash lifetime;
- refreshing that GET must remain navigable and must not recreate the invitation or expose 405;
- duplicate invitation behavior remains unchanged.

AUD-09 — Back after logout restores private commercial data
Required outcome:
- authenticated/private application responses must carry an appropriate no-store/private cache policy so browser history navigation after logout does not restore usable private commercial content from cache;
- keep logout session invalidation/regeneration intact;
- do not weaken authentication or rely only on client-side hiding;
- public login/health behavior must remain valid.
Prefer the smallest centralized server-side mechanism consistent with Laravel/Inertia. A dedicated middleware is acceptable if needed.

AUD-10 — recovery password validation exposes translation keys / poor recovery
Required outcome:
- RecoveryPasswordRequest must provide natural es-MX messages for required, string, min:12, and confirmation mismatch;
- Access UI must associate errors to the correct field(s), expose them accessibly, and move/focus recovery sensibly on validation failure;
- do not log or redisplay passwords.

AUD-15 — invitation email overflows 320px and obscures Revocar
Required outcome:
- normal and long invitation emails must wrap safely at 320px;
- Revocar remains visible/tappable;
- no horizontal page overflow at 320/390.

Regression requirements:
1. Feature test: successful invitation POST returns 303 redirect to stable access GET, flash carries one-time link/notice only as needed, refresh GET succeeds without duplicate side effects.
2. Feature test: authenticated private responses include the chosen anti-cache headers and logout still invalidates auth.
3. Feature test: recovery-password validation returns human es-MX messages, not translation keys.
4. Playwright at 320/390:
   - create invitation via explicit admin fixture where available; refresh resulting page and prove no 405 and stable access surface;
   - long invitation email does not overflow and Revocar remains visible;
   - recovery-password invalid submission visibly reports natural-language error and focuses/associates the relevant field;
   - authenticate, view private business data, logout, use browser Back, and prove private business UI is not restored as an authenticated usable page.
5. Keep Google OAuth uncalled/unrequired.

Implementation constraints:
- preserve invitation token hashing, expiration, revocation, exact-email semantics, admin invariants, CSRF and session security;
- do not add migrations/models/routes unless absolutely required by observed behavior; prefer existing access.index GET;
- no TSK-019/020 work;
- no broad auth refactor;
- do not invoke Foundry Runner or mutate governance files from executor.

Before editing, inspect current routes/middleware/test harness. Make the smallest safe complete change.