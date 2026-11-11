import { refreshToken } from './generated/endpoints/authentication/authentication';
import type { AccessToken } from './generated/model';
import { tokenStore } from './token-store';

const REFRESH_LOCK = 'meridian.session-refresh';
export const SESSION_EXPIRED_EVENT = 'meridian:session-expired';

let inFlight: Promise<AccessToken> | null = null;

/**
 * Exchanges the HttpOnly refresh cookie for a new access token.
 *
 * - Concurrent callers in this tab share one request (the queue).
 * - Across tabs, the Web Locks API serialises refreshes. Refresh tokens are
 *   single-use and the API revokes the whole session if a rotated token is
 *   replayed, so two tabs must never refresh with the same cookie at once.
 *   Each tab sends the latest cookie once it holds the lock.
 */
export function refreshSession(): Promise<AccessToken> {
  inFlight ??= withCrossTabLock(async () => {
    const { data } = await refreshToken({ skipAuthRefresh: true });
    tokenStore.set(data.access_token, data.expires_in);

    return data;
  }).finally(() => {
    inFlight = null;
  });

  return inFlight;
}

/** Tells the application the session is gone (refresh failed). */
export function notifySessionExpired(): void {
  tokenStore.clear();
  window.dispatchEvent(new Event(SESSION_EXPIRED_EVENT));
}

function withCrossTabLock<T>(task: () => Promise<T>): Promise<T> {
  if (typeof navigator !== 'undefined' && 'locks' in navigator && navigator.locks) {
    // The DOM typings model the callback's return value, not its awaited value;
    // at runtime request() resolves to what the async callback resolves to.
    return navigator.locks.request(REFRESH_LOCK, task) as Promise<unknown> as Promise<T>;
  }

  return task();
}
