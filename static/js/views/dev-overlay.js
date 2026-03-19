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
