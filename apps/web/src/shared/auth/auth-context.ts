import { createContext } from 'react';
import type { User } from '@/types/api';
import type { PermissionKey } from '@/types/permissions';

export type AuthState =
  | { status: 'loading' }
  | { status: 'unauthenticated'; reason?: 'signed-out' | 'expired' }
  | { status: 'authenticated'; user: User; roles: string[]; permissions: ReadonlySet<string> };

export interface AuthContextValue {
  state: AuthState;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  logoutEverywhere: () => Promise<void>;
  reloadProfile: () => Promise<void>;
  can: (permission: PermissionKey) => boolean;
}

export const AuthContext = createContext<AuthContextValue | null>(null);
