#!/usr/bin/env bash

set -euo pipefail

fail() {
    printf 'smoke-release: %s\n' "$1" >&2
    exit 1
}

[[ $# -eq 1 ]] || fail 'Usage: bash scripts/smoke-release.sh https://production.example.test'
BASE_URL="${1%/}"
case "$BASE_URL" in
    http://*|https://*) ;;
    *) fail 'Base URL must use http:// or https://.' ;;
esac

command -v curl >/dev/null 2>&1 || fail 'curl is required.'

get_status() {
    curl --silent --show-error --max-time 20 -o /dev/null -w '%{http_code}' "$1"
}

expect_status_200() {
    local path="$1"
    local status
    status="$(get_status "$BASE_URL$path")"
    [[ "$status" == 200 ]] || fail "GET $path returned HTTP $status; expected 200."
}

expect_guest_redirect() {
    local path="$1"
    local response status redirect_url
    response="$(curl --silent --show-error --max-time 20 -o /dev/null -w '%{http_code}\t%{redirect_url}' "$BASE_URL$path")"
    status="${response%%$'\t'*}"
    redirect_url="${response#*$'\t'}"

    case "$status" in
        301|302|303|307|308) ;;
        *) fail "GET $path returned HTTP $status; expected a guest redirect." ;;
    esac
    [[ "$redirect_url" == *"/login"* ]] || fail "GET $path did not redirect to /login (target: $redirect_url)."
}

expect_status_200 /up
expect_status_200 /login
expect_guest_redirect /recetas
expect_status_200 /manifest.webmanifest
expect_status_200 /sw.js
expect_status_200 /favicon.ico
expect_status_200 /icons/app-icon-180.png
expect_status_200 /icons/app-icon-192.png
expect_status_200 /icons/app-icon-512.png
expect_status_200 /icons/maskable-512.png

printf 'Release smoke checks passed for %s\n' "$BASE_URL"
