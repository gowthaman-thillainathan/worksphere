import { http, HttpResponse } from 'msw';
import { describe, expect, it, vi } from 'vitest';
import { problem, tokenResponse } from '@/test/msw/handlers';
import { api } from '@/test/msw/api';
import { server } from '@/test/msw/server';
import { ApiError } from './api-error';
import { EmployeeService } from '../employee-service';
import { SESSION_EXPIRED_EVENT } from './session';
import { tokenStore } from './token-store';

const EMPTY_PAGE = { data: [], meta: { per_page: 25, next_cursor: null, prev_cursor: null }, links: { next: null, prev: null } };

/** /employees answers 401 to the stale token and 200 to the refreshed one. */
function protectedEmployees(seen: string[]) {
  return http.get(api.employees, ({ request }) => {
    const authorization = request.headers.get('Authorization') ?? '';
    seen.push(authorization);

    return authorization === 'Bearer fresh-token' ? HttpResponse.json(EMPTY_PAGE) : problem(401, 'unauthenticated', 'Authentication is required.');
  });
}

describe('API client authentication', () => {
  it('attaches the in-memory access token', async () => {
    const seen: string[] = [];
    server.use(protectedEmployees(seen));
    tokenStore.set('fresh-token', 900);

    await EmployeeService.list({});

    expect(seen).toEqual(['Bearer fresh-token']);
  });

  it('refreshes once for concurrent 401s and retries every request', async () => {
    const seen: string[] = [];
    let refreshCalls = 0;
    server.use(
      protectedEmployees(seen),
      http.post(api.refresh, async () => {
        refreshCalls += 1;
        await new Promise((resolve) => setTimeout(resolve, 20));

        return tokenResponse('fresh-token');
      }),
    );
    tokenStore.set('stale-token', 900);

    await Promise.all([EmployeeService.list({}), EmployeeService.list({ search: 'a' }), EmployeeService.list({ search: 'b' })]);

    expect(refreshCalls).toBe(1);
    expect(seen.filter((header) => header === 'Bearer fresh-token')).toHaveLength(3);
    expect(tokenStore.get()?.accessToken).toBe('fresh-token');
  });

  it('refreshes proactively when the token is about to expire', async () => {
    const seen: string[] = [];
    server.use(protectedEmployees(seen), http.post(api.refresh, () => tokenResponse('fresh-token')));
    tokenStore.set('stale-token', 10); // 10 seconds left

    await EmployeeService.list({});

    expect(seen).toEqual(['Bearer fresh-token']);
  });

  it('ends the session when the refresh fails', async () => {
    const expired = vi.fn();
    window.addEventListener(SESSION_EXPIRED_EVENT, expired);
    server.use(protectedEmployees([]), http.post(api.refresh, () => problem(401, 'invalid_refresh_token', 'Expired.')));
    tokenStore.set('stale-token', 900);

    await expect(EmployeeService.list({})).rejects.toMatchObject({ status: 401 });

    expect(expired).toHaveBeenCalledOnce();
    expect(tokenStore.get()).toBeNull();
    window.removeEventListener(SESSION_EXPIRED_EVENT, expired);
  });

  it('does not try to refresh for visitors without a session', async () => {
    const refresh = vi.fn();
    server.use(protectedEmployees([]), http.post(api.refresh, refresh));

    await expect(EmployeeService.list({})).rejects.toBeInstanceOf(ApiError);
    expect(refresh).not.toHaveBeenCalled();
  });

  it('sends If-Match for optimistic locking without losing the JSON content type', async () => {
    let headers: Headers | undefined;
    server.use(
      http.patch(api.employee, ({ request }) => {
        headers = request.headers;

        return HttpResponse.json({ data: {} });
      }),
    );
    tokenStore.set('fresh-token', 900);

    await EmployeeService.update('id-1', 7, { job_title: 'Lead' });

    expect(headers?.get('If-Match')).toBe('"v7"');
    expect(headers?.get('Content-Type')).toContain('application/json');
  });
});
