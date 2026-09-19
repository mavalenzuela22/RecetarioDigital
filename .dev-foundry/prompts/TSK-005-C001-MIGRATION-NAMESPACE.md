# TSK-005 C001 — Migration Namespace Corrective

Operate as a bounded implementation corrective for TSK-005.

## Classified root cause
The initial TSK-005 implementation completed, path policy passed, TypeScript passed, Vite build passed, and git diff --check passed.

Full Pest and Playwright were blocked by one product implementation defect in:
`database/migrations/2026_09_19_000003_create_products_and_pricing.php`

The migration imports were emitted without PHP namespace separators:
- `IlluminateDatabaseMigrationsMigration`
- `IlluminateDatabaseSchemaBlueprint`
- `IlluminateSupportFacadesSchema`

This causes every database-backed Pest test to fail before its assertions and prevents the Playwright web server from completing migrations.

## Required mutation
Modify only:
- `database/migrations/2026_09_19_000003_create_products_and_pricing.php`

Correct the imports to the valid Laravel classes:
- `Illuminate\Database\Migrations\Migration`
- `Illuminate\Database\Schema\Blueprint`
- `Illuminate\Support\Facades\Schema`

Do not change schema semantics, product code, tests, UI, routes, manifests, Docker or Playwright configuration.

## Validation
Run the complete TSK-005 validation matrix after the fix.

If the repaired migration exposes any additional failure, preserve the evidence and stop for classification rather than broadening this corrective.
