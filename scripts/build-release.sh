#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
OUTPUT_DIR="$REPO_ROOT/artifacts/releases"
ALLOW_DIRTY=0

usage() {
    cat <<'EOF'
Usage: bash scripts/build-release.sh [--allow-dirty] [--output-dir DIRECTORY]

Build a production Laravel/IIS ZIP, checksum, and release manifest.
EOF
}

fail() {
    printf 'build-release: %s\n' "$1" >&2
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

copy_file() {
    local relative_path="$1"
    local source="$REPO_ROOT/$relative_path"
    local target="$STAGE/$relative_path"

    [[ -f "$source" ]] || fail "Required source file is missing: $relative_path"
    mkdir -p "$(dirname "$target")"
    cp "$source" "$target"
}

copy_directory() {
    local relative_path="$1"
    local source="$REPO_ROOT/$relative_path"
    local target="$STAGE/$relative_path"

    [[ -d "$source" ]] || fail "Required source directory is missing: $relative_path"
    mkdir -p "$target"
    cp -R "$source/." "$target/"
}

while (($# > 0)); do
    case "$1" in
        --allow-dirty)
            ALLOW_DIRTY=1
            shift
            ;;
        --output-dir)
            (($# >= 2)) || fail '--output-dir requires a directory.'
            OUTPUT_DIR="$2"
            shift 2
            ;;
        --help|-h)
            usage
            exit 0
            ;;
        *)
            usage >&2
            fail "Unknown argument: $1"
            ;;
    esac
done

if [[ "$OUTPUT_DIR" != /* ]]; then
    OUTPUT_DIR="$REPO_ROOT/$OUTPUT_DIR"
fi
mkdir -p "$OUTPUT_DIR"
OUTPUT_DIR="$(cd "$OUTPUT_DIR" && pwd)"

cd "$REPO_ROOT"
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || fail 'Repository root is not a Git worktree.'

if ((ALLOW_DIRTY == 0)); then
    if ! git diff --quiet --ignore-submodules -- || ! git diff --cached --quiet --; then
        fail 'Tracked worktree changes detected. Commit or use --allow-dirty only for governed validation.'
    fi
fi

command -v npm >/dev/null 2>&1 || fail 'npm is required to build frontend assets.'
command -v zip >/dev/null 2>&1 || fail 'zip is required to create the release archive.'

RELEASE_SHA="$(git rev-parse HEAD)"
RELEASE_SHORT_SHA="$(git rev-parse --short=12 HEAD)"
RELEASE_NAME="emprendimientoos-${RELEASE_SHA}"
BUILD_STARTED_AT="$(date -u '+%Y-%m-%dT%H:%M:%SZ')"

printf '%s\n' 'Installing clean frontend dependencies and building production assets...'
npm ci
npm run build

printf '%s\n' 'Installing production Composer dependencies...'
if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
elif command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    docker compose run --rm app composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
else
    fail 'Composer is required directly or through docker compose.'
fi

[[ -f public/build/manifest.json ]] || fail 'Frontend build did not produce public/build/manifest.json.'
[[ -f vendor/autoload.php ]] || fail 'Composer install did not produce vendor/autoload.php.'
[[ ! -e public/storage ]] || fail 'public/storage must not exist; runtime storage must remain outside the public document root.'

TEMP_ROOT="$(mktemp -d "${TMPDIR:-/tmp}/emprendimientoos-release.XXXXXX")"
STAGE="$TEMP_ROOT/$RELEASE_NAME"
mkdir -p "$STAGE"
trap 'rm -rf "$TEMP_ROOT"' EXIT

copy_directory app
copy_file bootstrap/app.php
copy_file bootstrap/providers.php
copy_directory config
copy_directory database
copy_directory public
copy_directory resources/views
copy_directory routes
copy_file artisan
copy_file composer.json
copy_file composer.lock
copy_directory vendor

# These directories are intentionally empty in the package. IIS/PHP must be
# granted write access to them, while their persistent/runtime contents remain
# outside the release archive.
mkdir -p \
    "$STAGE/storage/app/private" \
    "$STAGE/storage/app/public" \
    "$STAGE/storage/framework/cache/data" \
    "$STAGE/storage/framework/sessions" \
    "$STAGE/storage/framework/views" \
    "$STAGE/storage/logs" \
    "$STAGE/bootstrap/cache"

ZIP_PATH="$OUTPUT_DIR/$RELEASE_NAME.zip"
CHECKSUM_PATH="$OUTPUT_DIR/$RELEASE_NAME.sha256"
MANIFEST_PATH="$OUTPUT_DIR/$RELEASE_NAME.manifest.txt"
rm -f "$ZIP_PATH" "$CHECKSUM_PATH" "$MANIFEST_PATH"

printf '%s\n' 'Creating release archive...'
(cd "$STAGE" && zip -qr "$ZIP_PATH" .)

ZIP_SHA256="$(sha256_file "$ZIP_PATH")"
printf '%s  %s\n' "$ZIP_SHA256" "$(basename "$ZIP_PATH")" > "$CHECKSUM_PATH"

cat > "$MANIFEST_PATH" <<EOF
EmprendimientoOS production release
===================================
Release commit: $RELEASE_SHA
Release short SHA: $RELEASE_SHORT_SHA
Built at (UTC): $BUILD_STARTED_AT
Archive: $(basename "$ZIP_PATH")
SHA-256: $ZIP_SHA256

Runtime target
--------------
IIS document root: public/
PHP: 8.3
Database target: MySQL-compatible Percona Server 8.4
Node.js runtime: not required on production

Package boundary
----------------
Includes application PHP source, production vendor/, Laravel migrations,
Blade runtime views, public/web.config, compiled public/build/, and PWA assets.
Excludes environment files, secrets, Git/governance/tooling source, tests,
development containers, frontend source/tooling, local caches/logs/sessions,
database files, and mutable application data.

Persistent writable runtime directories
---------------------------------------
storage/app/private
storage/app/public
storage/framework/cache/data
storage/framework/sessions
storage/framework/views
storage/logs
bootstrap/cache

The directories above are deployment/runtime boundaries. Their contents are
not included in this release and must be preserved or provisioned outside the
archive according to docs/PRODUCTION-DEPLOYMENT-IIS.md.
EOF

printf 'Release ZIP: %s\nChecksum: %s\nManifest: %s\n' "$ZIP_PATH" "$CHECKSUM_PATH" "$MANIFEST_PATH"
