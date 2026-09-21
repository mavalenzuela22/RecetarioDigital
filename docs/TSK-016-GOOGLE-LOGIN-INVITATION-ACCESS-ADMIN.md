# TSK-016 — Google Login + Invitation-Based Access Administration

## Objective

Replace the normal end-user password experience with an invitation-gated Google login while preserving a controlled local-password break-glass path.

This task must also make access administration operable entirely from the application because the production GoDaddy environment must not depend on Artisan/SSH/console access.

## Product intent

For the normal invited user:

1. An administrator creates an invitation for one exact email address.
2. The application returns a one-time invitation link that can be copied and sent through WhatsApp or another channel.
3. The invited person opens the link and chooses **Continuar con Google**.
4. Google returns a verified identity.
5. EmprendimientoOS requires the normalized Google email to exactly match the invitation email.
6. The invitation is consumed, a local user is created or safely linked, and the user enters the application.
7. Future logins use **Continuar con Google** directly; the invitation link is no longer required.

There is no public registration flow and no hard-coded whitelist.

## Upstream facts

- Laravel Socialite officially supports Google OAuth.
- The Google provider requests openid/profile/email and exposes Google's email verification claim in the raw user payload.
- Google web OAuth redirect URIs must exactly match an authorized redirect URI; production redirect URIs require HTTPS (localhost is the development exception).
- Socialite supports stateful OAuth and should remain stateful here.

## Authentication model

### Google normal login

Public routes:
- login page;
- Google redirect;
- Google callback.

Normal Google login is allowed only when:
- the Google email is verified;
- a local user already exists;
- the local user is active;
- the local user has the same stored Google subject/provider id;
- the normalized callback email matches the stored local email.

An unknown Google account must not create a user and must receive a calm invitation-required message.

An existing local user with matching email but no Google subject must **not** be silently linked during normal login. Linking an existing password-only user requires a valid invitation for that same email.

Google access tokens and refresh tokens must never be persisted.

### Invitation access

Create table/model for access invitations with at least:
- normalized email;
- one-way token hash;
- created-by admin;
- expiration;
- accepted timestamp;
- accepted-by user;
- revoked timestamp;
- timestamps.

Rules:
- raw invitation token is generated with cryptographically secure randomness;
- only a hash is stored;
- default expiry is seven days;
- accepted/revoked/expired invitations cannot be reused;
- only one currently usable pending invitation should exist per email by application rule;
- invitation link is revealed to the administrator only when created;
- if the link is lost, revoke/create another instead of storing recoverable plaintext;
- exact normalized email match is required at Google callback;
- Google email must be verified;
- subject collisions or ambiguous account linking must fail closed;
- successful acceptance is transactional: user link/create + invitation consumption must succeed atomically.

### User access state

Extend users with:
- nullable password for Google-only users;
- nullable unique Google subject/provider id;
- active flag;
- admin flag.

Rules:
- inactive users cannot log in;
- an already-authenticated user who becomes inactive is blocked on the next authenticated request and logged out;
- an admin cannot deactivate their own current account;
- the last active administrator cannot be deactivated;
- v1 has only admin vs regular access. Do not create a generalized RBAC system.

### Initial administrator bootstrap

Production cannot depend on CLI user creation.

Create a one-time browser bootstrap flow:
- public setup page is available only while persistent bootstrap state is incomplete;
- operator supplies a bootstrap secret configured outside version control via production environment configuration;
- secret comparison must be timing-safe and failed attempts rate-limited;
- the secret must never be stored in the database or displayed after submission;
- successful secret validation authorizes only the current session to continue with Google;
- Google callback must require verified email;
- first successful bootstrap Google identity becomes active administrator;
- persistent bootstrap state is marked completed transactionally;
- after completion the setup flow remains unavailable even if the environment secret is still present;
- documentation instructs the operator to remove the bootstrap secret after successful initialization.

Do not put the bootstrap secret in a URL.

### Break-glass local password

Keep the existing local password POST login as a de-emphasized support/recovery path.

Because production has no console dependency, an authenticated administrator must be able to set/update a recovery password for their own account from Access Administration:
- minimum 12 characters;
- confirmation required;
- Laravel hashed cast / Hash facilities only;
- never display or log the password.

Google-only users may keep password NULL.

## Administration UI

Add a small mobile-first administrator-only **Accesos** surface, reachable from Home only for admins.

It must support:
- list active/inactive users;
- show whether an account is Google-linked;
- list invitations and status (pending, accepted, expired, revoked);
- create invitation by exact email;
- immediately copy the generated invitation link;
- revoke pending invitation;
- deactivate/reactivate a user with lockout protections;
- set/update the current administrator's recovery password.

No SMTP/email-sending integration is required in v1.
No user deletion.
No role editor.
No invitation for administrator elevation.
No generalized permissions UI.

## Login UX

The main login page must make **Continuar con Google** the primary action.

The password form remains available as a visually de-emphasized **Acceso de soporte** / recovery fallback.

The invited-user flow must not ask the patrona to create an EmprendimientoOS password or fill a registration form.

## Configuration

Add safe configuration keys that read secrets from environment configuration. Do not commit secret values and do not introduce a versioned env file.

Expected operational values include:
- Google client id;
- Google client secret;
- Google redirect path/URI;
- bootstrap secret.

Prefer a relative Socialite callback path when supported so deployment base URL remains authoritative.

Update the IIS/GoDaddy deployment runbook with:
- Google Cloud OAuth client setup;
- exact HTTPS callback URI;
- required production environment keys;
- one-time bootstrap steps;
- invitation/admin operation;
- removal of bootstrap secret after initialization;
- no console requirement.

## Dependency

Use official `laravel/socialite` on the current supported 5.x line compatible with Laravel 12.

## Required tests

At minimum cover:

### Google authentication
- redirect route is public and stateful;
- existing active linked user can log in and intended URL is honored;
- unknown Google user without invitation is denied and not created;
- matching-email local account without Google subject is not silently linked;
- unverified Google email is denied;
- Google subject collision is denied;
- provider cancellation/error is handled calmly;
- inactive user is denied;
- callback regenerates authenticated session;
- logout still invalidates the session.

### Invitations
- normalized exact-email invitation creation;
- database stores token hash, not raw token;
- seven-day default expiry;
- invalid/expired/revoked/accepted invitation cannot be used;
- mismatched Google email cannot accept;
- verified matching email creates Google-only user with nullable password;
- existing matching password account can be safely linked only through invitation;
- repeated acceptance cannot duplicate users or reuse invitation;
- acceptance is transactional.

### Administration
- non-admin receives 403;
- admin can list/create/revoke;
- admin can deactivate/reactivate another user;
- self-deactivation blocked;
- last-active-admin deactivation blocked;
- deactivated authenticated user is rejected on next protected request;
- recovery password can be set and then used by local fallback login.

### Bootstrap
- setup unavailable after completion;
- missing/wrong secret denied and throttled;
- secret is not accepted through URL/query;
- valid secret authorizes only current session;
- verified Google callback creates first active admin and permanently consumes bootstrap;
- unverified/cancelled callback does not consume bootstrap.

### Regression
- existing password auth tests remain valid;
- all business routes remain guest-protected;
- recipe image privacy remains intact.

## Validation matrix

1. Composer dependency resolution/install.
2. PHP formatting/static syntax where applicable.
3. Full SQLite Pest.
4. Percona 8.4 full gate.
5. npm ci.
6. npm audit --audit-level=low.
7. TypeScript noEmit.
8. Vite 8 production build.
9. Docker app build.
10. Full mobile Playwright.
11. Production release ZIP build + verify.
12. git diff --check.

## Completion

PASS requires:
- Google login works only for already-linked active users or valid invitation/bootstrap flows;
- no open registration / no hardcoded whitelist;
- invitation tokens are one-way persisted and single-use;
- first admin can be bootstrapped from browser without console;
- access administration is possible in the application;
- local password is optional and break-glass only;
- no OAuth tokens are persisted;
- SQLite and Percona behavior agree;
- full regression matrix is green.
