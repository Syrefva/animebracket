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

  if (!toggle || !panel || !listEl) return;

  toggle.addEventListener('click', () => {
    const isHidden = panel.hasAttribute('hidden');
    if (isHidden) {
      panel.removeAttribute('hidden');
      fetchAndPopulateList(listEl, overlay.dataset.currentUser || '');
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

function fetchAndPopulateList(listEl, currentUsername) {
  listEl.innerHTML = '';

  fetch('/api/dev-users/')
    .then((res) => {
      if (!res.ok) return [];
      return res.json();
    })
    .then((users) => {
      const redirect = encodeURIComponent(window.location.pathname || '/');
      users.forEach((u) => {
        if (u.name === currentUsername) return;
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = `/user/dev-login/${encodeURIComponent(u.name)}?redirect=${redirect}`;
        a.textContent = u.name;
        li.appendChild(a);
        listEl.appendChild(li);
      });
    })
    .catch(() => {});
}
