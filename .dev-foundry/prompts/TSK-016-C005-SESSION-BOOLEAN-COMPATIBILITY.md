# TSK-016 C005 — Session Boolean Compatibility

Bounded corrective after C004.

## Classified failure

C004 reduced the suite from 52 failures to 12 failures.

All remaining observed failures are in GoogleAuthTest and share the same runtime exception:

BadMethodCallException: Method Illuminate\Session\Store::boolean does not exist.

Repository search confirms exactly two invalid calls:
- GoogleAuthController.php line ~31
- GoogleAuthController.php line ~68

The application uses Laravel 12 where Illuminate\Session\Store does not provide a boolean() helper.

## Authorized mutation

Modify only app/Http/Controllers/GoogleAuthController.php (plus C005 governance artifacts).

Replace both invalid calls with a safe boolean interpretation of the session value, preserving existing semantics:
- only explicit truthy bootstrap_authorized session state should be treated as true;
- missing/null/false must remain false.

A simple strict comparison to true is preferred because SetupController stores a boolean true.

Do not change:
- bootstrap flow semantics;
- invitation logic;
- Google linking rules;
- user active/admin behavior;
- routes;
- schema;
- tests;
- UI;
- Composer files.

## Validation

Run the complete TSK-016 matrix:
- Composer validate/show/install;
- full SQLite Pest;
- full Percona 8.4 gate;
- npm ci/audit low;
- TypeScript;
- Vite 8;
- full mobile Playwright;
- release ZIP build/verify;
- git diff --check.

If another real failure remains after this compatibility fix, report it exactly.
