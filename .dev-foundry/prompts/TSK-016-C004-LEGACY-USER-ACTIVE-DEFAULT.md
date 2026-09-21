# TSK-016 C004 — Legacy User Active Default Compatibility

Bounded corrective after C003.

## Classified regression

C003 fixed Pest bootstrap configuration and allowed the suite to execute.

The new `active` middleware then exposed a compatibility regression:
- the migration correctly defines users.active DEFAULT true;
- tests/TestCase.php creates the baseline authenticated user without explicitly setting active;
- immediately after Eloquent `User::create()`, the in-memory model does not contain the database-defaulted `active` attribute;
- the boolean cast therefore reads the missing value as false;
- EnsureActiveUser logs out that freshly-created authenticated user;
- this causes widespread 303-to-login failures across legacy feature tests in both SQLite and Percona.

This is not a database compatibility failure. It is an Eloquent model-default compatibility issue introduced by TSK-016.

## Authorized mutation

Modify only app/Models/User.php (plus these C004 governance artifacts).

Add explicit model attribute defaults equivalent to:
- active => true
- is_admin => false

Use Laravel model defaults so newly instantiated/created users that omit these fields match the database defaults before refresh.

Do not change:
- EnsureActiveUser semantics;
- migration defaults;
- tests/TestCase.php;
- existing feature tests;
- Google authorization;
- invitation rules;
- admin lockout behavior.

An explicitly persisted `active=false` user must still cast false and be blocked.

## Validation

Run full TSK-016 matrix:
- Composer validate/show/install;
- full SQLite Pest;
- full Percona 8.4 gate;
- npm ci/audit low;
- TypeScript;
- Vite 8;
- mobile Playwright;
- release ZIP build/verify;
- git diff --check.

If a smaller real product/test failure remains after this compatibility fix, report it exactly.
