import { isAxiosError, type AxiosInstance } from 'axios';
import { ApiError } from './api-error';
import { http } from './http-client';
import { notifySessionExpired, refreshSession } from './session';
import { tokenStore } from './token-store';

/** Refresh proactively when the token has less than this left. */
const PROACTIVE_REFRESH_MS = 30_000;
const HTTP_UNAUTHORIZED = 401;

let installed = false;

/**
 * Attaches authentication to the API client:
 *
 * 1. Requests carry `Authorization: Bearer <token>` from memory; a token
 *    about to expire is refreshed first.
 * 2. A 401 triggers one shared refresh; every request that failed meanwhile
 *    waits for it and is retried once with the new token.
 * 3. If the refresh fails, the session is over: the app returns to /login.
 * 4. Every error is normalised to an ApiError.
 */
export function installInterceptors(client: AxiosInstance = http): void {
  if (installed && client === http) {
    return;
  }

  installed = client === http || installed;

  client.interceptors.request.use(async (config) => {
    if (!config.skipAuthRefresh && tokenStore.expiresWithin(PROACTIVE_REFRESH_MS)) {
      await refreshSession().catch(() => undefined); // a failure surfaces as a 401 below
    }

    const session = tokenStore.get();

    if (session && !config.skipAuthRefresh) {
      config.headers.set('Authorization', `Bearer ${session.accessToken}`);
    }

    return config;
  });

  client.interceptors.response.use(undefined, async (error: unknown) => {
    if (!isAxiosError(error) || !error.config) {
      throw ApiError.from(error);
    }

    const { config } = error;
    const canRefresh =
      error.response?.status === HTTP_UNAUTHORIZED &&
      !config.skipAuthRefresh &&
      !config.retriedAfterRefresh &&
      tokenStore.get() !== null;

    if (!canRefresh) {
      throw ApiError.from(error);
    }

    try {
      await refreshSession();
    } catch {
      notifySessionExpired();
      throw ApiError.from(error);
    }

    return client.request({ ...config, retriedAfterRefresh: true });
  });
}
