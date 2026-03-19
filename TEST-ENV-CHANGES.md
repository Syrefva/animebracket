# test-env Branch Changes (Relative to main)

All changes in `test-env` compared to `main`. The branch adds local development tooling, Docker improvements, and testing workflows.

---

## 1. Docker & Containerization

**Dockerfile**
- Added Composer binary and `unzip` package

**docker-compose.yml**
- Added `CORE_LOCATION` env var for web service
- Switched DB from bind mount to named volume (`dbdata`)
- Auto-runs init SQL scripts on first DB start

**docker/nginx.conf**
- Passes `DB_HOST db` to PHP via fastcgi param

**docker/startup.sh**
- Adds git safe directory for mounted volume
- Runs `composer install` when vendor is missing
- Uses `mkdir -p` for cache directory

**.gitattributes**
- Enforces LF line endings on `startup.sh`

---

## 2. Configuration (Local Dev Defaults)

**app-config.sample.php**
- `CORE_LOCATION` uses `__DIR__` instead of hardcoded path
- Image path/URL default to local (`/images`, `localhost:8080`)
- `SESSION_DOMAIN` is empty on localhost, `.brakk.it` otherwise
- `VIEW_PATH` derives from `CORE_LOCATION`

**config.sample.php**
- DB host reads from `$_SERVER['DB_HOST']` (set by nginx in Docker)
- Ships with local test credentials
- Adds `DEV_LOGIN` flag (enables `/user/dev-login`)

---

## 3. Dev Login (Bypass Reddit OAuth)

**controller/user.php**
- New `/user/dev-login` route
- When `DEV_LOGIN` is true, creates or fetches `devadmin` user, sets session, redirects to `/me/`

**api/user.php**
- New `getById($id)` static method

---

## 4. Database / Test Data

**sql/dev-user.sql** *(new)*
- Inserts `devadmin` admin user for local testing

**sql/init-nobody-character.sql** *(new)*
- Creates system bracket (id 1) and "Nobody" character (id 1)
- Used for bye/wildcard placeholders

**sql/insert-test4-nominees.sql** *(new)*
- Inserts 32 nominees + characters into a `test4` bracket
- For manual end-to-end testing

---

## 5. Testing Notes

**syre-testing.txt** *(new)*
- Quick-start steps: dev login, creating a test bracket, seeding nominees, cache tips
