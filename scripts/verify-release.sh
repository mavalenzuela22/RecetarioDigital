#!/usr/bin/env bash

set -euo pipefail

fail() {
    printf 'verify-release: %s\n' "$1" >&2
    exit 1
}

sha256_file() {
    if command -v shasum >/dev/null 2>&1; then
        shasum -a 256 "$1" | awk '{print $1}'
    elif command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$1" | awk '{print $1}'
    else
        fail 'shasum or sha256sum is required.'
    fi
}

[[ $# -eq 1 ]] || fail 'Usage: bash scripts/verify-release.sh PATH_TO_RELEASE_ZIP'
ZIP_PATH="$1"
[[ -f "$ZIP_PATH" ]] || fail "ZIP does not exist: $ZIP_PATH"

case "$ZIP_PATH" in
    *.zip) ;;
    *) fail 'The input must be a .zip release archive.' ;;
esac

ZIP_DIR="$(cd "$(dirname "$ZIP_PATH")" && pwd)"
ZIP_PATH="$ZIP_DIR/$(basename "$ZIP_PATH")"
CHECKSUM_PATH="${ZIP_PATH%.zip}.sha256"
[[ -f "$CHECKSUM_PATH" ]] || fail "Adjacent checksum file is missing: $CHECKSUM_PATH"

EXPECTED_SHA256="$(awk 'NR == 1 {print $1}' "$CHECKSUM_PATH")"
ACTUAL_SHA256="$(sha256_file "$ZIP_PATH")"
[[ "$EXPECTED_SHA256" == "$ACTUAL_SHA256" ]] || fail 'Release ZIP checksum does not match its adjacent checksum file.'

command -v unzip >/dev/null 2>&1 || fail 'unzip is required to inspect a release archive.'
command -v zipinfo >/dev/null 2>&1 || fail 'zipinfo is required to inspect a release archive.'

ENTRIES="$(zipinfo -1 "$ZIP_PATH")" || fail 'Could not list release ZIP entries.'
while IFS= read -r entry; do
    [[ -n "$entry" ]] || continue

    case "$entry" in
        /*|.|..|../*|*/../*|*/..|./*|*/./*)
            fail "Unsafe path in release ZIP: $entry"
            ;;
        .env|.env.*|*/.env|*/.env.*)
            fail "Environment file is packaged: $entry"
            ;;
        .git|.git/*|.dev-foundry|.dev-foundry/*|node_modules|node_modules/*)
            fail "Development/governance directory is packaged: $entry"
            ;;
        tests|tests/*|e2e|e2e/*|coverage|coverage/*|playwright-report|playwright-report/*|test-results|test-results/*|reports|reports/*)
            fail "Test/report artifact is packaged: $entry"
            ;;
        Dockerfile|docker-compose.yml|docker-compose.yaml|compose.yml|compose.yaml|package.json|package-lock.json|vite.config.*|tsconfig*.json|phpunit.xml|postcss.config.*|tailwind.config.*|eslint.config.*)
            fail "Development/tooling file is packaged: $entry"
            ;;
        *.sqlite|*.sqlite-*|*.db)
            fail "Local database file is packaged: $entry"
            ;;
        bootstrap/cache/*.php)
            fail "Build-machine Laravel cache is packaged: $entry"
            ;;
        public/storage|public/storage/*)
            fail "Runtime storage is exposed under the public document root: $entry"
            ;;
        storage/app/private|storage/app/private/|storage/app/public|storage/app/public/|storage/framework/cache/data|storage/framework/cache/data/|storage/framework/sessions|storage/framework/sessions/|storage/framework/views|storage/framework/views/|storage/logs|storage/logs/)
            ;;
        storage/app/private/*|storage/app/public/*|storage/framework/cache/data/*|storage/framework/sessions/*|storage/framework/views/*|storage/logs/*)
            fail "Mutable runtime data is packaged: $entry"
            ;;
        storage/framework/testing|storage/framework/testing/*)
            fail "Testing storage is packaged: $entry"
            ;;
    esac
done <<< "$ENTRIES"

TEMP_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/emprendimientoos-verify.XXXXXX")"
trap 'rm -rf "$TEMP_ROOT"' EXIT
unzip -q "$ZIP_PATH" -d "$TEMP_ROOT/release" || fail 'Could not extract release ZIP.'
ROOT="$TEMP_ROOT/release"

required_files=(
    artisan
    vendor/autoload.php
    public/index.php
    public/web.config
    public/build/manifest.json
    public/manifest.webmanifest
    public/sw.js
    public/favicon.ico
    public/icons/app-icon-180.png
    public/icons/app-icon-192.png
    public/icons/app-icon-512.png
    public/icons/maskable-512.png
    resources/views/app.blade.php
)
for relative_path in "${required_files[@]}"; do
    [[ -f "$ROOT/$relative_path" ]] || fail "Required release file is missing: $relative_path"
done

required_directories=(
    app
    bootstrap
    bootstrap/cache
    config
    database/migrations
    public
    public/build
    resources
    resources/views
    routes
    storage/app/private
    storage/app/public
    storage/framework/cache/data
    storage/framework/sessions
    storage/framework/views
    storage/logs
)
for relative_path in "${required_directories[@]}"; do
    [[ -d "$ROOT/$relative_path" ]] || fail "Required release directory is missing: $relative_path"
done

if find "$ROOT" -type l -print -quit | grep -q .; then
    fail 'Release contains a symlink; deployment must use explicit files and directories.'
fi

[[ -z "$(find "$ROOT/storage/app/private" -mindepth 1 -print -quit)" ]] || fail 'Private runtime storage is not empty.'
[[ -z "$(find "$ROOT/storage/app/public" -mindepth 1 -print -quit)" ]] || fail 'Public runtime storage is not empty.'
[[ -z "$(find "$ROOT/storage/framework/cache/data" -mindepth 1 -print -quit)" ]] || fail 'Framework cache data is not empty.'
[[ -z "$(find "$ROOT/storage/framework/sessions" -mindepth 1 -print -quit)" ]] || fail 'Framework sessions are not empty.'
[[ -z "$(find "$ROOT/storage/framework/views" -mindepth 1 -print -quit)" ]] || fail 'Compiled views are not empty.'
[[ -z "$(find "$ROOT/storage/logs" -mindepth 1 -print -quit)" ]] || fail 'Local logs are not packaged.'
[[ -z "$(find "$ROOT/bootstrap/cache" -type f -name '*.php' -print -quit)" ]] || fail 'Compiled Laravel cache is not empty.'

printf 'Verified release: %s\nSHA-256: %s\n' "$(basename "$ZIP_PATH")" "$ACTUAL_SHA256"
