---
name: Dev Create Seeded Bracket
overview: Add a new Dev Action that creates a fully seeded test bracket (32 nominees + 32 characters) with Create-a-New-Bracket defaults, then deprecate the manual test4 SQL workflow.
todos: []
isProject: false
---

# Dev Action: Create Seeded Test Bracket

## Current State

The test4 workflow ([syre-testing.txt](syre-testing.txt)) requires:

1. Manually creating a bracket via UI ("Create a New Bracket")
2. Running [sql/insert-test4-nominees.sql](sql/insert-test4-nominees.sql) (32 nominees + 32 characters)
3. Running `php cli/save-test4-character-images.php` (referenced in SQL comments but **not present in repo**)

The insert script uses `bracket_perma = 'test4'` and a placeholder image URL. Character images are served from `IMAGE_LOCATION/{id_base36}.jpg`.

## Proposed Design

Add a **"Create seeded test bracket"** button to the Dev Actions overlay. When clicked, it sends a `POST` request to a new dev route that:

1. Verifies `DEV_LOGIN` and that the user is logged in
2. Creates a bracket with the specified defaults
3. Seeds 32 nominees (processed) + 32 characters
4. Creates placeholder images for each character
5. Assigns the bracket to the current user; frontend redirects to `/me/?created&flushCache`

### Bracket Defaults (matching "Create a New Bracket" defaults)


| Field       | Value                                               |
| ----------- | --------------------------------------------------- |
| advanceHour | -1 (I want to manage this manually)                 |
| minAge      | 2592000 (1 month or older)                          |
| captcha     | NEVER (0)                                           |
| nameLabel   | "Character name"                                    |
| sourceLabel | "Source" (show source)                              |
| rules       | "test"                                              |
| name        | "Test " + random suffix (e.g. 6 alphanumeric chars) |
| hidden      | 0 (visible in bracket list)                         |


Additional: `state = 0`, `start = time()`, `generatePerma()` for unique perma.

### Placeholder images

Character images are served from `IMAGE_LOCATION/{id_base36}.jpg`. Use **PHP GD** to create 150x150 grey squares with numbers (no network). Image generation is best-effort: skip on GD/path failure and still return success.

### Architecture

```mermaid
flowchart TD
    subgraph frontend [Frontend]
        Overlay[Dev Actions Overlay]
        Btn[Create seeded test bracket]
        Overlay --> Btn
        Btn -->|POST| Route[/user/dev-create-seeded-bracket]
    end

    subgraph backend [Backend]
        UserCtrl[Controller User]
        Route --> UserCtrl
        UserCtrl -->|DEV_LOGIN + logged in| CreateBracket[Create Bracket API]
        CreateBracket --> SeedNominees[Seed 32 nominees]
        SeedNominees --> SeedChars[Seed 32 characters]
        SeedChars --> PlaceholderImgs[Create placeholder images]
        PlaceholderImgs --> AddOwner[Add user as owner]
        AddOwner --> JsonResponse[Return JSON with redirect or error]
    end
```



## Implementation

### 1. Backend: New dev route in [controller/user.php](controller/user.php)

- Add `dev-create-seeded-bracket` to `$devActions` array (line 16)
- Add handler block (after `dev-logout`, ~line 77):
  - Accept **POST only**; for non-POST return JSON error
  - Require logged-in user; if not, return JSON `{ success: false, message: 'Login required' }` (button is disabled when logged out; this is a safety check)
  - **Return JSON** for both success and failure:
    - Success: `{ success: true, redirect: '/me/?created&flushCache' }`
    - Failure: `{ success: false, message: '...' }`
  - Create `Api\Bracket` with defaults above
  - Call `$bracket->generatePerma()` (perma derived from name, deduped)
  - `$bracket->sync()` then `$bracket->addUser($user)`
  - Seed 32 nominees (processed=1) and 32 characters via bulk INSERT (single loop builds both; use `:placeholderUrl` once for all nominee images)
  - Query inserted characters by `bracket_id` ordered by `character_id ASC` to get deterministic IDs for image generation
  - Create placeholder images: for each character ID, use PHP GD to create a simple image (e.g. 150x150 grey with number) and save to `IMAGE_LOCATION . '/' . base_convert($charId, 10, 36) . '.jpg'`

### 2. Frontend: Dev Actions overlay

- **[views/partials/_dev-actions-overlay.hbs](views/partials/_dev-actions-overlay.hbs)**: Add button (same visual/button style as flush-cache)
  - Use `<button type="button">` with `data-dev-action="create-seeded-bracket"`
  - When user is **not** logged in: wrap in `__action-wrap` with disabled button + overlay div. Overlay has `data-tooltip="Log in to create a seeded bracket"` (disabled buttons don't receive hover, so overlay provides hoverable surface for tooltip)
  - Use `{{#if user}}` / `{{else}}` (user is already passed globally from `controller/page.php`)
- **[static/scss/dev-actions-overlay.scss](static/scss/dev-actions-overlay.scss)**:
  - Add padding between action buttons (`margin-top: 8px` on `__action` except first child)
  - Add `__action-wrap` and `__action-overlay` with custom CSS tooltip (`::after` on `[data-tooltip]:hover`, 0.08s transition for near-instant appearance vs native title ~600ms)
- **dev-actions-overlay.js**: Add click handler for `[data-dev-action="create-seeded-bracket"]`:
  - If disabled, do nothing
  - Otherwise: `fetch('/user/dev-create-seeded-bracket/', { method: 'POST' })`, parse JSON
  - On `success: true`: `window.location.href = data.redirect` (redirect includes `flushCache` so the /me/ page shows fresh data)
  - On `success: false`: `alert(data.message || 'Failed to create seeded bracket.')`

### 3. Seeding logic

Reuse structure from [sql/insert-test4-nominees.sql](sql/insert-test4-nominees.sql) but implement in PHP:

- 32 nominees: `nominee_name`, `nominee_source` (null/empty), `nominee_created`, `nominee_processed=1`, `nominee_image` set to a fixed placeholder string (not used for rendered entrant images)
- 32 characters: `character_name`, `character_source` (empty string), `character_seed` (null), `character_meta` (null)

Use bulk INSERT for 32 nominees + 32 characters (single loop builds both arrays; `:placeholderUrl` reused for all nominee images). After character insert, read rows back by bracket ID in `character_id ASC`; number generated images `1..32` in that order for deterministic mapping.

### 4. Placeholder images

Inline in the handler: for each character ID, create `IMAGE_LOCATION/{id_base36}.jpg` using `imagecreatetruecolor(150, 150)`, fill grey, `imagestring` for number, `imagejpeg`. Skip if GD or path fails (best-effort). See [controller/admin/process.php](controller/admin/process.php) (lines 195, 374) for image-saving pattern.

### 5. Deprecation (after new action works)

- Delete [sql/insert-test4-nominees.sql](sql/insert-test4-nominees.sql)
- Update [TEST-ENV-CHANGES.md](TEST-ENV-CHANGES.md): Remove insert-test4-nominees from "Database / Test Data", add replacement note ("Use Dev Actions -> Create seeded test bracket")
- Update [syre-testing.txt](syre-testing.txt): Replace test4 steps with "Use Dev Actions -> Create seeded test bracket"
- Add a final verification step: repo-wide search confirms no lingering references to:
  - `insert-test4-nominees.sql`
  - `save-test4-character-images.php`
  - old `test4` manual seeding instructions
- Verify seeded counts for the created bracket: exactly 32 `nominee` rows and 32 `character` rows.

## Files to Modify/Create


| Action | File                                                                                                       |
| ------ | ---------------------------------------------------------------------------------------------------------- |
| Modify | [controller/user.php](controller/user.php) – add route + handler                                           |
| Modify | [views/partials/_dev-actions-overlay.hbs](views/partials/_dev-actions-overlay.hbs) – add button            |
| Modify | [static/scss/dev-actions-overlay.scss](static/scss/dev-actions-overlay.scss) – padding between buttons     |
| Modify | [static/js/views/dev-actions-overlay.js](static/js/views/dev-actions-overlay.js) – add POST action handler |
| Delete | [sql/insert-test4-nominees.sql](sql/insert-test4-nominees.sql)                                             |
| Modify | [TEST-ENV-CHANGES.md](TEST-ENV-CHANGES.md)                                                                 |
| Modify | [syre-testing.txt](syre-testing.txt)                                                                       |


## Notes

- **Image fallback**: If PHP GD or IMAGE_LOCATION is unavailable, skip image creation; bracket and characters still work (images 404).
- **Login requirement**: Button is disabled when not logged in; overlay with custom tooltip (disabled buttons don't receive hover). Backend returns failure if somehow called without a logged-in user; frontend shows alert on failure.
- **Intentional scope**: Keep transaction handling as-is for this dev-only seed workflow.
- **Intentional scope**: Keep broad access model in test environment as-is (no additional role restrictions for this action).

## Guardrails (No Unintended Behavior Changes)

- Keep existing `flush-cache` action behavior unchanged.
- Keep existing Dev Account Selector behavior unchanged.
- Do not alter normal bracket creation/edit flows; only add the new dev action path.
- Keep existing `DEV_LOGIN` gating behavior intact; no production behavior changes.

