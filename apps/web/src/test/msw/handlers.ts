import { http, HttpResponse } from 'msw';
import { api } from './api';
import { ALL_PERMISSIONS, user } from './fixtures';

export const ACCESS_TOKEN = 'test-access-token';

export function problem(status: number, code: string, detail: string, errors?: Record<string, { code: string; message: string }[]>) {
  return HttpResponse.json(
    { type: `https://docs.meridian.app/errors/${code}`, title: 'Error', status, code, detail, instance: '/', request_id: '0193a4c2-0000-7000-8000-00000000beef', errors },
    { status, headers: { 'Content-Type': 'application/problem+json' } },
  );
}

export function tokenResponse(accessToken = ACCESS_TOKEN, expiresIn = 900) {
  return HttpResponse.json({
    data: { access_token: accessToken, token_type: 'Bearer', expires_in: expiresIn, expires_at: '2026-11-10T08:15:00Z', user },
  });
}

/**
 * A signed-in session: the refresh cookie is valid and /me returns the given
 * permissions. Pass signedIn: false for a visitor without a session.
 */
export function sessionHandlers({ signedIn = true, permissions = ALL_PERMISSIONS, roles = ['hr_admin'] } = {}) {
  return [
    http.post(api.refresh, () => (signedIn ? tokenResponse() : problem(401, 'invalid_refresh_token', 'The session has expired.'))),
    http.get(api.me, () => HttpResponse.json({ data: user, meta: { roles, permissions } })),
    http.post(api.logout, () => new HttpResponse(null, { status: 204 })),
  ];
}
