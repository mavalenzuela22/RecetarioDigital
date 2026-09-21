# TSK-016 — Google Login + Invitation-Based Access Administration

Operate as the bounded implementation executor for EmprendimientoOS.

Read first:
- docs/TSK-016-GOOGLE-LOGIN-INVITATION-ACCESS-ADMIN.md
- .dev-foundry/profiles/project-operating-profile-v2.yaml
- docs/PRODUCT-DEFINITION-v1.md
- docs/ARCHITECTURE-v1.md
- docs/UX-PRINCIPLES-v1.md
- docs/PRODUCTION-DEPLOYMENT-IIS.md

## Outcome

Implement the complete invitation-gated Google access model and its small admin UI.

Normal patrona journey:
invitation link -> Continuar con Google -> verified exact-email match -> inside.
Later:
login -> Continuar con Google -> inside.

No public signup and no password creation for invited users.

## Mandatory implementation principles

- Use official Laravel Socialite (supported 5.x compatible with Laravel 12).
- Keep OAuth stateful.
- Persist Google subject/provider id and normalized verified email, but never access/refresh tokens.
- Invitation token: cryptographically random raw value, SHA-256 or stronger one-way stored representation only.
- Use a DB transaction for invitation acceptance and bootstrap completion.
- Unknown Google identities do not JIT-create without invitation/bootstrap.
- Existing same-email password user does not silently link without invitation.
- Normalize email by trim + lowercase at every trust boundary.
- Fail closed on email mismatch, unverified email, provider subject collision, expired/revoked/accepted token, inactive account or ambiguous linking.
- Use persistent one-time bootstrap state; never infer bootstrap eligibility only from current user/admin count.
- Bootstrap secret comes from environment-backed config, is entered via POST body, compared timing-safely, rate-limited, never put in URL, never persisted.
- Once bootstrap completes, setup is permanently unavailable.
- Add active-user enforcement for all authenticated business routes.
- Add admin authorization without creating a generalized RBAC system.
- Keep local password login as support/recovery and let current admin set/update their own >=12-character recovery password.
- Do not add SMTP/email sending.
- Do not add user deletion, admin promotion UI, forgot-password mail, registration, OIDC beyond Google, or OAuth token storage.

## UX

- Google button primary on login.
- Password form visibly secondary as support/recovery.
- Mobile first at 320/390.
- Admin-only Accesos link from Home.
- Access admin page must list users/invitations and support create+copy link, revoke, activate/deactivate and own recovery password.
- Invitation landing page should plainly say the invitation email and offer Continuar con Google.
- Calm Spanish errors; no provider internals.

## Testing

Use Socialite's supported fake/mocking facilities. No live Google dependency.

Implement all security/behavior tests enumerated in the task document, including bootstrap, invitation abuse cases, linking collisions, inactive-session enforcement, admin lockout protections and recovery password fallback.

Update existing Playwright only as needed for the new login presentation; add focused mobile coverage for invitation/admin surfaces without calling live Google.

## Operations

Update docs/PRODUCTION-DEPLOYMENT-IIS.md with exact production setup:
- Google OAuth web client;
- exact HTTPS authorized redirect URI;
- environment-backed client id/secret;
- bootstrap secret;
- one-time browser bootstrap;
- remove bootstrap secret after success;
- invitation administration from UI;
- production requires no Artisan/SSH for user onboarding.

Do not create or modify a versioned env example file.

Run the complete validation matrix. If a hosting/runtime limitation invalidates stateful OAuth or browser bootstrap, stop BLOCKED with evidence rather than weakening security.
