# TSK-013 — Percona 8.4 Runtime Compatibility Gate — Implementation Handoff

Operate as the bounded implementation executor for RecetarioDigital / EmprendimientoOS.

Read:
1. `.dev-foundry/profiles/project-operating-profile-v2.yaml`
2. `docs/TSK-013-PERCONA-RUNTIME-COMPATIBILITY.md`
3. `docs/ARCHITECTURE-v1.md`
4. `docs/PRODUCTION-DEPLOYMENT-IIS.md`
5. `compose.yaml`, `phpunit.xml`, `config/database.php`, `config/cache.php`, `config/session.php`, `config/queue.php`
6. all current migrations.

Expected branch:
`tsk-013-percona-runtime-compatibility`

Expected baseline:
`1d39dacc2c9916d7427246835d280fd57b0ab8f4`

Implement only the governed production-database compatibility boundary.

Critical:
- use official Percona Server 8.4 image;
- no live/prod DB;
- no host DB port;
- no production secrets;
- no rewriting historical migrations;
- add only cache/cache_locks runtime migration;
- queue default becomes sync because v1 IIS has no worker;
- normal SQLite tests remain untouched and green;
- full Pest must also pass against ephemeral Percona;
- preserve all TSK-012 release validation.
