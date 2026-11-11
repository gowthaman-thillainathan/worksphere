import { getCurrentUser } from './api/generated/endpoints/current-user/current-user';
import { resendEmailVerification } from './api/generated/endpoints/email-verification/email-verification';
import { login, logout, logoutEverywhere } from './api/generated/endpoints/authentication/authentication';
import { forgotPassword, resetPassword } from './api/generated/endpoints/password/password';
import type { AccessToken, GetCurrentUser200, ResetPasswordRequest } from './api/generated/model';
import { refreshSession } from './api/session';
import { tokenStore } from './api/token-store';

export const AuthService = {
  async login(email: string, password: string): Promise<AccessToken> {
    const { data } = await login({ email, password }, { skipAuthRefresh: true });
    tokenStore.set(data.access_token, data.expires_in);

    return data;
  },

  /** Restores a session from the refresh cookie (page load, new tab). */
  restore(): Promise<AccessToken> {
    return refreshSession();
  },

  async logout(): Promise<void> {
    try {
      await logout();
    } finally {
      tokenStore.clear();
    }
  },

  async logoutEverywhere(): Promise<void> {
    try {
      await logoutEverywhere();
    } finally {
      tokenStore.clear();
    }
  },

  currentUser(): Promise<GetCurrentUser200> {
    return getCurrentUser();
  },

  requestPasswordReset(email: string): Promise<void> {
    return forgotPassword({ email }, { skipAuthRefresh: true });
  },

  resetPassword(request: ResetPasswordRequest): Promise<void> {
    return resetPassword(request, { skipAuthRefresh: true });
  },

  resendVerificationEmail(): Promise<void> {
    return resendEmailVerification();
  },
};
