import { AxiosError, AxiosHeaders, type AxiosResponse } from 'axios';
import { describe, expect, it } from 'vitest';
import { describeError } from '@/shared/lib/error-messages';
import { ApiError } from './api-error';

function axiosError(status: number, data: unknown, headers: Record<string, string> = {}): AxiosError {
  const response = { status, data, headers, statusText: '', config: { headers: new AxiosHeaders() } } as AxiosResponse;

  return new AxiosError('Request failed', 'ERR_BAD_RESPONSE', undefined, undefined, response);
}

describe('ApiError', () => {
  it('normalises an RFC 9457 problem with field errors', () => {
    const error = ApiError.from(
      axiosError(422, {
        code: 'validation_failed',
        detail: 'The given data was invalid.',
        request_id: 'req-1',
        errors: { email: [{ code: 'email', message: 'The email field must be a valid email address.' }] },
      }),
    );

    expect(error).toMatchObject({ status: 422, code: 'validation_failed', requestId: 'req-1' });
    expect(error.fieldErrors).toEqual({ email: ['The email field must be a valid email address.'] });
  });

  it('reads Retry-After on rate limiting', () => {
    expect(ApiError.from(axiosError(429, { code: 'rate_limited' }, { 'retry-after': '42' })).retryAfterSeconds).toBe(42);
  });

  it('reports network failures as status 0', () => {
    expect(ApiError.from(new AxiosError('Network Error', 'ERR_NETWORK'))).toMatchObject({ status: 0, code: 'network_error' });
  });
});

describe('describeError', () => {
  it.each([
    [0, 'any', 'You appear to be offline'],
    [403, 'forbidden', 'Access denied'],
    [403, 'email_not_verified', 'Verify your e-mail address'],
    [404, 'not_found', 'Not found'],
    [412, 'version_mismatch', 'Someone else changed this record'],
    [500, 'internal_error', 'Something went wrong'],
  ])('maps %i %s to a user-friendly title', (status, code, title) => {
    expect(describeError(new ApiError({ status, code, detail: 'detail' })).title).toBe(title);
  });

  it('shows business conflicts verbatim and adds the reference to server errors', () => {
    expect(describeError(new ApiError({ status: 409, code: 'work_email_in_use', detail: 'Another employee already uses this work e-mail address.' })).description).toBe(
      'Another employee already uses this work e-mail address.',
    );
    expect(describeError(new ApiError({ status: 500, code: 'internal_error', detail: '', requestId: 'req-9' })).description).toContain('req-9');
  });
});
