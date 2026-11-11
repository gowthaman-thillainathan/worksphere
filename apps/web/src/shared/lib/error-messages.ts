import { ApiError } from '@/services/api/api-error';

export interface ErrorDescription {
  title: string;
  description: string;
}

/**
 * Turns any error into words a user can act on. Business errors (409, 422)
 * carry a safe, specific message from the API; everything else gets a
 * generic message plus the request id for support.
 */
export function describeError(error: unknown): ErrorDescription {
  const apiError = ApiError.from(error);
  const reference = apiError.requestId ? ` Reference: ${apiError.requestId}` : '';

  switch (true) {
    case apiError.status === 0:
      return { title: 'You appear to be offline', description: 'Check your connection and try again.' };
    case apiError.status === 401:
      return { title: 'Your session has ended', description: 'Please sign in again.' };
    case apiError.is('email_not_verified'):
      return { title: 'Verify your e-mail address', description: 'Open the link we sent you to unlock the rest of Meridian.' };
    case apiError.status === 403:
      return { title: 'Access denied', description: 'You do not have permission to do this. Ask an administrator if you need access.' };
    case apiError.status === 404:
      return { title: 'Not found', description: 'It may have been deleted, or the link is wrong.' };
    case apiError.status === 409:
      return { title: 'This change conflicts with existing data', description: apiError.detail };
    case apiError.status === 412:
      return { title: 'Someone else changed this record', description: 'Reload to see the latest version, then make your change again.' };
    case apiError.status === 422:
      return { title: 'Please check your input', description: apiError.hasFieldErrors ? 'Some fields need your attention.' : apiError.detail };
    case apiError.status === 429:
      return {
        title: 'Too many requests',
        description: apiError.retryAfterSeconds ? `Please wait ${apiError.retryAfterSeconds} seconds and try again.` : 'Please wait a moment and try again.',
      };
    default:
      return { title: 'Something went wrong', description: `An unexpected error occurred on our side.${reference}` };
  }
}
