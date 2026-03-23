# test-env Branch Changes (Relative to main)

> **Last updated:** branch `test-env`, commit `18c2e169b5eaf73c65a3229ba33e8fc4e6d3e1fc`. After editing this file, set this to the new tip (`git rev-parse HEAD` on the branch that carries the doc).

All changes in `test-env` compared to `main`. The branch adds local development tooling, Docker improvements, and testing workflows.

---

## 1. Docker & Containerization

**Dockerfile**
- Added Composer binary and `unzip` package

**docker-compose.yml**
- Added `CORE_LOCATION` env var for web service
- Added `PASSWORD_GATE_ENABLED` env var (default `false` via `.env`)
- Mounts `./.htpasswd` into container at `/etc/nginx/.htpasswd:ro`
- Switched DB from bind mount to named volume (`dbdata`)
- Auto-runs init SQL scripts on first DB start

**docker/nginx.conf**
- Passes `DB_HOST db` to PHP via fastcgi param
- Each server block includes `/etc/nginx/auth-snippet.conf` (populated at startup based on `PASSWORD_GATE_ENABLED`)

**docker/startup.sh**
- Adds git safe directory for mounted volume
- Runs `composer install` when vendor is missing
- Uses `mkdir -p` for cache directory
- Toggles auth: when `PASSWORD_GATE_ENABLED=true`, copies `auth-on.conf` to auth-snippet; otherwise writes empty auth-snippet. Validates `.htpasswd` exists and is a file when auth is enabled.

**.gitattributes**
- Enforces LF line endings on `startup.sh`

---

## 1.5. Password Gate (Optional)

Nginx Basic Auth for test/production. Off by default for local dev.

**Files**
- `docker/auth-on.conf` – auth directives (`auth_basic`, `auth_basic_user_file`)
- `.htpasswd.example` – template/instructions (copy to `.htpasswd`)
- `.htpasswd` – credentials file (gitignored); each line is `username:hash`

**Usage**
- Create `.htpasswd`: `cp .htpasswd.example .htpasswd` then add credentials via `echo "user:$(openssl passwd -apr1)" > .htpasswd` (or `htpasswd -c .htpasswd user` if available)
- Enable: set `PASSWORD_GATE_ENABLED=true` in `.env`, then `docker compose up -d` (or `restart web`)
- Disable: set `PASSWORD_GATE_ENABLED=false` or leave unset
- Edit users: edit `.htpasswd` directly (delete line to remove user), then `docker compose restart web`

**See README § Password Gate for full steps.**

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

**webpack.config.js**
- `CopyPlugin` copies static assets into `dist/`: `static/images/` → `dist/static/images/`, `static/js/jquery.Jcrop.min.js` → `dist/static/js/` (so nominee cropping in admin works against the built tree), and `static/js/Chart.min.js` → `dist/static/Chart.min.js` (admin vote stats chart)

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
- `/user/dev-create-seeded-bracket/` (POST) – creates a bracket with 32 nominees + 32 characters and placeholder images, assigns to current user, returns JSON with redirect

**API**
- `GET /api/dev-users/` – returns dev users whose IDs are in the `dev_users_created` cookie (cookie-based isolation for multiple testers)

**Cookie**
- `dev_users_created`: comma-separated user IDs; appended when you create a user; each tester only sees their own users

---

## 4. Database / Test Data

**sql/init-nobody-character.sql** *(new)*
- Creates system bracket (id 1) and "Nobody" character (id 1)
- Used for bye/wildcard placeholders

**Test brackets with nominees/characters**: Use Dev Actions → Create seeded test bracket (no manual SQL)

---

## 5. Testing Notes

**syre-testing.txt** *(new)*
- Quick-start steps: dev login, creating a test bracket, seeding nominees, cache tips

**controller/admin/advance.php**
- `BRACKET_ADVANCE_DELAY` is `0` (not `300`), so manual “advance bracket” in admin has no cooldown—faster iteration in dev. Use a non-zero value (e.g. `300`) in production if you want that guardrail back.

**Cache bypass**: Use the Dev Actions overlay (top-right) → "Flush cache" button, or manually append `?flushCache` to the URL
