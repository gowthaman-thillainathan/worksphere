import { useContext } from 'react';
import type { User } from '@/types/api';
import { AuthContext, type AuthContextValue } from './auth-context';

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (context === null) {
    throw new Error('useAuth must be used inside <AuthProvider>.');
  }

  return context;
}

/** The signed-in user; only call below <RequireAuth>. */
export function useCurrentUser(): { user: User; roles: string[] } {
  const { state } = useAuth();

  if (state.status !== 'authenticated') {
    throw new Error('useCurrentUser requires an authenticated session.');
  }

  return { user: state.user, roles: state.roles };
}
