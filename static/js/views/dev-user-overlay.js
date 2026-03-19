/**
 * Dev User Overlay - toggle, fetch dev users, populate switch/login list.
 * No-ops when #dev-user-overlay is absent (DEV_LOGIN disabled).
 */
export function init() {
  const overlay = document.getElementById('dev-user-overlay');
  if (!overlay) return;

  const toggle = overlay.querySelector('.dev-user-overlay__toggle');
  const panel = overlay.querySelector('.dev-user-overlay__panel');
  const listEl = overlay.querySelector('[data-dev-user-list]');
  const redirectTarget = getCurrentRedirectTarget();

  if (!toggle || !panel || !listEl) return;

  // Keep redirect target aligned to the full current URL.
  overlay.querySelectorAll('a[href*="redirect="]').forEach((link) => {
    link.href = link.href.replace(/redirect=[^&]*/, `redirect=${redirectTarget}`);
  });

  toggle.addEventListener('click', () => {
    const isHidden = panel.hasAttribute('hidden');
    if (isHidden) {
      panel.removeAttribute('hidden');
      fetchAndPopulateList(listEl, overlay.dataset.currentUser || '', redirectTarget);
    } else {
      panel.setAttribute('hidden', '');
    }
  });

  // Click outside to close
  document.addEventListener('click', (evt) => {
    if (panel.hasAttribute('hidden')) return;
    if (overlay.contains(evt.target)) return;
    panel.setAttribute('hidden', '');
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
