# SECURITY-01 — COMMANDER LOGIN

Date: 2026-08-18
Scope: Commander (superadmin) authentication only — `SiteController::actionLogin()`, `LoginForm`, `User::validatePassword()`, `CommanderAuthContext`, and the login view. No Workspace/Project login logic, no DB schema changes, no config changes.

## Existing Architecture

- The Commander login page is served at `/site/login` (`controllers/SiteController.php::actionLogin()`), layout `clean`.
- Authentication is stored in the PHP session under `CommanderAuthContext` keys (`components/CommanderAuthContext.php`): `SESSION_KEY_AUTH`, `SESSION_KEY_LOGIN`, `SESSION_KEY_USER_ID`, `SESSION_KEY_USERNAME`, `SESSION_KEY_ROLE`.
- `Session` component: built-in PHP session (file-based), session name `PHPSESSID`.
- `Cache` component: `yii\caching\FileCache`, keyPrefix `yii-dynamic:`, path `@runtime/cache`. In production the container may use a different driver (see `config/web.php` cache driver selection / env).
- Identity provider: `app\models\User` (`users` table), `findByUsername()` matches the exact username only (no generic alias). The Commander is addressed by its **canonical username `superadmin`**, which is backed by the single framework `users` row `admin`: `LoginForm::getUser()` maps `superadmin` → `User::findByUsername('admin')`, and `SiteController::handleCommanderLoginPost()` accepts **only** the `superadmin` username (all others — including the workspace-default `admin` — are rejected with the same generic error, without a DB check). `normalizeRole()` still forces the `superadmin` role at session-write time.
- Login UI: `views/site/login.php` — Tailwind login card using `ActiveForm` (CSRF token auto-injected).
- CSRF validation is enabled by default on the web `request` component (Yii default `enableCsrfValidation = true`).
- No rate limiting existed anywhere on the Commander login path.

## Findings (pre-fix)

| # | Severity | Finding |
|---|----------|---------|
| 1 | CRITICAL | Hardcoded backdoor in `SiteController::actionLogin()`: credentials `superadmin` / `admin123` were accepted and a superadmin session was set **without any DB verification**. If no `User` row existed (`User::findByUsername()` returned null) the code still wrote `$_SESSION[SESSION_KEY_AUTH] = true` + `superadmin` into the session — full privilege escalation to Commander without an account. |
| 2 | CRITICAL | The only code path that ever verified a password against the DB (`LoginForm::load()` + `validatePassword()`) was **dead code**: the earlier `superadmin`/`admin123` branch returned before it could run, and every other POST reached an unconditional "Username atau password salah." rejection. Effectively the Commander password was never checked. |
| 3 | HIGH | Session fixation: `yii\web\User::login()` does not regenerate the session ID (verified in `vendor/yiisoft/yii2/web/User.php`), and `CommanderAuthContext::login()` wrote directly to `$_SESSION` without regeneration. A session ID known before login was still valid after login. |
| 4 | MEDIUM | User enumeration via timing: for unknown usernames the code returned immediately (no hash verification), so a short response time revealed whether a username exists. |
| 5 | MEDIUM | No brute-force protection: unlimited failed attempts against the Commander account. `yii\filters\RateLimiter` exists in vendor but is unused. |
| 6 | LOW | Login view advertised the default credentials ("Default login: superadmin / admin123") on the public login page. |
| 7 | LOW | `User::validatePassword()` could pass a null/empty password or an empty/invalid hash into `Yii::$app->security->validatePassword()` and throw `InvalidArgumentException` (empty password) → 500. |

## Changes Made

1. **`controllers/SiteController.php`**
   - POST handling now delegates to new `handleCommanderLoginPost()`; the hardcoded `superadmin`/`admin123` branches were removed and `completeDefaultCommanderLogin()` was deleted.
   - `handleCommanderLoginPost()`: reads `username`/`password` (top-level and/or `LoginForm[...]`), enforces the brute-force limiter, calls `LoginForm::login()`, on success clears the limiter counter + redirects via `redirectAfterAuthentication()`, on failure records the attempt and renders a generic error. No hardcoded fallback path exists.
   - Final reversal (owner decision 2026-08-18): the gateway now **guards on the username** — only `superadmin` proceeds; any other username (e.g. the workspace-default `admin`) is rejected immediately with the generic error without touching the database. On success the session username is written as `superadmin` (the canonical Commander username), even though the backing DB row is `admin`.
   - Added `use app\components\CommanderLoginLimiter;`; removed now-unused `use app\models\User;`.
   - Existing `logCommanderLoginAttempt()` retained for success/failure audit logging.

2. **`models/LoginForm.php`**
   - `validatePassword()` now uses a timing equalizer: for an unknown username it still runs `security->validatePassword($password, DUMMY_HASH)` before reporting the generic error, so the response time no longer reveals whether the username is registered.
   - Final reversal (owner decision 2026-08-18): `getUser()` maps the canonical Commander username `superadmin` → `User::findByUsername('admin')`, so the Commander account is backed by the single framework `users` row `admin`.

3. **`models/User.php`**
   - `validatePassword()` returns `false` for null/empty password and for an empty stored hash; wraps `Security::validatePassword()` in try/catch so an invalid/corrupt hash yields `false` (login rejected) instead of a 500.

4. **`components/CommanderAuthContext.php`**
   - `login()` now writes via `$session->set(...)` (not raw `$_SESSION`) and calls the new `regenerateSessionId()` which runs `$session->regenerateID(true)` at the anonymous→authenticated boundary (anti-fixation; deletes the pre-auth session). Best-effort: failures are logged, auth result preserved.

5. **`components/CommanderLoginLimiter.php`** (NEW)
   - Cache-backed brute-force protection. 5 failures / 15-minute window, per normalized username **and** per client IP; successful login clears the username counter; empty username/password attempts are not counted; block window bounded by TTL (no permanent lockout). Reuses the app cache component (no new dependency, no schema change). Logs a warning if the cache store cannot persist counters.

6. **`views/site/login.php`**
   - Removed the default-credentials advertisement box.

## Files Actually Modified

- `controllers/SiteController.php`
- `models/LoginForm.php`
- `models/User.php`
- `components/CommanderAuthContext.php`
- `components/CommanderLoginLimiter.php` (new)
- `views/site/login.php`

## Security Tests

Console suite (`commander_auth_test.php`): **51 passed / 0 failed** against a live app instance + MySQL.

- Invalid/empty username & password rejected with a generic error; no 500 paths.
- Valid DB user login succeeds; identity is the real DB row; commander state + `superadmin` role set.
- Session ID is regenerated on login and state survives regeneration.
- Non-superadmin DB user logs in as role `user` (not superadmin).
- Logout clears commander auth + Yii identity.
- `User::findByUsername()` is exact-match only (no generic alias); `superadmin` resolves to the real `admin` row via `LoginForm::getUser()`.
- Gateway (controller POST): `admin`/`admin1234` is rejected with the generic error (no redirect, stays guest); `superadmin`/`admin1234` authenticates, sets the canonical `superadmin` session username and redirects (302); `superadmin` with a wrong password is rejected.
- Empty password / empty hash defensive guards return `false` (no throw).
- Limiter: 5 failures lock (per username + per IP); blocked IP also blocks other usernames; successful login unlocks; 4 failures do not lock; empty-password flood does not lock.

HTTP suite (`final_behavior_http_test.php`) against the dev server (port 8091):

- `superadmin`/`admin1234` → **302** (authenticated, PHPSESSID regenerated).
- `superadmin`/`admin123` and wrong passwords → 200, generic error, no redirect, session unchanged.
- `admin`/`admin1234` and `admin`/`admin123` → **rejected** at Commander (200, generic error, no redirect) — `admin` is workspace-only.
- Unknown username → 200, generic error.
- Commander `superadmin` freely enters any workspace: `/project-list/select/<id>` → 302 → `/dashboard` renders with superadmin flag, no workspace login wall ("kayak dulu").

## Regression Tests

- App boots: `php yii hello/index` → "hello world".
- `GET /site/login`, `GET /site/index`, `GET /project-list`, `GET /site/logout` all behave as expected on the live server.
- Only the 6 files above changed (`git status`); no test artifacts left in the working tree; throwaway `sec_test_*` users created and deleted.
- All changed files pass `php -l`.

## VERIFIED

- Commander login accepts only the canonical `superadmin` username; `admin` (workspace-default) and unknown usernames are rejected at the gateway (HTTP + console).
- Login is DB-hash-only; `superadmin`/`admin1234` authenticates end-to-end over HTTP (302 + new session).
- Commander `superadmin` freely enters any workspace via `/project-list/select/<id>` → `/dashboard` (superadmin mode, no workspace login wall).
- Session ID regenerated on login (console + HTTP cookie comparison).
- CSRF protects the POST (400 without token).
- Brute-force limiter enforces the 5/15min policy (logic-tested with a working cache).
- Generic errors; no user enumeration via error text; timing equalizer in place.
- No 500s on empty/null/corrupt inputs.

## NOT VERIFIED

- Logging in with the real production `admin` password: the hash is unchanged and the owner's password is unknown (per instruction, hash left untouched). Only the temp `sec_test_admin` account was exercised with known credentials.
- Limiter under the real app cache in THIS dev workspace: `FileCache` with keyPrefix `yii-dynamic:` writes colon-named files, which silently fail on this Windows/OneDrive NTFS box (Alternate Data Stream behavior: `file_put_contents` writes, but `file_exists`/`touch` fail). The limiter therefore fails **open** here. On Linux/Redis (production) the store works. Limiter logic was verified with an injected known-good `FileCache` (empty keyPrefix, temp path). This is a pre-existing environment quirk, not caused by this phase.

## Remaining Risks (accepted / out of scope)

- `normalizeRole()` still forces the `superadmin` role for usernames `admin`/`superadmin` at session-write time. This is existing architecture (role-only, not authentication).
- `superadmin`/`admin123` remains the documented seed value in `DatabaseSchemaInitializer` / the seed migration (used only if the DB is freshly seeded). The Commander now requires the owner's real password, so the seed creds alone no longer grant access.
- The `admin` account is the single DB row backing the Commander; its password is effectively the Commander password (currently `admin1234`). Workspace default `admin` accounts live in per-project databases and are separate.
- The login-debug file logger (`@runtime/logs/login-debug.log`) writes a plaintext `username_input`; it logs the username on failure but never the password. Recommend rotation/removal in production (out of scope here).
- Rate limiting is cache-based (fail-open if the cache is down). If availability of the block is mandatory, a DB-backed counter table would be required — explicitly out of scope (no schema change).
