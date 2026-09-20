# TSK-013 — Percona 8.4 Runtime Compatibility Gate

## Objective

Close the remaining production-database evidence gap for EmprendimientoOS v1.

Production is explicitly targeted at MySQL-compatible Percona Server 8.4, while the normal fast test suite currently uses SQLite in memory. This task must prove that the real migrations and full backend behavior execute successfully against an ephemeral Percona 8.4 server.

This is a production-readiness task, not a product feature.

## Observed gap

- `docs/ARCHITECTURE-v1.md` declares MySQL-compatible Percona Server 8.4 as the production database.
- prior TSK evidence explicitly did not claim live MySQL verification.
- `phpunit.xml` uses SQLite `:memory:`.
- `SESSION_DRIVER=database` is supported by the existing `sessions` migration.
- TSK-012 production guidance specifies `CACHE_STORE=database`, but no migration currently creates `cache` or `cache_locks`.
- `config/queue.php` currently defaults to the database driver, but v1 has no deployed queue worker on the IIS target.

## Required changes

### 1. Ephemeral Percona 8.4 test service

Extend `compose.yaml` with an isolated `percona` service under profile `percona-test`.

Requirements:
- image: official `percona/percona-server:8.4`;
- no host database port exposure;
- disposable test-only credentials;
- ephemeral database storage;
- healthcheck suitable for `docker compose ... up --wait`;
- the normal `app` service remains unchanged for normal development;
- no production secret is introduced.

For cross-platform reproducibility, an overridable Compose platform value may default to `linux/amd64`.

### 2. Required database-cache schema

Add one forward migration that creates the standard Laravel database-cache tables:
- `cache`;
- `cache_locks`.

The migration must be portable across SQLite and Percona/MySQL and have a complete `down()`.

Do not rewrite earlier historical migrations.

### 3. Queue runtime policy

The current production shape has no persistent queue worker.

Change the default queue connection in `config/queue.php` to `sync`.

Update `docs/PRODUCTION-DEPLOYMENT-IIS.md` to make `QUEUE_CONNECTION=sync` explicit for v1. Do not add jobs/job_batches/failed_jobs tables in this task.

A future background-worker TSK may separately introduce durable queue infrastructure if product requirements justify it.

### 4. Percona compatibility script

Create `scripts/test-percona.sh`.

It must:
- use `set -euo pipefail`;
- start only the Percona test profile and wait through Compose health status, with no assistant/manual polling loop;
- always clean up the Percona test container and its disposable volume on exit;
- print server version/comment/sql mode as evidence;
- run `php artisan migrate:fresh --force` through the app container using:
  - `DB_CONNECTION=mysql`;
  - `DB_HOST=percona`;
  - the disposable test database/user/password;
  - `SESSION_DRIVER=database`;
  - `CACHE_STORE=database`;
  - `QUEUE_CONNECTION=sync`;
- verify required tables exist, including `sessions`, `cache`, `cache_locks` and representative domain tables;
- run the full Pest suite against that same Percona service with the same database/session/cache settings.

It must not modify `phpunit.xml`; SQLite remains the normal fast test path.

### 5. Documentation

Update README with a concise database-compatibility validation section documenting:
- normal Pest remains SQLite;
- `bash scripts/test-percona.sh` is the production-database compatibility gate;
- it is ephemeral and does not touch production.

## Required invariants

- no product/UI behavior changes;
- no financial semantics change;
- no existing migration is rewritten;
- no production credentials;
- no production database access;
- no exposed host DB port;
- SQLite fast suite remains green;
- full Pest suite passes against Percona 8.4;
- TSK-012 release packaging still passes;
- full mobile Playwright remains green.

## Validation matrix

1. `docker compose build app`
2. `docker compose run --rm app composer install --no-interaction --prefer-dist`
3. `npm ci`
4. normal SQLite `docker compose run --rm app php artisan test`
5. `bash scripts/test-percona.sh`
6. `npx tsc --noEmit`
7. `npm run build`
8. clean known E2E container
9. full `npx playwright test --project=mobile`
10. rebuild and verify the TSK-012 release ZIP
11. shell syntax check for release and Percona scripts
12. `git diff --check`

## Path budget

Target <= 10 tracked paths; hard maximum 16.

## Non-goals

- live production DB access;
- production data migration;
- MySQL tuning;
- replication/backups;
- queue workers;
- Redis;
- application features;
- changing persisted financial representation.

## Completion

TSK-013 closes only when both the fast SQLite suite and full Percona 8.4 backend suite pass, production runtime DB prerequisites are internally consistent, release validation remains green, and governed promotion/reconciliation/cleanup completes.
