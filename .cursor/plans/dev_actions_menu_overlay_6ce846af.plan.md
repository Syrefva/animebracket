---
name: Dev Actions Menu Overlay
overview: Add a "Dev Actions" overlay at the top-right (under the header), reusing shared SCSS mixins and JS toggle logic with the existing Dev Account Selector overlay. Refactor both overlays to use a common base.
todos: []
isProject: false
---

# Dev Actions Menu Overlay (with Code Reuse)

## Overview

Add a "Dev Actions" overlay at the top-right of the page (under the header), gated by `DEV_LOGIN`. Maximize code reuse with the existing [Dev Account Selector](views/partials/_dev-user-overlay.hbs) by extracting shared SCSS mixins and a reusable JS toggle module. The refactor preserves all existing Dev Account Selector behavior; only additions are `aria-expanded` and data attributes.

## Code Reuse Strategy

Both overlays share the same structure and behavior:


| Shared piece                                          | Current location                                                       | Refactor                                          |
| ----------------------------------------------------- | ---------------------------------------------------------------------- | ------------------------------------------------- |
| Toggle + click-outside logic                          | [dev-user-overlay.js](static/js/views/dev-user-overlay.js) lines 21–36 | Extract to `dev-overlay.js`                       |
| Panel styling (background, padding, shadow, [hidden]) | [dev-user-overlay.scss](static/scss/dev-user-overlay.scss) lines 21–34 | Extract to `@mixin dev-overlay-panel($placement)` |
| Toggle button base (small-button, padding, cursor)    | dev-user-overlay.scss lines 9–18                                       | Extract to `@mixin dev-overlay-toggle($color)`    |
| Action/list link styling                              | dev-user-overlay `__user-list` a                                       | `@mixin dev-overlay-action-link()`                |


### Shared SCSS (`static/scss/dev-overlay.scss`)

- `@import "globals"` so mixins can use `$darkGrey`, `$lightBlue`, etc.
- Import in `index.scss` **before** `dev-user-overlay` and `dev-actions-overlay`
- File contains only mixins (no selectors), so it emits no CSS

```scss
@mixin dev-overlay-panel($placement: 'above') {
  position: absolute;
  @if $placement == 'above' {
    bottom: 100%;
    right: 0;
    margin-bottom: 8px;
  } @else {
    top: 100%;
    right: 0;
    margin-top: 8px;
  }
  min-width: 220px;
  padding: 12px;
  background: $darkGrey;
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  &[hidden] { display: none; }
}

@mixin dev-overlay-toggle($color: #a94442) {
  @include small-button();
  padding: 8px 14px;
  font-size: 14px;
  cursor: pointer;
  background-color: $color !important;
  &:hover { background-color: darken($color, 8%) !important; }
}

@mixin dev-overlay-action-link() {
  color: $lightBlue;
  font-size: 14px;
  font-family: inherit;
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  &:hover { text-decoration: underline; }
}
```

### Shared JS (`dev-overlay.js`)

```javascript
/**
 * Sets up toggle + click-outside for any dev overlay.
 * @param {string} overlayId - e.g. 'dev-user-overlay', 'dev-actions-overlay'
 * @param {Object} [opts] - { onOpen, onClose }
 * @returns {{ overlay, toggle, panel } | null}
 */
export function initToggle(overlayId, opts = {}) {
  const overlay = document.getElementById(overlayId);
  if (!overlay) return null;
  const toggle = overlay.querySelector('[data-dev-overlay-toggle]');
  const panel = overlay.querySelector('[data-dev-overlay-panel]');
  if (!toggle || !panel) return null;

  toggle.addEventListener('click', () => {
    const isHidden = panel.hasAttribute('hidden');
    if (isHidden) {
      panel.removeAttribute('hidden');
      toggle.setAttribute('aria-expanded', 'true');
      opts.onOpen?.();
    } else {
      panel.setAttribute('hidden', '');
      toggle.setAttribute('aria-expanded', 'false');
      opts.onClose?.();
    }
  });

  document.addEventListener('click', (evt) => {
    if (panel.hasAttribute('hidden')) return;
    if (overlay.contains(evt.target)) return;
    panel.setAttribute('hidden', '');
    toggle.setAttribute('aria-expanded', 'false');
  });

  return { overlay, toggle, panel };
}
```

Both overlays use `data-dev-overlay-toggle` and `data-dev-overlay-panel` for the shared init. Each overlay adds its own document click listener; acceptable for two overlays (could optimize later with a shared listener). **Keep existing classes** for styling (e.g. `class="dev-user-overlay__toggle"`) and add the data attributes alongside.

## Implementation Order

1. **Create shared assets** (no breaking changes yet)
  - Add `static/scss/dev-overlay.scss` with `@import "globals"` and the three mixins (`dev-overlay-panel`, `dev-overlay-toggle`, `dev-overlay-action-link`)
  - In `index.scss`: add `@import "dev-overlay"` **before** `@import "dev-user-overlay"` so mixins are available
  - Add `static/js/views/dev-overlay.js` with `initToggle()` (in views/ so not ignored by .gitignore; toggle + click-outside to close; no Escape key)
2. **Refactor Dev Account Selector** to use shared code
  - In `_dev-user-overlay.hbs`: add `data-dev-overlay-toggle` and `data-dev-overlay-panel` (keep existing classes); add `aria-expanded="false"` to toggle (aria-label already present)
  - In `dev-user-overlay.scss`: replace panel/toggle blocks with `@include dev-overlay-panel('above')` and `@include dev-overlay-toggle(#a94442)`; use `@include dev-overlay-action-link()` for `__user-list a`
  - In `dev-user-overlay.js`: call `initToggle('dev-user-overlay', { onOpen: ... })`; use returned `{ overlay }` for redirect sync and for `listEl` (overlay.querySelector('[data-dev-user-list]')); run redirect sync after initToggle using `result.overlay`. Note: redirectTarget is computed once at init (matches current behavior; can be stale after client-side navigation).
3. **Add Dev Actions overlay**
  - Create `_dev-actions-overlay.hbs` with same structure (toggle + panel), `data-dev-overlay-toggle` / `data-dev-overlay-panel`, `aria-label="Dev actions"` and `aria-expanded="false"` on toggle
  - Panel contains `<button type="button" data-dev-action="flush-cache" class="dev-actions-overlay__action">Flush cache</button>` directly (no list wrapper — avoids bullet styling). Style `__action` as a button (solid background $standard, padding, border-radius; matches dev-user-overlay__btn)
  - Create `dev-actions-overlay.scss` using the mixins; position `top: 98px; right: 16px` (82px nav + 16px padding, matches account selector bottom offset); panel uses `dev-overlay-panel('below')`; toggle uses `dev-overlay-toggle(#a94442)` (red, same as account selector)
  - Create `dev-actions-overlay.js`: call `initToggle('dev-actions-overlay')`, attach click handler to `[data-dev-action="flush-cache"]` that navigates to current URL with `searchParams.set('flushCache','')`
  - Include `{{>_dev-actions-overlay}}` **right after** `{{>_dev-user-overlay}}` in both layouts; add to app.js and index.scss

## File Summary


| Action     | File                                                                                                                |
| ---------- | ------------------------------------------------------------------------------------------------------------------- |
| **New**    | `static/scss/dev-overlay.scss` – mixins (panel, toggle, action-link)                                                |
| **New**    | `static/js/views/dev-overlay.js` – `initToggle()` (in views/ so not ignored by static/js/*.js)                     |
| **New**    | `views/partials/_dev-actions-overlay.hbs`                                                                           |
| **New**    | `static/scss/dev-actions-overlay.scss`                                                                              |
| **New**    | `static/js/views/dev-actions-overlay.js`                                                                            |
| **Modify** | `static/scss/index.scss` – add `@import "dev-overlay"` before dev-user-overlay, add `@import "dev-actions-overlay"` |
| **Modify** | `static/scss/dev-user-overlay.scss` – use mixins                                                                    |
| **Modify** | `views/partials/_dev-user-overlay.hbs` – add data attributes, `aria-expanded="false"` on toggle                     |
| **Modify** | `static/js/views/dev-user-overlay.js` – use `initToggle`                                                            |
| **Modify** | `views/layouts/default.hbs`, `admin.hbs` – include `_dev-actions-overlay` right after `_dev-user-overlay`           |
| **Modify** | `static/js/app.js` – import and init `dev-actions-overlay`                                                          |


## Flush Cache Logic

From [lib/cache.php](lib/cache.php): cache is disabled when `flushCache` appears in the query string (disables reads/writes for that request; does not clear stored cache). JS:

```javascript
const url = new URL(window.location.href);
url.searchParams.set('flushCache', '');
window.location.href = url.toString();
```

Preserves hash if present. If URL already has `flushCache`, navigation still triggers a refresh.

