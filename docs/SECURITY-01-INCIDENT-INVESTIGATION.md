# SECURITY-01 INCIDENT INVESTIGATION

## Reproduction

Fresh unauthenticated reproduction against the live app (`http://127.0.0.1:8091`, clean cookie jar, no pre-existing session, CSRF token obtained from the login page in the same session):

| # | Credentials | HTTP | PHPSESSID regenerated | Result |
|---|-------------|------|-----------------------|--------|
| A | `superadmin` / `admin1234` | 302 → /dashboard | yes (new session) | AUTHENTICATED |
| B | `superadmin` / `admin123` | 200, generic error | no | REJECTED |
| C | `superadmin` / `definitely-wrong-xyz` | 200, generic error | no | REJECTED |
| D | `admin` / `admin1234` | 200, generic error | no | REJECTED (workspace-only after final decision) |
| E | `no_such_user` / `admin1234` | 200, generic error | no | REJECTED |

Scenario A proves the login creates a **brand-new authenticated session** (session ID was regenerated, i.e., the anti-fixation control in `CommanderAuthContext::login()` fired). It is not a pre-existing authenticated session being reused.

## Actual Authentication Flow

```
POST /site/login
  → SiteController::actionLogin()                         (controllers/SiteController.php:338)
  → SiteController::handleCommanderLoginPost()            (controllers/SiteController.php:367)
      reads username/password (top-level or LoginForm[...])
      guard: username must be `superadmin`, else generic error (no DB check)
  → CommanderLoginLimiter::isLocked()                     → null (not blocked)
  → LoginForm::login()                                    (models/LoginForm.php)
      → Model::validate()
        → LoginForm::validatePassword()                   (models/LoginForm.php:42)
          → LoginForm::getUser()
            → maps `superadmin` → User::findByUsername('admin')
            → resolves to the real `admin` row (id=1)
          → User::validatePassword('admin1234')           (models/User.php:144)
            → Yii::$app->security->validatePassword('admin1234', stored_hash)
            → true
      → Yii::$app->user->login($user)
  → CommanderAuthContext::login($user)                    (components/CommanderAuthContext.php:16)
      → session keys written (canonical username `superadmin`)
      → regenerateID(true)  → NEW session issued
  → redirect → /dashboard
```

The full path reaches the real password-verification code. No other controller, service, middleware, behavior, or bootstrap runs before it on this route.

## superadmin Resolution

`User::findByUsername('superadmin')` (`models/User.php`): exact-match only → **null** (no `superadmin` row exists; only `admin` is in the table). The canonical Commander username is mapped in the Commander login path only: `LoginForm::getUser()` (`models/LoginForm.php:90`) maps `superadmin` → `User::findByUsername('admin')` → **row id=1, username=`admin`, role=`superadmin`, status=1**. `SiteController::handleCommanderLoginPost()` further guards that only the `superadmin` username may proceed through the gateway.

## Database Password Verification

Read-only verification (no database changes):

- username row resolved: `id=1`, `username=admin`, `role=superadmin`, `status=1`
- stored hash metadata: bcrypt (`$2y$13$…`), cost = 13, hash length = 60 (valid bcrypt format)
- `passwordMatchesDatabaseHash('admin1234') = true`
- `passwordMatchesDatabaseHash('admin123')  = false`
- `passwordMatchesDatabaseHash('admin12345') = false`
- `passwordMatchesDatabaseHash('definitely-wrong-xyz') = false`

The stored hash for the `admin` row **genuinely validates `admin1234`**.

## Why superadmin/admin1234 Works

`admin1234` is the real password of the `admin` Commander account. The stored bcrypt hash in the `users` table matches it, so authentication succeeds through the normal database-backed path — there is no special casing of `admin1234` anywhere.

Note that the pre-SECURITY-01 code's hardcoded backdoor accepted exactly `admin123` (with no DB check). The current stored hash does **not** match `admin123`, which proves the row's password was set to something else after seeding — and that something is `admin1234`. The SECURITY-01 claim ("hardcoded `superadmin`/`admin123` no longer works") remains **true**: scenario B rejects `admin123`. The manual observation tested a *different* credential (`admin1234`), which is simply the account's actual password.

## Is This a Backdoor?

**NO**

- `admin1234` does not appear anywhere in application code (grep across `controllers/`, `components/`, `models/`, `config/`, `web/`, `commands/`: zero matches for `admin1234`).
- The only credential literals found are `admin123` in seed/bootstrap code (`components/DatabaseSchemaInitializer.php:987`, `migrations/m240101_000001_create_users_table.php:21`) — seed-time only, not a runtime path, and it does not even match the current hash.
- No literal password comparisons, no fallback/shortcut branches, no env-based credentials, no `$_SESSION`-write auth shortcuts outside `CommanderAuthContext::login()` (the normal path).
- Authentication flows exclusively through `LoginForm::login()` → `User::validatePassword()` → bcrypt verification against the DB hash. A wrong password (C), `admin123` (B), and unknown users (E) are all rejected.
- Session regeneration on success proves a genuine new authenticated session via the normal flow.

## Evidence

1. `passwordMatchesDatabaseHash('admin1234') = true`, `passwordMatchesDatabaseHash('admin123') = false` (bcrypt, cost 13).
2. Live HTTP (final state): `superadmin/admin1234` → 302 + new PHPSESSID; `admin/admin1234` and `admin/admin123` → 200 generic error (rejected at Commander); `superadmin/admin123` and wrong passwords → 200 generic error, no session change.
3. Audit log (`runtime/logs/login-debug.log`) records the successful attempts through the SECURITY-01 path:
   `username_input=superadmin  password_valid=true  commander_username=admin  commander_role=superadmin  redirect=/dashboard`
   (`logCommanderLoginAttempt` is called only from `handleCommanderLoginPost`, the post-fix path.)
4. `git status`/`git diff` confirm the working tree still matches the SECURITY-01 deliverable (same 5 modified files + new `CommanderLoginLimiter.php` + report). No regression, no reverted file, no added bypass.

## Files Inspected

- `controllers/SiteController.php` (actionLogin, handleCommanderLoginPost, logCommanderLoginAttempt)
- `models/LoginForm.php` (validatePassword, login, getUser, DUMMY_HASH)
- `models/User.php` (findByUsername, validatePassword)
- `components/CommanderAuthContext.php` (login, normalizeRole, isAuthenticated)
- `components/CommanderLoginLimiter.php`
- `config/web.php`, `config/db.php`, `config/console.php`, `.env` / `.env.example`
- `components/DatabaseSchemaInitializer.php`, `migrations/m240101_000001_create_users_table.php`
- `controllers/ProjectController.php` (workspace login — separate feature, not involved)
- `runtime/logs/login-debug.log`

## Database State Inspected

Read-only queries against MySQL `yii2basic`:

- `users` table contains a single row: id=1, `admin`, role=`superadmin`, status=1, bcrypt hash (cost 13).
- `superadmin` is mapped to that same row by `LoginForm::getUser()` (Commander path only); `User::findByUsername()` itself is exact-match.
- The seed password `admin123` does not match the stored hash, so the hash was changed after seeding; the current hash matches `admin1234`.

No database data was modified during this investigation.

## Security Impact

- The hardcoded backdoor removal from SECURITY-01 is intact and effective (`admin123` is rejected).
- `superadmin`/`admin1234` succeeding is **normal, expected database authentication** — the account's real password works. Impact: none beyond the known risk that `admin1234` is the account's actual password.
- Action for the account owner: this is effectively the real Commander password. If the owner did not intend `admin1234` to be the password, it should be changed; otherwise no action is required.

## Recommended Fix

No code fix is required — the behavior is correct authentication.

Recommended hygiene actions (account-side, not code):
1. Confirm the intended Commander password is known to the owner. If `admin1234` was unexpected, change the `admin` password to a strong unique value.
2. Change it regardless as good practice: a sequential `admin1234`-style password is weak and easily guessed. After changing, `superadmin/admin1234` will stop working, matching the original SECURITY-01 expectation.

## Changes Made

**NONE** (investigation only — no code, database, configuration, or credential data was modified during this investigation).

## Follow-up (owner decision, 2026-08-18 — final state)

After this investigation, the owner chose (reversing the intermediate "remove the alias / only `admin`" step) that the Commander is signed in with the **canonical username `superadmin`**, while the workspace-default `admin` account is **not** accepted at the Commander gateway. Implemented without touching workspace login:

- `User::findByUsername()` stays exact-match only.
- `LoginForm::getUser()` maps `superadmin` → the single `users` row `admin` (backing account).
- `SiteController::handleCommanderLoginPost()` rejects any username !== `superadmin` with the generic error (no DB check) and, on success, writes the canonical `superadmin` session username.

Verified live (HTTP, port 8091): `superadmin/admin1234` → 302 authenticated (new session); `admin/admin1234` and `admin/admin123` → 200 generic error (rejected at Commander); commander `superadmin` freely enters every workspace via `/project-list/select/<id>` → `/dashboard` (superadmin mode, no workspace login wall). Console suite 51/51. This is the final state; it supersedes the intermediate alias-removal decision.
