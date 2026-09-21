# TSK-016 C001 — Composer Lock + Invitation Feedback Corrective

Operate only as a bounded corrective for the already implemented TSK-016 boundary.

## Failure classification

The initial TSK-016 execution completed implementation without path-policy violations, but validation failed for three classified reasons:

1. `composer.json` requires `laravel/socialite:^5.24` while `composer.lock` was not regenerated.
2. Host-level `composer validate` is invalid in this environment because Composer is intentionally available inside the application container, not on the Mac host.
3. Invalid/revoked invitation redirects correctly to login with a Laravel session validation error, but the login React page only renders local `useForm().errors`, so the redirect error is not visible to the user or Playwright.

The Percona schema migrations themselves completed successfully. npm/TypeScript/Vite were green with zero npm vulnerabilities.

## Allowed corrective changes

Make only the minimum changes needed to:
- regenerate `composer.lock` for the already-declared Socialite dependency using Composer inside the Docker app environment;
- expose the redirect/session email error on the login page while preserving the existing form-local validation behavior;
- add/update a focused test only if necessary to prove the redirect error is visible.

Do not redesign Google authorization, invitation semantics, bootstrap, access-state enforcement, admin behavior, or database schema.

## Composer procedure

Use the app container, not host Composer.

Regenerate the lock for the existing requirement using the narrowest normal Composer operation, e.g. a targeted update for `laravel/socialite` and required dependencies. Do not broadly update unrelated packages unless Composer requires it.

Then prove:
- composer validate succeeds in container;
- composer install from lock succeeds;
- `laravel/socialite` is present in the resolved lock/vendor graph.

## Invitation feedback

Preserve the backend redirect and message:
`Esta invitación ya no está disponible. Pide una nueva invitación.`

Render session/Inertia validation errors on GET /login as well as local support-login form errors. Do not duplicate the same error visually when both sources contain the same message.

## Validation

Run the full corrected TSK-016 matrix:
- Docker Composer validate/install;
- full SQLite Pest;
- full Percona 8.4 gate;
- npm ci + audit low;
- TypeScript;
- Vite 8 build;
- full mobile Playwright;
- release ZIP build/verify;
- diff check.

Do not use polling, force installs, or scope expansion.
