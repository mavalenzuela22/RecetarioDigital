#!/usr/bin/env bash
set -euo pipefail

BRANCH="tsk-016-google-login-invitation-access-admin"
MAIN="main"
BASE_HEAD="7777ee74899e305c13a7dee347f93de8b491283b"
COMMIT_MESSAGE="TSK-016 Google login, invitations, access admin, and Astra remediation handoff"
PR_TITLE="TSK-016 — Google login, invitations, and access administration"
SCRIPT_PATH="docs/operator-actions/TSK-016-operator-assisted-promotion.sh"

die() {
  printf 'ERROR: %s\n' "$*" >&2
  exit 1
}

note() {
  printf '\n==> %s\n' "$*"
}

command -v git >/dev/null 2>&1 || die "git is required"
command -v gh >/dev/null 2>&1 || die "gh is required"
git rev-parse --show-toplevel >/dev/null 2>&1 || die "not inside a git repository"

ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"

REMOTE_URL="$(git remote get-url origin 2>/dev/null || true)"
[[ -n "$REMOTE_URL" ]] || die "origin remote is not configured"
gh auth status >/dev/null 2>&1 || die "gh is not authenticated"

EXPECTED_FILE="$(mktemp)"
ACTUAL_FILE="$(mktemp)"
STAGED_FILE="$(mktemp)"
trap 'rm -f "$EXPECTED_FILE" "$ACTUAL_FILE" "$STAGED_FILE"' EXIT

cat >"$EXPECTED_FILE" <<'EXPECTED_PATHS'
.dev-foundry/execution-contracts/TSK-016-C001-COMPOSER-LOCK-AND-INVITATION-FEEDBACK.json
.dev-foundry/execution-contracts/TSK-016-C002-DETERMINISTIC-SOCIALITE-LOCK-REFRESH.json
.dev-foundry/execution-contracts/TSK-016-C003-PEST-TESTCASE-DEDUPLICATION.json
.dev-foundry/execution-contracts/TSK-016-C004-LEGACY-USER-ACTIVE-DEFAULT.json
.dev-foundry/execution-contracts/TSK-016-C005-SESSION-BOOLEAN-COMPATIBILITY.json
.dev-foundry/execution-contracts/TSK-016-C006-AUTH-TEST-HARNESS-REALIGNMENT.json
.dev-foundry/execution-contracts/TSK-016-C007-ACCESS-ADMIN-TEST-EXPECTATIONS.json
.dev-foundry/execution-contracts/TSK-016-C008-REQUEST-RELOAD-AND-ASSOCIATIVE-ASSERTION.json
.dev-foundry/execution-contracts/TSK-016-GOOGLE-LOGIN-INVITATION-ACCESS-ADMIN.json
.dev-foundry/prompts/CONTINUATION-ASTRA-AUDIT-REMEDIATION.md
.dev-foundry/prompts/TSK-016-C001-COMPOSER-LOCK-AND-INVITATION-FEEDBACK.md
.dev-foundry/prompts/TSK-016-C002-DETERMINISTIC-SOCIALITE-LOCK-REFRESH.md
.dev-foundry/prompts/TSK-016-C003-PEST-TESTCASE-DEDUPLICATION.md
.dev-foundry/prompts/TSK-016-C004-LEGACY-USER-ACTIVE-DEFAULT.md
.dev-foundry/prompts/TSK-016-C005-SESSION-BOOLEAN-COMPATIBILITY.md
.dev-foundry/prompts/TSK-016-C006-AUTH-TEST-HARNESS-REALIGNMENT.md
.dev-foundry/prompts/TSK-016-C007-ACCESS-ADMIN-TEST-EXPECTATIONS.md
.dev-foundry/prompts/TSK-016-C008-REQUEST-RELOAD-AND-ASSOCIATIVE-ASSERTION.md
.dev-foundry/prompts/TSK-016-GOOGLE-LOGIN-INVITATION-ACCESS-ADMIN.md
app/Http/Controllers/AccessAdminController.php
app/Http/Controllers/AuthController.php
app/Http/Controllers/GoogleAuthController.php
app/Http/Controllers/HomeController.php
app/Http/Controllers/InvitationController.php
app/Http/Controllers/SetupController.php
app/Http/Middleware/EnsureActiveUser.php
app/Http/Middleware/EnsureAdmin.php
app/Http/Requests/BootstrapSecretRequest.php
app/Http/Requests/CreateAccessInvitationRequest.php
app/Http/Requests/LoginRequest.php
app/Http/Requests/RecoveryPasswordRequest.php
app/Http/Requests/ToggleUserAccessRequest.php
app/Models/AccessInvitation.php
app/Models/ApplicationBootstrap.php
app/Models/User.php
bootstrap/app.php
composer.json
composer.lock
config/access.php
config/services.php
database/migrations/2026_09_20_000007_add_access_control_to_users_table.php
database/migrations/2026_09_20_000008_create_access_invitations_table.php
database/migrations/2026_09_20_000009_create_application_bootstrap_table.php
docs/ASTRA-ADVERSARIAL-AUDIT-REMEDIATION-BACKLOG-2026-09-20.md
docs/PRODUCTION-DEPLOYMENT-IIS.md
docs/TSK-016-GOOGLE-LOGIN-INVITATION-ACCESS-ADMIN.md
docs/TSK-017-PILOT-BLOCKER-CORRECTNESS-ECONOMIC-TRUST.md
docs/operator-actions/TSK-016-operator-assisted-promotion.sh
e2e/access-admin.spec.ts
e2e/auth.spec.ts
e2e/auth.ts
resources/js/Pages/Admin/Access/Index.tsx
resources/js/Pages/Auth/Invitation.tsx
resources/js/Pages/Auth/Login.tsx
resources/js/Pages/Home.tsx
resources/js/Pages/Setup/Index.tsx
routes/web.php
tests/Feature/AccessAdministrationTest.php
tests/Feature/GoogleAuthTest.php
tests/Feature/IngredientPurchaseTest.php
EXPECTED_PATHS

visible_paths() {
  {
    git diff --name-only HEAD
    git diff --cached --name-only
    git ls-files --others --exclude-standard
  } | sed '/^$/d' | LC_ALL=C sort -u
}

assert_initial_manifest() {
  visible_paths >"$ACTUAL_FILE"
  if ! diff -u "$EXPECTED_FILE" "$ACTUAL_FILE"; then
    die "visible change-set differs from the approved TSK-016 manifest"
  fi
}

assert_clean() {
  [[ -z "$(git status --porcelain=v1 --untracked-files=all)" ]] || die "worktree must be clean at this phase"
}

note "Fetching origin/main for a fresh promotion guard"
git fetch --quiet origin "$MAIN"

CURRENT_BRANCH="$(git branch --show-current)"
CURRENT_HEAD="$(git rev-parse HEAD)"
REMOTE_MAIN_HEAD="$(git rev-parse "origin/$MAIN")"

TASK_COMMIT=""

if [[ "$CURRENT_BRANCH" == "$BRANCH" ]]; then
  if [[ "$CURRENT_HEAD" == "$BASE_HEAD" ]]; then
    [[ "$REMOTE_MAIN_HEAD" == "$BASE_HEAD" ]] || die "origin/main moved from expected base $BASE_HEAD to $REMOTE_MAIN_HEAD; reconcile before promotion"

    note "Validating exact approved dirty-tree manifest"
    assert_initial_manifest

    note "Staging exactly the approved TSK-016 paths"
    APPROVED_PATHS=()
    while IFS= read -r approved_path; do
      APPROVED_PATHS+=("$approved_path")
    done <"$EXPECTED_FILE"
    git add -- "${APPROVED_PATHS[@]}"

    git diff --cached --name-only | LC_ALL=C sort -u >"$STAGED_FILE"
    if ! diff -u "$EXPECTED_FILE" "$STAGED_FILE"; then
      die "staged paths differ from the approved manifest"
    fi

    [[ -z "$(git diff --name-only)" ]] || die "unstaged tracked changes remain after staging"
    [[ -z "$(git ls-files --others --exclude-standard)" ]] || die "untracked files remain after staging"

    note "Running staged diff safety check"
    git diff --cached --check

    note "Creating governed operator-assisted TSK-016 commit"
    git commit -m "$COMMIT_MESSAGE"
    TASK_COMMIT="$(git rev-parse HEAD)"
  else
    assert_clean
    git merge-base --is-ancestor "$BASE_HEAD" "$CURRENT_HEAD" || die "current task branch HEAD is not descended from expected base"
    [[ "$(git rev-list --count "$BASE_HEAD..$CURRENT_HEAD")" == "1" ]] || die "expected exactly one promotion commit above the TSK-016 base"
    [[ "$(git log -1 --format=%s)" == "$COMMIT_MESSAGE" ]] || die "existing task commit subject does not match the operator-assisted promotion commit"
    TASK_COMMIT="$CURRENT_HEAD"
  fi
elif [[ "$CURRENT_BRANCH" == "$MAIN" ]]; then
  assert_clean
  TASK_COMMIT="$(git log "origin/$MAIN" --format='%H%x09%s' --grep="^$COMMIT_MESSAGE$" -n 1 | cut -f1)"
  [[ -n "$TASK_COMMIT" ]] || die "on main, but no integrated TSK-016 operator-assisted commit was found"
else
  die "unexpected branch '$CURRENT_BRANCH'; expected '$BRANCH' or '$MAIN'"
fi

if [[ "$CURRENT_BRANCH" == "$BRANCH" ]]; then
  note "Checking source branch on origin"
  REMOTE_TASK_HEAD="$(git ls-remote --heads origin "refs/heads/$BRANCH" | awk '{print $1}')"
  if [[ -z "$REMOTE_TASK_HEAD" ]]; then
    note "Pushing TSK-016 source branch"
    git push -u origin "$BRANCH"
  elif [[ "$REMOTE_TASK_HEAD" == "$TASK_COMMIT" ]]; then
    note "Remote source branch already matches task commit"
  else
    die "origin/$BRANCH exists at unexpected HEAD $REMOTE_TASK_HEAD"
  fi

  REPO="$(gh repo view --json nameWithOwner --jq '.nameWithOwner')"
  [[ -n "$REPO" ]] || die "could not resolve GitHub repository"

  PR_LINE="$(gh pr list --repo "$REPO" --head "$BRANCH" --base "$MAIN" --state all --limit 20 --json number,state,headRefOid --jq '.[] | [.number,.state,.headRefOid] | @tsv' | awk -F '\t' -v oid="$TASK_COMMIT" '$3 == oid { print $1 "\t" $2; exit }')"

  if [[ -z "$PR_LINE" ]]; then
    note "Creating pull request"
    PR_URL="$(gh pr create \
      --repo "$REPO" \
      --base "$MAIN" \
      --head "$BRANCH" \
      --title "$PR_TITLE" \
      --body $'Operator-assisted promotion of the mechanically validated TSK-016 boundary.\n\nThe Foundry Runner repository-transaction persistence boundary is bypassed only for Git/GitHub lifecycle operations because its generic observed-fact string-array limit rejects this valid >32-path change-set. TSK-016 C008 validation evidence remains authoritative for implementation validation.\n\nIncludes the persisted Astra remediation backlog and TSK-017 governance handoff.' )"
    PR_NUMBER="$(gh pr view "$PR_URL" --repo "$REPO" --json number --jq '.number')"
    PR_STATE="OPEN"
  else
    IFS=$'\t' read -r PR_NUMBER PR_STATE <<<"$PR_LINE"
  fi

  if [[ "$PR_STATE" == "OPEN" ]]; then
    note "Merging pull request #$PR_NUMBER"
    gh pr merge "$PR_NUMBER" --repo "$REPO" --merge
  elif [[ "$PR_STATE" == "MERGED" ]]; then
    note "Pull request #$PR_NUMBER is already merged"
  else
    die "matching pull request #$PR_NUMBER is closed without merge"
  fi
fi

note "Reconciling local main with origin"
git fetch --quiet origin "$MAIN"
git merge-base --is-ancestor "$TASK_COMMIT" "origin/$MAIN" || die "task commit is not integrated into origin/main"

if [[ "$(git branch --show-current)" != "$MAIN" ]]; then
  git switch "$MAIN"
fi
git pull --ff-only origin "$MAIN"
git merge-base --is-ancestor "$TASK_COMMIT" "$MAIN" || die "task commit is not integrated into local main"
assert_clean

note "Cleaning up TSK-016 branches"
if git show-ref --verify --quiet "refs/heads/$BRANCH"; then
  git branch -d "$BRANCH"
fi

REMOTE_TASK_HEAD="$(git ls-remote --heads origin "refs/heads/$BRANCH" | awk '{print $1}')"
if [[ -n "$REMOTE_TASK_HEAD" ]]; then
  git push origin --delete "$BRANCH"
fi

git fetch --quiet --prune origin
assert_clean

FINAL_HEAD="$(git rev-parse HEAD)"
REMOTE_FINAL_HEAD="$(git rev-parse "origin/$MAIN")"
[[ "$FINAL_HEAD" == "$REMOTE_FINAL_HEAD" ]] || die "local main and origin/main differ after reconciliation"

printf '\nRESULT=PASS\n'
printf 'TASK_COMMIT=%s\n' "$TASK_COMMIT"
printf 'MAIN_HEAD=%s\n' "$FINAL_HEAD"
printf 'BRANCH=%s\n' "$(git branch --show-current)"
printf 'WORKTREE_CLEAN=true\n'
printf 'TSK016_IN_MAIN=true\n'
