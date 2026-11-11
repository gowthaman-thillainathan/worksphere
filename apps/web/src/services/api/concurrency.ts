import type { AxiosRequestConfig } from 'axios';

/**
 * Optimistic locking: writes send the version the user edited as If-Match
 * (the API answers 412 if someone else changed the record meanwhile).
 */
export function ifMatch(version: number): AxiosRequestConfig {
  return { headers: { 'If-Match': `"v${version}"` } };
}
