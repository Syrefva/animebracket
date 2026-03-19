/**
 * Dev Actions Overlay - toggle + action handlers.
 * No-ops when #dev-actions-overlay is absent (DEV_LOGIN disabled).
 */
import { initToggle } from './dev-overlay';

export function init() {
  const result = initToggle('dev-actions-overlay');
  if (!result) return;

  const flushBtn = result.overlay.querySelector('[data-dev-action="flush-cache"]');
  if (flushBtn) {
    flushBtn.addEventListener('click', () => {
      const url = new URL(window.location.href);
      url.searchParams.set('flushCache', '');
      window.location.href = url.toString();
    });
  }
}
