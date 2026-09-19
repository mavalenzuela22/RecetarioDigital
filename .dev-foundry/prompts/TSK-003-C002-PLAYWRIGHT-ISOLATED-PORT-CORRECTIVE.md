# TSK-003 C002 — Isolated Playwright Port Corrective

Operate as the bounded implementation executor for TSK-003.

Observed after C001:
- Laravel/Pest: PASS, 32 tests / 280 assertions
- production frontend build: PASS
- WebKit installation: PASS
- git diff --check: PASS
- Playwright failed only because http://127.0.0.1:8000 was already in use
- repository has 26 visible changed paths, all belonging to the authorized TSK-003 + C001 work

## Required corrective

Use a dedicated default Playwright webServer port that is unlikely to collide with the developer environment, for example 18080.

Update `playwright.config.ts` so that when `PLAYWRIGHT_BASE_URL` is not supplied:
- baseURL points to the dedicated port
- Docker `--service-ports` is NOT relied on for a fixed host mapping that still targets 8000
- the application server inside the ephemeral container binds the same dedicated port
- the managed server continues to use isolated SQLite inside the ephemeral container
- `migrate:fresh --database=sqlite --force` still runs before serving
- `reuseExistingServer` remains false

Do not set `reuseExistingServer:true`.
Do not connect to an existing server on port 8000.
Do not weaken database isolation.

If Docker compose port publishing makes this awkward, use an explicit one-off port mapping for the dedicated host/container port rather than changing compose.yaml.

Do not modify application/domain behavior unless a new reproduced functional defect appears after Playwright can launch.

## Validation
Run:
- Docker build
- Composer install
- npm ci
- full Pest suite
- production frontend build
- Playwright WebKit install
- real mobile Playwright purchase flow
- git diff --check

Final visible path count must remain <= 30.

Do not commit, push, create PR, or merge.
