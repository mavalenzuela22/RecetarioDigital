# TSK-016 C002 — Deterministic Socialite Lock Refresh

This is a bounded corrective for TSK-016 after C001.

## Classified state

C001 successfully fixed the invitation feedback presentation:
- full mobile Playwright is now PASS (20 tests);
- npm audit, TypeScript and Vite 8 build are PASS;
- Percona migrations are PASS.

The sole root blocker is deterministic:
- composer.json already declares laravel/socialite:^5.24;
- composer.lock still does not contain laravel/socialite;
- therefore Composer install fails, Pest cannot load its dev dependencies, the Percona full suite cannot run, and release packaging cannot install production dependencies.

Do not alter application code, auth logic, invitation logic, schema, UI, routes, tests, or docs except the C002 governance artifacts.

## Required mutation

Regenerate composer.lock for the existing laravel/socialite requirement using Composer inside the Docker app environment.

The canonical command is:

docker compose run --rm app composer update laravel/socialite --with-dependencies --no-interaction --prefer-dist

The execution contract also runs this command as the first dependency validation step so the lock refresh is deterministic even if the executor performs no additional mutation.

Do not:
- change composer.json constraints;
- remove Socialite;
- broadly update unrelated packages beyond dependencies Composer must resolve for Socialite;
- use ignore-platform-reqs;
- use force-like dependency bypasses.

## Required proof

After lock refresh:
- composer validate --no-check-publish passes in the app container;
- composer show laravel/socialite succeeds;
- composer install from lock succeeds;
- full SQLite Pest passes;
- full Percona 8.4 gate passes;
- npm audit remains 0 vulnerabilities;
- TypeScript/Vite pass;
- mobile Playwright remains PASS;
- release ZIP build/verify passes;
- git diff --check passes.

No other product mutation is authorized.
