import { QueryClient } from '@tanstack/react-query';
import { ApiError } from '@/services/api/api-error';

const RETRYABLE_STATUSES = new Set([0, 408, 429, 502, 503, 504]);
const MAX_RETRIES = 2;

/**
 * Retry only transient failures; 4xx answers are final and shown to the user.
 */
export function createQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 30_000,
        refetchOnWindowFocus: true,
        retry: (failureCount, error) => failureCount < MAX_RETRIES && RETRYABLE_STATUSES.has(ApiError.from(error).status),
      },
      mutations: {
        retry: false,
      },
    },
  });
}
