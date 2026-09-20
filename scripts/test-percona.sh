#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
COMPOSE=(docker compose -p emprendimientoos-tsk013)
APP_ENV_VARS=(
    -e APP_ENV=testing
    -e APP_DEBUG=false
    -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
    -e DB_CONNECTION=mysql
    -e DB_HOST=percona
    -e DB_PORT=3306
    -e DB_DATABASE=emprendimientoos_test
    -e DB_USERNAME=emprendimientoos_test
    -e DB_PASSWORD=percona_tsk013_only
    -e SESSION_DRIVER=database
    -e CACHE_STORE=database
    -e QUEUE_CONNECTION=sync
    -e MAIL_MAILER=array
    -e BCRYPT_ROUNDS=4
)

cleanup() {
    local exit_code=$?

    trap - EXIT
    "${COMPOSE[@]}" --profile percona-test down --volumes --remove-orphans >/dev/null 2>&1 || true
    exit "$exit_code"
}

run_app() {
    "${COMPOSE[@]}" run --rm --no-deps "${APP_ENV_VARS[@]}" app "$@"
}

cd "$REPO_ROOT"
trap cleanup EXIT

# Keep this test project disposable even if a previous invocation was interrupted.
"${COMPOSE[@]}" --profile percona-test down --volumes --remove-orphans >/dev/null 2>&1 || true

printf '%s\n' 'Starting disposable Percona Server 8.4 test service...'
"${COMPOSE[@]}" --profile percona-test up -d --wait percona

printf '%s\n' 'Printing Percona server evidence...'
run_app php -r '
$pdo = new PDO(
    "mysql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE"),
    getenv("DB_USERNAME"),
    getenv("DB_PASSWORD"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$row = $pdo->query("SELECT VERSION() AS server_version, @@version_comment AS version_comment, @@sql_mode AS sql_mode")->fetch(PDO::FETCH_ASSOC);
foreach ($row as $name => $value) {
    printf("%s=%s%s", $name, $value ?? "", PHP_EOL);
}
'

printf '%s\n' 'Running a fresh migration against Percona...'
run_app php artisan migrate:fresh --force

printf '%s\n' 'Verifying runtime and representative domain tables...'
run_app php -r '
$pdo = new PDO(
    "mysql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE"),
    getenv("DB_USERNAME"),
    getenv("DB_PASSWORD"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$required = [
    "migrations",
    "users",
    "sessions",
    "cache",
    "cache_locks",
    "ingredients",
    "ingredient_purchases",
    "recipes",
    "recipe_versions",
    "products",
    "product_prices",
    "orders",
    "order_lines",
    "order_payments",
    "order_fulfillment_events",
];
$actual = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
$missing = array_values(array_diff($required, $actual));
if ($missing !== []) {
    fwrite(STDERR, "Missing required tables: " . implode(", ", $missing) . PHP_EOL);
    exit(1);
}
printf("Verified tables=%d%s", count($required), PHP_EOL);
'

printf '%s\n' 'Running the full Pest suite against Percona...'
run_app php artisan test

printf '%s\n' 'Percona 8.4 compatibility gate passed.'
