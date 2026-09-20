# TSK-010 — Authentication & Public Data Protection

## Objective

Make EmprendimientoOS v1 safe for public deployment by ensuring business data is not anonymously accessible.

Implement the smallest safe authentication boundary using Laravel's existing session authentication infrastructure. Authentication must remain outside core domain logic.

This task also closes the direct recipe-image exposure created by public storage URLs.

## Authority

- `docs/ARCHITECTURE-v1.md`, Authentication:
  - public deployment must not expose business data anonymously;
  - authentication infrastructure stays outside core domain logic;
  - no complex RBAC in initial v1.
- Existing Laravel 12 session guard, `users` table, `User` model and sessions table.
- Existing Spanish-first mobile UX principles.

Do not modify `docs/design/**`.

## v1 authentication model

v1 has a very small trusted user population.

Implement:
- email + password login;
- session-based authentication using Laravel's existing `web` guard;
- logout;
- operator-only user provisioning via Artisan.

Do not implement:
- public registration;
- invitation workflow;
- password reset email delivery;
- email verification workflow;
- social login/OIDC;
- roles/permissions/RBAC;
- multi-tenant authorization;
- API tokens.

## Public vs protected surface

Public application endpoints:
- `GET /login`
- `POST /login`
- framework health endpoint `GET /up`
- static build/manifest/icon assets required to render/login/install the shell.

Protected by Laravel `auth` middleware:
- Home / Today;
- Ingredients and purchases;
- Recipes;
- Products, pricing and history;
- Orders, collections and fulfillment;
- Production;
- recipe image delivery;
- every other business-data route currently in `routes/web.php`.

A guest requesting a business page must be redirected to the named login route and must not receive Inertia business payloads.

Authenticated users must not need any role check in v1.

## Login semantics

Create an Inertia login page:
`Auth/Login`.

Required Spanish copy:
- `Iniciar sesión`
- `Correo`
- `Contraseña`
- `Entrar`
- generic credential error: `Los datos de acceso no son correctos.`

Behavior:
- use email and password only;
- preserve submitted email on validation/authentication failure;
- never echo/repopulate password;
- use password input and appropriate autocomplete attributes;
- successful authentication regenerates the session ID;
- honor Laravel's intended URL so a guest sent to login returns to the originally requested business page after successful authentication;
- do not expose whether an email exists.

### Brute-force protection

Use Laravel RateLimiter in authentication infrastructure.

Key attempts by normalized email + requesting IP.

Policy:
- allow at most 5 failed attempts per 60-second window;
- successful authentication clears the key;
- throttled response uses a non-sensitive Spanish message such as:
  `Demasiados intentos. Intenta de nuevo en unos segundos.`

Do not leak account existence through different failure responses.

## Logout semantics

Logout is POST-only and authenticated.

On logout:
1. call the session guard logout;
2. invalidate the session;
3. regenerate CSRF token;
4. redirect to login using 303.

Expose a clear logout action from authenticated application chrome:
- visible `Salir`;
- accessible name `Cerrar sesión`.

Home, which has its own header, must also expose logout.

## Operator-only user provisioning

Add an Artisan command:
`app:user-provision {email} {--name=} {--password-env=}`

Rules:
- normalize email to trim/lowercase and require a valid email;
- name is required either through `--name` or interactive prompt;
- password is read from the environment variable named by `--password-env` when supplied;
- otherwise, in an interactive terminal, request password and confirmation using secret/no-echo prompts;
- minimum password length: 12 characters;
- never print the password;
- upsert by normalized email so provisioning is repeatable;
- use the existing User password hashed cast / Laravel hashing;
- output only a success identifier such as the email.

Do not add default production credentials, seeded passwords, or a public account-creation endpoint.

## Recipe image privacy

Current recipe uploads use Laravel's `public` disk and expose `/storage/...` URLs. That violates the public-data requirement.

For newly uploaded recipe images:
- store under the private/local Laravel disk, not the public disk;
- preserve filesystem abstraction;
- retain only the storage path in recipe history;
- serve the image through an authenticated Laravel route associated with the recipe version;
- return 404 when the version has no image or the stored file is missing;
- set an appropriate image response/content type through Laravel Storage response handling.

Recipe Inertia payloads expose the authenticated image route, not a public disk URL.

No `public/storage` link is required by v1 after this change.

Historical order economics and recipe version immutability remain unchanged.

## Test authentication policy

Do not weaken production auth under `APP_ENV=testing`.

### Pest

Existing Feature tests are business-flow tests, so establish a shared authenticated test user in `tests/Pest.php` for Feature tests.

Requirements:
- use the real Laravel auth guard;
- no auth bypass middleware;
- no production code branch based on testing environment.

The dedicated Auth tests must explicitly log out/reset the guard to exercise guest behavior.

### Playwright

The Playwright web server must:
1. migrate the isolated SQLite DB;
2. provision a deterministic E2E user using `app:user-provision`;
3. start the normal Laravel server.

Use a shared E2E login helper. Update existing business-flow specs to log in through the real `/login` UI before their workflow.

Do not add a test-only login route, bypass cookie, or testing-only auto-auth middleware.

The fixed E2E credential is test fixture data only and must not be used as a production default.

## Required backend coverage

At minimum:

1. guest `GET /` redirects to login;
2. representative ingredient/recipe/product/order/production business routes redirect for guests;
3. login page renders without authentication;
4. valid credentials authenticate and redirect to intended URL;
5. invalid credentials use the same generic message and do not authenticate;
6. login regenerates session behavior through the actual controller path;
7. rate limiting triggers after five failed attempts and does not expose account existence;
8. successful login clears failed-attempt throttle;
9. logout logs out, invalidates access and redirects to login;
10. `GET /up` remains publicly available;
11. Artisan provisioning creates a hashed user without exposing password;
12. repeat provisioning updates the same normalized-email user rather than creating a duplicate;
13. invalid email / short password provisioning fails cleanly;
14. new recipe images are stored on the private/local disk rather than public;
15. authenticated recipe image route serves existing private image;
16. guest image request redirects to login and receives no image bytes;
17. missing image returns 404 to authenticated user;
18. existing business Feature suite remains passing while using real authenticated sessions.

## Required Playwright coverage

Add `e2e/auth.spec.ts`, mobile 320px and 390px.

At each width:
1. navigate to a protected business URL as a guest and verify redirect to login;
2. verify login heading/fields and no horizontal overflow;
3. submit wrong credentials and verify generic error;
4. submit valid provisioned E2E credentials;
5. verify return to the intended protected page;
6. use the visible logout action;
7. verify login page returns;
8. attempt another protected URL and verify it remains protected.

All existing business E2E specs must log in through the shared helper and remain behaviorally unchanged after authentication.

## Validation matrix

Run:
1. `docker compose build app`
2. `docker compose run --rm app composer install --no-interaction --prefer-dist`
3. `npm ci`
4. `docker compose run --rm app php artisan test`
5. `npx tsc --noEmit`
6. `npm run build`
7. idempotent cleanup of known E2E container
8. `npx playwright test --project=mobile`
9. `git diff --check`

## Path-count guardrail

The complete TSK, including governance and bounded correctives, must remain **<=30 visible changed paths**.

Target: 22–27 paths.

## Non-goals

Do not:
- introduce Breeze, Jetstream, Fortify or another auth package;
- add dependencies;
- add migrations unless an unexpected proven blocker exists;
- implement registration/password reset/email verification;
- implement RBAC;
- add multi-tenancy;
- change domain/economic semantics;
- redesign existing business pages;
- change session driver defaults or hardcode production cookie/domain settings;
- create public image URLs;
- weaken CSRF protection;
- create testing-only production backdoors.

## Completion rule

TSK-010 closes only when:
- anonymous requests cannot access business routes/data;
- authentication uses real Laravel sessions with login throttling and safe logout;
- operator provisioning is repeatable and contains no default production secret;
- recipe images are no longer newly written to public storage and are delivered through auth;
- existing Feature and all mobile E2E workflows pass through real authentication;
- semantic review finds no auth bypass or public business-data path;
- governed promotion/reconciliation/cleanup completes.
