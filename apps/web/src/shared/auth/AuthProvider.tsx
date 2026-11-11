import { useQueryClient } from '@tanstack/react-query';
import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react';
import { AuthService } from '@/services/auth-service';
import { refreshSession, SESSION_EXPIRED_EVENT, notifySessionExpired } from '@/services/api/session';
import { tokenStore } from '@/services/api/token-store';
import type { PermissionKey } from '@/types/permissions';
import { AuthContext, type AuthContextValue, type AuthState } from './auth-context';

/** Refresh this long before the access token expires (silent refresh). */
const REFRESH_AHEAD_MS = 60_000;

/**
 * Owns the session lifecycle:
 * - on start, restores the session from the HttpOnly refresh cookie;
 * - keeps the in-memory access token fresh in the background;
 * - loads the profile and effective permissions from /me;
 * - ends the session when a refresh fails anywhere in the app.
 */
export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const [state, setState] = useState<AuthState>({ status: 'loading' });

  const loadProfile = useCallback(async () => {
    const { data: user, meta } = await AuthService.currentUser();
    setState({ status: 'authenticated', user, roles: meta.roles, permissions: new Set(meta.permissions) });
  }, []);

  // Restore the session once on start-up.
  useEffect(() => {
    let cancelled = false;

    AuthService.restore()
      .then(() => loadProfile())
      .catch(() => {
        if (!cancelled) setState({ status: 'unauthenticated' });
      });

    return () => {
      cancelled = true;
    };
  }, [loadProfile]);

  // Silent refresh shortly before the access token expires.
  useEffect(() => {
    let timer: number | undefined;

    const schedule = () => {
      window.clearTimeout(timer);
      const session = tokenStore.get();

      if (session) {
        const delay = Math.max(session.expiresAt - Date.now() - REFRESH_AHEAD_MS, 0);
        timer = window.setTimeout(() => {
          refreshSession().catch(notifySessionExpired);
        }, delay);
      }
    };

    schedule();
    const unsubscribe = tokenStore.subscribe(schedule);

    return () => {
      unsubscribe();
      window.clearTimeout(timer);
    };
  }, []);

  // A refresh failed somewhere: the session is over.
  useEffect(() => {
    const onExpired = () => {
      queryClient.clear();
      setState({ status: 'unauthenticated', reason: 'expired' });
    };

    window.addEventListener(SESSION_EXPIRED_EVENT, onExpired);

    return () => window.removeEventListener(SESSION_EXPIRED_EVENT, onExpired);
  }, [queryClient]);

  const login = useCallback(
    async (email: string, password: string) => {
      await AuthService.login(email, password);
      await loadProfile();
    },
    [loadProfile],
  );

  const endSession = useCallback(
    async (end: () => Promise<void>) => {
      try {
        await end();
      } finally {
        queryClient.clear();
        setState({ status: 'unauthenticated', reason: 'signed-out' });
      }
    },
    [queryClient],
  );

  const value = useMemo<AuthContextValue>(
    () => ({
      state,
      login,
      logout: () => endSession(() => AuthService.logout()),
      logoutEverywhere: () => endSession(() => AuthService.logoutEverywhere()),
      reloadProfile: loadProfile,
      can: (permission: PermissionKey) => state.status === 'authenticated' && state.permissions.has(permission),
    }),
    [state, login, endSession, loadProfile],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
