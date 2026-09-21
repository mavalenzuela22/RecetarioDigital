#!/usr/bin/env bash
set -euo pipefail

BRANCH="tsk-017-pilot-blocker-correctness-economic-trust"
MAIN="main"
BASE_HEAD="bbabd266be69915391c54ddca5bc906d773bba57"
COMMIT_MESSAGE="TSK-017 pilot blocker correctness and economic trust"
PR_TITLE="TSK-017 — Pilot blocker correctness and economic trust"
MANIFEST="docs/operator-actions/TSK-017-approved-paths.txt"

die(){ printf 'ERROR: %s\n' "$*" >&2; exit 1; }
note(){ printf '\n==> %s\n' "$*"; }

command -v git >/dev/null 2>&1 || die "git is required"
command -v gh >/dev/null 2>&1 || die "gh is required"
git rev-parse --show-toplevel >/dev/null 2>&1 || die "not inside a git repository"
ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"
[[ -f "$MANIFEST" ]] || die "approved manifest missing"
gh auth status >/dev/null 2>&1 || die "gh is not authenticated"

TMP_ACTUAL="$(mktemp)"
TMP_STAGED="$(mktemp)"
trap 'rm -f "$TMP_ACTUAL" "$TMP_STAGED"' EXIT

visible_paths(){
  { git diff --name-only HEAD; git diff --cached --name-only; git ls-files --others --exclude-standard; } |
    sed '/^$/d' | LC_ALL=C sort -u
}
assert_clean(){ [[ -z "$(git status --porcelain=v1 --untracked-files=all)" ]] || die "worktree must be clean"; }

note "Fetching origin/main"
git fetch --quiet origin "$MAIN"
CURRENT_BRANCH="$(git branch --show-current)"
CURRENT_HEAD="$(git rev-parse HEAD)"
REMOTE_MAIN_HEAD="$(git rev-parse "origin/$MAIN")"
TASK_COMMIT=""

if [[ "$CURRENT_BRANCH" == "$BRANCH" ]]; then
  if [[ "$CURRENT_HEAD" == "$BASE_HEAD" ]]; then
    [[ "$REMOTE_MAIN_HEAD" == "$BASE_HEAD" ]] || die "origin/main moved to $REMOTE_MAIN_HEAD"
    visible_paths >"$TMP_ACTUAL"
    diff -u "$MANIFEST" "$TMP_ACTUAL" || die "visible change-set differs from approved TSK-017 manifest"

    APPROVED=()
    while IFS= read -r approved_path; do
      [[ -n "$approved_path" ]] && APPROVED+=("$approved_path")
    done <"$MANIFEST"
    git add -- "${APPROVED[@]}"
    git diff --cached --name-only | LC_ALL=C sort -u >"$TMP_STAGED"
    diff -u "$MANIFEST" "$TMP_STAGED" || die "staged paths differ from approved manifest"
    [[ -z "$(git diff --name-only)" ]] || die "unstaged tracked changes remain"
    [[ -z "$(git ls-files --others --exclude-standard)" ]] || die "untracked files remain"
    git diff --cached --check

    note "Creating TSK-017 commit"
    git commit -m "$COMMIT_MESSAGE"
    TASK_COMMIT="$(git rev-parse HEAD)"
  else
    assert_clean
    git merge-base --is-ancestor "$BASE_HEAD" "$CURRENT_HEAD" || die "task head not descended from base"
    [[ "$(git rev-list --count "$BASE_HEAD..$CURRENT_HEAD")" == "1" ]] || die "expected one promotion commit"
    [[ "$(git log -1 --format=%s)" == "$COMMIT_MESSAGE" ]] || die "unexpected existing commit subject"
    TASK_COMMIT="$CURRENT_HEAD"
  fi
elif [[ "$CURRENT_BRANCH" == "$MAIN" ]]; then
  assert_clean
  TASK_COMMIT="$(git log "origin/$MAIN" --format='%H%x09%s' --grep="^$COMMIT_MESSAGE$" -n 1 | cut -f1)"
  [[ -n "$TASK_COMMIT" ]] || die "integrated TSK-017 commit not found"
else
  die "unexpected branch $CURRENT_BRANCH"
fi

if [[ "$CURRENT_BRANCH" == "$BRANCH" ]]; then
  REMOTE_TASK_HEAD="$(git ls-remote --heads origin "refs/heads/$BRANCH" | awk '{print $1}')"
  if [[ -z "$REMOTE_TASK_HEAD" ]]; then
    git push -u origin "$BRANCH"
  elif [[ "$REMOTE_TASK_HEAD" != "$TASK_COMMIT" ]]; then
    die "origin task branch at unexpected head $REMOTE_TASK_HEAD"
  fi

  REPO="$(gh repo view --json nameWithOwner --jq '.nameWithOwner')"
  PR_LINE="$(gh pr list --repo "$REPO" --head "$BRANCH" --base "$MAIN" --state all --limit 20 --json number,state,headRefOid --jq '.[] | [.number,.state,.headRefOid] | @tsv' | awk -F '\t' -v oid="$TASK_COMMIT" '$3 == oid { print $1 "\t" $2; exit }')"

  if [[ -z "$PR_LINE" ]]; then
    PR_URL="$(gh pr create --repo "$REPO" --base "$MAIN" --head "$BRANCH" --title "$PR_TITLE" --body "Operator-assisted promotion of validated TSK-017 after Foundry Runner repository transaction failed before side effects with validation_service_failed. C004+C005 remain validation authority.")"
    PR_NUMBER="$(gh pr view "$PR_URL" --repo "$REPO" --json number --jq '.number')"
    PR_STATE="OPEN"
  else
    IFS=$'\t' read -r PR_NUMBER PR_STATE <<<"$PR_LINE"
  fi

  if [[ "$PR_STATE" == "OPEN" ]]; then
    gh pr merge "$PR_NUMBER" --repo "$REPO" --merge
  elif [[ "$PR_STATE" != "MERGED" ]]; then
    die "matching PR is closed without merge"
  fi
fi

git fetch --quiet origin "$MAIN"
git merge-base --is-ancestor "$TASK_COMMIT" "origin/$MAIN" || die "task commit not in origin/main"
[[ "$(git branch --show-current)" == "$MAIN" ]] || git switch "$MAIN"
git pull --ff-only origin "$MAIN"
git merge-base --is-ancestor "$TASK_COMMIT" "$MAIN" || die "task commit not in local main"
assert_clean

if git show-ref --verify --quiet "refs/heads/$BRANCH"; then git branch -d "$BRANCH"; fi
REMOTE_TASK_HEAD="$(git ls-remote --heads origin "refs/heads/$BRANCH" | awk '{print $1}')"
[[ -z "$REMOTE_TASK_HEAD" ]] || git push origin --delete "$BRANCH"
git fetch --quiet --prune origin
assert_clean

FINAL_HEAD="$(git rev-parse HEAD)"
[[ "$FINAL_HEAD" == "$(git rev-parse "origin/$MAIN")" ]] || die "main and origin/main differ"

printf '\nRESULT=PASS\nTASK_COMMIT=%s\nMAIN_HEAD=%s\nBRANCH=%s\nWORKTREE_CLEAN=true\nTSK017_IN_MAIN=true\n'   "$TASK_COMMIT" "$FINAL_HEAD" "$(git branch --show-current)"
