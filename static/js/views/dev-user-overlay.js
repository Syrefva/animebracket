/**
 * Dev User Overlay - toggle, fetch dev users, populate switch/login list.
 * No-ops when #dev-user-overlay is absent (DEV_LOGIN disabled).
 */
import { initToggle } from './dev-overlay';

export function init() {
  const redirectTarget = getCurrentRedirectTarget();
  const result = initToggle('dev-user-overlay', {
    onOpen: () => {
      const listEl = result.overlay.querySelector('[data-dev-user-list]');
      if (listEl) {
        fetchAndPopulateList(listEl, result.overlay.dataset.currentUser || '', redirectTarget);
      }
    }
  });
  if (!result) return;

  // Keep redirect target aligned to the full current URL.
  result.overlay.querySelectorAll('a[href*="redirect="]').forEach((link) => {
    link.href = link.href.replace(/redirect=[^&]*/, `redirect=${redirectTarget}`);
  });
}

function fetchAndPopulateList(listEl, currentUsername, redirectTarget) {
  listEl.innerHTML = '';

  fetch('/api/dev-users/')
    .then((res) => {
      if (!res.ok) return [];
      return res.json();
    })
    .then((users) => {
      if (!Array.isArray(users)) return;
      users.forEach((u) => {
        if (u.name === currentUsername) return;
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = `/user/dev-login/${encodeURIComponent(u.name)}?redirect=${redirectTarget}`;
        a.textContent = u.name;
        li.appendChild(a);
        listEl.appendChild(li);
      });
    })
    .catch(() => {});
}

function getCurrentRedirectTarget() {
  const fullPath = `${window.location.pathname || '/'}${window.location.search || ''}${window.location.hash || ''}`;
  return encodeURIComponent(fullPath || '/');
}
