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
- Adds `DEV_LOGIN` flag (enables Dev Account Selector and Dev Actions overlays)

---

## 3. Dev Overlays (Bypass Reddit OAuth)

When `DEV_LOGIN` is true, two floating overlays appear on all pages.

### Dev Account Selector (bottom-right)

- Toggle button ("Dev Account Selector") expands/collapses the panel
- **Logged in**: Shows current user, switch-user list (from API), and log out link
- **Logged out**: Create admin / Create normal user buttons, and login-as list

### Dev Actions (top-right)

- Toggle button ("Dev Actions") expands/collapses the panel
- **Flush cache**: Reloads the current page with `?flushCache` appended to bypass cache for that request
- **Create seeded test bracket**: Creates a new bracket with 32 nominees + characters and placeholder images (requires login). Use Dev Actions -> Create seeded test bracket.

### Shared overlay infrastructure

- `static/js/views/dev-overlay.js` – shared toggle + click-outside logic
- `static/scss/dev-overlay.scss` – shared mixins (panel, toggle, action-link)

### Routes
- `/user/dev-create/{admin|user}` – creates `devadmin_*` or `devuser_*` with random suffix, sets session and cookie, redirects
- `/user/dev-login/{username}` – verifies user ID is in `dev_users_created` cookie, sets session, redirects
- `/user/dev-logout/?redirect=` – clears session, redirects

**API**
- `GET /api/dev-users/` – returns dev users whose IDs are in the `dev_users_created` cookie (cookie-based isolation for multiple testers)

**Cookie**
- `dev_users_created`: comma-separated user IDs; appended when you create a user; each tester only sees their own users

---

## 4. Database / Test Data

**sql/init-nobody-character.sql** *(new)*
- Creates system bracket (id 1) and "Nobody" character (id 1)
- Used for bye/wildcard placeholders

---

## 5. Testing Notes

**syre-testing.txt** *(new)*
- Quick-start steps: dev login, creating a test bracket, seeding nominees, cache tips

**Cache bypass**: Use the Dev Actions overlay (top-right) → "Flush cache" button, or manually append `?flushCache` to the URL
