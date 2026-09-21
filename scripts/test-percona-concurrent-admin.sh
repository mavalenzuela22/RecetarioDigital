#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
REPO_ROOT=$(cd "$SCRIPT_DIR/.." && pwd)
TASK_TMP_ROOT=$(mktemp -d /tmp/tsk017-percona-ab.XXXXXX)
BASELINE_ROOT="$TASK_TMP_ROOT/baseline"
PROJECT="emprendimientoos-tsk017-ab-$$"
COMPOSE=(docker compose -p "$PROJECT")
PERCONA_ROOT_PASSWORD=percona_root_tsk013_only
APP_DB_USERNAME=emprendimientoos_test
APP_DB_DATABASE=emprendimientoos_test
LOCK_SAFETY_TIMEOUT_SECONDS=30
OBSERVER_TIMEOUT_SECONDS=45

APP_CONTAINER_ID=
LOCK_PID=
LOCK_LOG=
REQUEST_ONE_PID=
REQUEST_TWO_PID=
COOKIE_ONE=
COOKIE_TWO=
RESPONSE_ONE=
RESPONSE_TWO=
OBSERVER_PID=
OBSERVER_LOG=
PHASE_DIR=
PHASE_URL=
PHASE_PORT=
ADMIN_ONE_ID=
ADMIN_TWO_ID=
LOCK_CONNECTION_ID=
CONTROL_DIR=
PHASE_ENV_ARGS=()

release_lock_barrier() {
    if [[ -n "${CONTROL_DIR:-}" && -d "$CONTROL_DIR" ]]; then
        touch "$CONTROL_DIR/release" >/dev/null 2>&1 || true
    fi
}

cleanup() {
    local exit_code=$?

    trap - EXIT
    set +e

    if [[ -n "${REQUEST_ONE_PID:-}" ]]; then
        kill "$REQUEST_ONE_PID" >/dev/null 2>&1 || true
        wait "$REQUEST_ONE_PID" >/dev/null 2>&1 || true
    fi
    if [[ -n "${REQUEST_TWO_PID:-}" ]]; then
        kill "$REQUEST_TWO_PID" >/dev/null 2>&1 || true
        wait "$REQUEST_TWO_PID" >/dev/null 2>&1 || true
    fi
    if [[ -n "${OBSERVER_PID:-}" ]]; then
        kill "$OBSERVER_PID" >/dev/null 2>&1 || true
        wait "$OBSERVER_PID" >/dev/null 2>&1 || true
    fi
    release_lock_barrier
    if [[ -n "${LOCK_PID:-}" ]]; then
        kill "$LOCK_PID" >/dev/null 2>&1 || true
        wait "$LOCK_PID" >/dev/null 2>&1 || true
    fi
    if [[ -n "${APP_CONTAINER_ID:-}" ]]; then
        docker rm -f "$APP_CONTAINER_ID" >/dev/null 2>&1 || true
    fi
    "${COMPOSE[@]}" --profile percona-test down --volumes --remove-orphans >/dev/null 2>&1 || true
    rm -rf -- "$TASK_TMP_ROOT"

    exit "$exit_code"
}

trap cleanup EXIT

if [[ ! -d "$REPO_ROOT/vendor" ]]; then
    echo 'Missing vendor dependencies. Run Composer install before this harness.' >&2
    exit 1
fi

echo "Creating isolated HEAD baseline at $BASELINE_ROOT..."
mkdir -p "$BASELINE_ROOT"
git -C "$REPO_ROOT" archive --format=tar HEAD | tar -x -C "$BASELINE_ROOT"
cp -a "$REPO_ROOT/vendor" "$BASELINE_ROOT/vendor"
if [[ -d "$REPO_ROOT/public/build" ]]; then
    mkdir -p "$BASELINE_ROOT/public"
    cp -a "$REPO_ROOT/public/build" "$BASELINE_ROOT/public/build"
fi

csrf_token_from_cookie_jar() {
    local encoded_token

    encoded_token=$(awk '$6 == "XSRF-TOKEN" { print $7 }' "$1" | tail -n 1)
    test -n "$encoded_token"
    printf '%s' "$encoded_token" | perl -pe 's/%([0-9A-Fa-f]{2})/chr(hex($1))/eg'
}

set_phase_env() {
    local url=$1

    PHASE_ENV_ARGS=(
        -e APP_ENV=testing \
        -e APP_DEBUG=false \
        -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
        -e APP_URL="$url" \
        -e DB_CONNECTION=mysql \
        -e DB_HOST=percona \
        -e DB_PORT=3306 \
        -e DB_DATABASE=emprendimientoos_test \
        -e DB_USERNAME=emprendimientoos_test \
        -e DB_PASSWORD=percona_tsk013_only \
        -e SESSION_DRIVER=database \
        -e CACHE_STORE=database \
        -e QUEUE_CONNECTION=sync \
        -e MAIL_MAILER=array \
        -e BCRYPT_ROUNDS=4 \
        -e PHP_CLI_SERVER_WORKERS=2
    )
}

run_app() {
    local phase_dir=$1
    shift

    "${COMPOSE[@]}" run --rm --no-deps --volume "$phase_dir:/var/www/html" \
        "${PHASE_ENV_ARGS[@]}" app "$@"
}

run_percona_mysql() {
    local query=$1

    "${COMPOSE[@]}" exec -T \
        -e MYSQL_PWD="$PERCONA_ROOT_PASSWORD" \
        percona mysql \
        --protocol=TCP \
        --host=127.0.0.1 \
        --user=root \
        --database="$APP_DB_DATABASE" \
        --batch \
        --raw \
        --skip-column-names \
        --silent \
        -e "$query"
}

login_admin() {
    local email=$1
    local cookie_jar=$2
    local token status

    curl --fail --silent --show-error --cookie-jar "$cookie_jar" --cookie "$cookie_jar" "$PHASE_URL/login" >/dev/null
    token=$(csrf_token_from_cookie_jar "$cookie_jar")
    test -n "$token"
    status=$(curl --silent --show-error --cookie-jar "$cookie_jar" --cookie "$cookie_jar" \
        -H 'Accept: text/html' \
        -H 'X-XSRF-TOKEN: '"$token" \
        --data-urlencode "email=$email" \
        --data-urlencode 'password=concurrent-test-password' \
        --write-out '%{http_code}' \
        --output /dev/null \
        -X POST "$PHASE_URL/login")
    case "$status" in
        30[123]) ;;
        *)
            echo "Login for $email returned HTTP $status." >&2
            return 1
            ;;
    esac

    status=$(curl --silent --show-error --cookie-jar "$cookie_jar" --cookie "$cookie_jar" \
        -H 'Accept: text/html' \
        --write-out '%{http_code}' \
        --output /dev/null \
        "$PHASE_URL/admin/accesos")
    if [[ "$status" != '200' ]]; then
        echo "Authenticated access check for $email returned HTTP $status." >&2
        return 1
    fi

    token=$(csrf_token_from_cookie_jar "$cookie_jar")
    test -n "$token"
}

start_concurrency_observer() {
    local phase_tmp=$1
    local performance_schema_query
    local information_schema_query

    OBSERVER_LOG="$phase_tmp/observer.log"
    performance_schema_query="
SELECT DISTINCT
    w.REQUESTING_THREAD_ID,
    COALESCE(rt.PROCESSLIST_ID, 0),
    w.BLOCKING_THREAD_ID,
    COALESCE(bt.PROCESSLIST_ID, 0)
FROM performance_schema.data_lock_waits AS w
JOIN performance_schema.data_locks AS r
    ON r.ENGINE = w.ENGINE
    AND r.ENGINE_LOCK_ID = w.REQUESTING_ENGINE_LOCK_ID
JOIN performance_schema.data_locks AS b
    ON b.ENGINE = w.ENGINE
    AND b.ENGINE_LOCK_ID = w.BLOCKING_ENGINE_LOCK_ID
JOIN performance_schema.threads AS rt
    ON rt.THREAD_ID = w.REQUESTING_THREAD_ID
JOIN performance_schema.threads AS bt
    ON bt.THREAD_ID = w.BLOCKING_THREAD_ID
WHERE r.OBJECT_SCHEMA = '$APP_DB_DATABASE'
  AND r.OBJECT_NAME = 'users'
  AND r.LOCK_STATUS = 'WAITING'
  AND bt.PROCESSLIST_ID = $LOCK_CONNECTION_ID
  AND rt.PROCESSLIST_USER = '$APP_DB_USERNAME'
ORDER BY w.REQUESTING_THREAD_ID"
    information_schema_query="
SELECT DISTINCT
    r.trx_mysql_thread_id,
    r.lock_trx_id,
    b.trx_mysql_thread_id,
    b.lock_trx_id
FROM information_schema.innodb_lock_waits AS w
JOIN information_schema.innodb_locks AS r
    ON r.lock_id = w.requested_lock_id
JOIN information_schema.innodb_locks AS b
    ON b.lock_id = w.blocking_lock_id
JOIN information_schema.innodb_trx AS rt
    ON rt.trx_id = r.lock_trx_id
JOIN information_schema.innodb_trx AS bt
    ON bt.trx_id = b.lock_trx_id
WHERE r.lock_table LIKE '%$APP_DB_DATABASE%users%'
  AND bt.trx_mysql_thread_id = $LOCK_CONNECTION_ID
  AND rt.trx_mysql_thread_id IS NOT NULL
ORDER BY r.trx_mysql_thread_id"

    (
        local mode='performance_schema.data_lock_waits'
        local query="$performance_schema_query"
        local deadline=$(( $(date +%s) + OBSERVER_TIMEOUT_SECONDS ))
        local last_rows=''
        local rows waiter_count

        if ! rows=$(run_percona_mysql "$query" 2>&1); then
            echo "LOCK_METADATA_PERFORMANCE_SCHEMA_UNAVAILABLE=$rows"
            mode='information_schema.innodb_lock_waits'
            query="$information_schema_query"
            if ! rows=$(run_percona_mysql "$query" 2>&1); then
                echo "LOCK_METADATA_UNAVAILABLE=$rows"
                exit 2
            fi
        fi

        while (( $(date +%s) < deadline )); do
            if ! rows=$(run_percona_mysql "$query" 2>&1); then
                echo "LOCK_METADATA_QUERY_FAILED=$rows"
                exit 2
            fi

            waiter_count=$(printf '%s\n' "$rows" | awk '$1 ~ /^[0-9]+$/ { seen[$1] = 1 } END { count = 0; for (id in seen) count++; print count }')
            if (( waiter_count >= 2 )); then
                echo "CONCURRENCY_METADATA_MODE=$mode"
                while IFS=$'\t' read -r requester_id requester_detail blocker_id blocker_detail; do
                    [[ "$requester_id" =~ ^[0-9]+$ ]] || continue
                    echo "DB_WAIT_REQUESTER=$requester_id REQUESTER_DETAIL=$requester_detail BLOCKER=$blocker_id BLOCKER_DETAIL=$blocker_detail"
                done <<< "$rows"
                echo "DB_CONCURRENT_WAITERS=$waiter_count"
                echo 'CONCURRENCY_OBSERVED=PASS'
                exit 0
            fi

            last_rows=$rows
            sleep 0.25
        done

        echo "CONCURRENCY_METADATA_MODE=$mode"
        echo 'CONCURRENCY_OBSERVED=FAIL'
        echo 'CONCURRENCY_OBSERVER_TIMEOUT=1'
        while IFS=$'\t' read -r requester_id requester_detail blocker_id blocker_detail; do
            [[ "$requester_id" =~ ^[0-9]+$ ]] || continue
            echo "DB_WAIT_DIAGNOSTIC_REQUESTER=$requester_id REQUESTER_DETAIL=$requester_detail BLOCKER=$blocker_id BLOCKER_DETAIL=$blocker_detail"
        done <<< "$last_rows"
        if ! rows=$(run_percona_mysql "SELECT ID, USER, DB, COMMAND, TIME, STATE, INFO FROM information_schema.PROCESSLIST WHERE DB = '$APP_DB_DATABASE' OR ID = $LOCK_CONNECTION_ID ORDER BY ID LIMIT 20" 2>&1); then
            echo "PROCESSLIST_DIAGNOSTIC_FAILED=$rows"
        else
            while IFS=$'\t' read -r process_id process_user process_db process_command process_time process_state process_info; do
                [[ -n "$process_id" ]] || continue
                echo "PROCESSLIST id=$process_id user=$process_user db=$process_db command=$process_command time=$process_time state=$process_state info=$process_info"
            done <<< "$rows"
        fi
        exit 2
    ) >"$OBSERVER_LOG" 2>&1 &
    OBSERVER_PID=$!
}

stop_phase() {
    if [[ -n "${REQUEST_ONE_PID:-}" ]]; then
        kill "$REQUEST_ONE_PID" >/dev/null 2>&1 || true
        wait "$REQUEST_ONE_PID" >/dev/null 2>&1 || true
        REQUEST_ONE_PID=
    fi
    if [[ -n "${REQUEST_TWO_PID:-}" ]]; then
        kill "$REQUEST_TWO_PID" >/dev/null 2>&1 || true
        wait "$REQUEST_TWO_PID" >/dev/null 2>&1 || true
        REQUEST_TWO_PID=
    fi
    if [[ -n "${OBSERVER_PID:-}" ]]; then
        kill "$OBSERVER_PID" >/dev/null 2>&1 || true
        wait "$OBSERVER_PID" >/dev/null 2>&1 || true
        OBSERVER_PID=
    fi
    release_lock_barrier
    if [[ -n "${LOCK_PID:-}" ]]; then
        kill "$LOCK_PID" >/dev/null 2>&1 || true
        wait "$LOCK_PID" >/dev/null 2>&1 || true
        LOCK_PID=
    fi
    if [[ -n "${APP_CONTAINER_ID:-}" ]]; then
        docker rm -f "$APP_CONTAINER_ID" >/dev/null 2>&1 || true
        APP_CONTAINER_ID=
    fi
    "${COMPOSE[@]}" --profile percona-test down --volumes --remove-orphans >/dev/null 2>&1 || true
}

run_phase() {
    local phase_name=$1
    local phase_dir=$2
    local baseline_marker=$3
    local fixed_marker=$4
    local phase_tmp="$TASK_TMP_ROOT/$phase_name"
    local lock_exit request_one_exit request_two_exit observer_exit request_one_http request_two_http request_one_location request_two_location active_count db_waiters

    PHASE_DIR=$phase_dir
    mkdir -p "$phase_tmp"
    COOKIE_ONE="$phase_tmp/cookie-one"
    COOKIE_TWO="$phase_tmp/cookie-two"
    RESPONSE_ONE="$phase_tmp/response-one"
    RESPONSE_TWO="$phase_tmp/response-two"
    LOCK_LOG="$phase_tmp/lock.log"
    CONTROL_DIR="$phase_tmp/control"
    mkdir -p "$CONTROL_DIR"

    echo "[$phase_name] Starting disposable Percona Server 8.4..."
    "${COMPOSE[@]}" --profile percona-test up -d --wait percona

    echo "[$phase_name] Migrating and preparing exactly two active administrators..."
    PHASE_URL='http://127.0.0.1:0'
    set_phase_env "$PHASE_URL"
    run_app "$phase_dir" php artisan migrate:fresh --force
    run_app "$phase_dir" php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
App\Models\User::query()->create([
    "name" => "Concurrent Admin One",
    "email" => "admin-one@example.com",
    "password" => "concurrent-test-password",
    "is_admin" => true,
    "active" => true,
]);
App\Models\User::query()->create([
    "name" => "Concurrent Admin Two",
    "email" => "admin-two@example.com",
    "password" => "concurrent-test-password",
    "is_admin" => true,
    "active" => true,
]);
echo "prepared_admins=" . App\Models\User::query()->where("is_admin", true)->where("active", true)->count() . PHP_EOL;
'
    ADMIN_ONE_ID=$(run_app "$phase_dir" php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo App\Models\User::query()->where("email", "admin-one@example.com")->value("id");
')
    ADMIN_TWO_ID=$(run_app "$phase_dir" php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo App\Models\User::query()->where("email", "admin-two@example.com")->value("id");
')
    test -n "$ADMIN_ONE_ID"
    test -n "$ADMIN_TWO_ID"

    echo "[$phase_name] Starting the Laravel HTTP server with two workers..."
    set_phase_env "$PHASE_URL"
    APP_CONTAINER_ID=$("${COMPOSE[@]}" run --no-deps --detach --publish '127.0.0.1::8000' --volume "$phase_dir:/var/www/html" \
        "${PHASE_ENV_ARGS[@]}" app sh -ec 'exec php -d variables_order=EGPCS artisan serve --no-reload --host=0.0.0.0 --port=8000')
    for attempt in $(seq 1 60); do
        PHASE_PORT=$(docker port "$APP_CONTAINER_ID" 8000/tcp 2>/dev/null | sed -n 's/.*://p' | tail -n 1)
        if [[ -n "$PHASE_PORT" ]]; then
            PHASE_URL="http://127.0.0.1:$PHASE_PORT"
            set_phase_env "$PHASE_URL"
            break
        fi
        sleep 0.2
        if [[ "$attempt" == 60 ]]; then
            echo "[$phase_name] Application port was not published." >&2
            return 1
        fi
    done
    for attempt in $(seq 1 60); do
        if curl --fail --silent "$PHASE_URL/up" >/dev/null; then break; fi
        sleep 1
        if [[ "$attempt" == 60 ]]; then
            echo "[$phase_name] Application server did not become ready." >&2
            return 1
        fi
    done

    login_admin admin-one@example.com "$COOKIE_ONE"
    login_admin admin-two@example.com "$COOKIE_TWO"

    echo "[$phase_name] Acquiring a harness-only lock on both administrator rows..."
    "${COMPOSE[@]}" run --rm --no-deps --volume "$phase_dir:/var/www/html" --volume "$CONTROL_DIR:/tmp/harness-control" \
        "${PHASE_ENV_ARGS[@]}" app php -r '
$pdo = new PDO(
    "mysql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE"),
    getenv("DB_USERNAME"),
    getenv("DB_PASSWORD"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$pdo->beginTransaction();
$pdo->query("SELECT id FROM users WHERE is_admin = 1 ORDER BY id FOR UPDATE")->fetchAll();
echo "LOCK_CONNECTION_ID=" . $pdo->query("SELECT CONNECTION_ID()")->fetchColumn() . "\n";
echo "LOCK_READY\n";
fflush(STDOUT);
$deadline = microtime(true) + '"$LOCK_SAFETY_TIMEOUT_SECONDS"';
try {
    while (! file_exists("/tmp/harness-control/release")) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException("Harness lock release safety timeout expired.");
        }
        usleep(50000);
    }

    $pdo->commit();
    echo "LOCK_RELEASED\n";
    fflush(STDOUT);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
' >"$LOCK_LOG" 2>&1 &
    LOCK_PID=$!

    for attempt in $(seq 1 100); do
        if rg -q '^LOCK_READY$' "$LOCK_LOG"; then break; fi
        if ! kill -0 "$LOCK_PID" >/dev/null 2>&1; then
            cat "$LOCK_LOG" >&2
            return 1
        fi
        sleep 0.1
        if [[ "$attempt" == 100 ]]; then
            echo "[$phase_name] Timed out waiting for the deterministic lock barrier." >&2
            return 1
        fi
    done
    LOCK_CONNECTION_ID=$(sed -n 's/^LOCK_CONNECTION_ID=//p' "$LOCK_LOG" | tail -n 1)
    if [[ ! "$LOCK_CONNECTION_ID" =~ ^[0-9]+$ ]]; then
        echo "[$phase_name] Harness lock did not expose a valid connection id." >&2
        cat "$LOCK_LOG" >&2
        return 1
    fi

    echo "[$phase_name] Launching two authenticated concurrent deactivation requests..."
    curl --silent --show-error --cookie-jar "$COOKIE_ONE" --cookie "$COOKIE_ONE" \
        -H 'Accept: text/html' \
        -H 'X-XSRF-TOKEN: '"$(csrf_token_from_cookie_jar "$COOKIE_ONE")" \
        --data-urlencode 'active=0' \
        --dump-header "$RESPONSE_ONE.headers" \
        --write-out '%{http_code}' \
        --output "$RESPONSE_ONE" \
        -X POST "$PHASE_URL/admin/accesos/usuarios/$ADMIN_TWO_ID/estado" >"$RESPONSE_ONE.status" 2>&1 &
    REQUEST_ONE_PID=$!
    curl --silent --show-error --cookie-jar "$COOKIE_TWO" --cookie "$COOKIE_TWO" \
        -H 'Accept: text/html' \
        -H 'X-XSRF-TOKEN: '"$(csrf_token_from_cookie_jar "$COOKIE_TWO")" \
        --data-urlencode 'active=0' \
        --dump-header "$RESPONSE_TWO.headers" \
        --write-out '%{http_code}' \
        --output "$RESPONSE_TWO" \
        -X POST "$PHASE_URL/admin/accesos/usuarios/$ADMIN_ONE_ID/estado" >"$RESPONSE_TWO.status" 2>&1 &
    REQUEST_TWO_PID=$!

    echo "[$phase_name] Observing Percona lock waits for both application requests..."
    start_concurrency_observer "$phase_tmp"
    if wait "$OBSERVER_PID"; then observer_exit=0; else observer_exit=$?; fi
    OBSERVER_PID=
    if [[ "$observer_exit" -ne 0 ]]; then
        release_lock_barrier
        echo "[$phase_name] Percona did not prove two simultaneous application waiters." >&2
        echo "[$phase_name] curl_one_alive=$(kill -0 "$REQUEST_ONE_PID" >/dev/null 2>&1 && echo 1 || echo 0)" >&2
        echo "[$phase_name] curl_two_alive=$(kill -0 "$REQUEST_TWO_PID" >/dev/null 2>&1 && echo 1 || echo 0)" >&2
        echo "[$phase_name] response_one_status_bytes=$(wc -c < "$RESPONSE_ONE.status")" >&2
        echo "[$phase_name] response_two_status_bytes=$(wc -c < "$RESPONSE_TWO.status")" >&2
        echo "[$phase_name] response_one_status=$(sed -n '1,4p' "$RESPONSE_ONE.status")" >&2
        echo "[$phase_name] response_two_status=$(sed -n '1,4p' "$RESPONSE_TWO.status")" >&2
        echo "[$phase_name] Percona/server diagnostics:" >&2
        sed -n '1,160p' "$OBSERVER_LOG" >&2 || true
        if [[ -n "${APP_CONTAINER_ID:-}" ]]; then
            docker top "$APP_CONTAINER_ID" 2>&1 | sed -n '1,80p' >&2 || true
        fi
        return 1
    fi
    sed -n '1,160p' "$OBSERVER_LOG"
    db_waiters=$(sed -n 's/^DB_CONCURRENT_WAITERS=//p' "$OBSERVER_LOG" | tail -n 1)
    if [[ ! "$db_waiters" =~ ^[2-9][0-9]*$ ]] || ! rg -q '^CONCURRENCY_OBSERVED=PASS$' "$OBSERVER_LOG"; then
        release_lock_barrier
        echo "[$phase_name] Observer output did not contain an authoritative PASS marker." >&2
        return 1
    fi
    release_lock_barrier
    echo "[$phase_name] RELEASE_SIGNAL_ISSUED=PASS"
    echo "[$phase_name] SERVER_CONCURRENCY=PASS (two HTTP-backed DB sessions waited concurrently)"

    if wait "$LOCK_PID"; then lock_exit=0; else lock_exit=$?; fi
    LOCK_PID=
    if wait "$REQUEST_ONE_PID"; then request_one_exit=0; else request_one_exit=$?; fi
    REQUEST_ONE_PID=
    if wait "$REQUEST_TWO_PID"; then request_two_exit=0; else request_two_exit=$?; fi
    REQUEST_TWO_PID=

    request_one_http=$(tail -n 1 "$RESPONSE_ONE.status")
    request_two_http=$(tail -n 1 "$RESPONSE_TWO.status")
    request_one_location=$(awk 'tolower($0) ~ /^location:/ { sub(/\r$/, ""); print }' "$RESPONSE_ONE.headers" | tail -n 1)
    request_two_location=$(awk 'tolower($0) ~ /^location:/ { sub(/\r$/, ""); print }' "$RESPONSE_TWO.headers" | tail -n 1)
    request_one_location=${request_one_location:-'<none>'}
    request_two_location=${request_two_location:-'<none>'}
    echo "[$phase_name] request_one_exit=$request_one_exit http=$request_one_http location=$request_one_location"
    echo "[$phase_name] request_two_exit=$request_two_exit http=$request_two_http location=$request_two_location"
    if [[ "$lock_exit" -ne 0 || "$request_one_exit" -ne 0 || "$request_two_exit" -ne 0 ]] \
        || [[ ! "$request_one_http" =~ ^30[123]$ ]] \
        || [[ ! "$request_two_http" =~ ^30[123]$ ]]; then
        echo "[$phase_name] One or more HTTP clients failed." >&2
        return 1
    fi

    active_count=$(run_app "$phase_dir" php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pdo = new PDO(
    "mysql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE"),
    getenv("DB_USERNAME"),
    getenv("DB_PASSWORD"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$rows = $pdo->query("SELECT id, email, active FROM users WHERE is_admin = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "user_id=" . $row["id"] . " email=" . $row["email"] . " active=" . $row["active"] . PHP_EOL;
}
$active = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1 AND active = 1")->fetchColumn();
echo "ACTIVE_COUNT=" . $active . PHP_EOL;
')
    echo "$active_count"
    active_count=$(printf '%s\n' "$active_count" | sed -n 's/^ACTIVE_COUNT=//p' | tail -n 1)
    test -n "$active_count"

    if [[ "$baseline_marker" == 'true' ]]; then
        echo "BASELINE_ACTIVE_ADMIN_COUNT=$active_count"
        if [[ "$active_count" == '0' ]]; then
            echo 'BASELINE_REPRODUCTION=PASS'
        else
            echo 'BASELINE_REPRODUCTION=FAIL'
            return 1
        fi
    fi
    if [[ "$fixed_marker" == 'true' ]]; then
        echo "FIXED_ACTIVE_ADMIN_COUNT=$active_count"
        if (( active_count >= 1 )); then
            echo 'FIXED_INVARIANT=PASS'
        else
            echo 'FIXED_INVARIANT=FAIL'
            return 1
        fi
    fi

    stop_phase
    PHASE_DIR=
    PHASE_URL=
    PHASE_PORT=
    ADMIN_ONE_ID=
    ADMIN_TWO_ID=
}

run_phase baseline "$BASELINE_ROOT" true false
run_phase fixed "$REPO_ROOT" false true

echo 'Deterministic concurrent Percona A/B proof passed.'
