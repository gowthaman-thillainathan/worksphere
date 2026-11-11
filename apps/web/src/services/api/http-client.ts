import axios, { type AxiosRequestConfig } from 'axios';
import { env } from '@/shared/config/env';

declare module 'axios' {
  interface AxiosRequestConfig {
    /** Do not try to refresh the session when this request returns 401 (auth endpoints). */
    skipAuthRefresh?: boolean;
    /** Internal: the request has already been retried after a refresh. */
    retriedAfterRefresh?: boolean;
  }
}

/**
 * The single axios instance every API call goes through. Authentication,
 * silent refresh and error normalisation are attached by
 * `installInterceptors` (interceptors.ts) when the application starts.
 */
export const http = axios.create({
  baseURL: env.apiBaseUrl,
  withCredentials: true, // sends the HttpOnly refresh cookie to /auth/*
  headers: { Accept: 'application/json' },
});

/**
 * Orval mutator: the generated client calls this for every operation and
 * receives the response body. Per-call options (e.g. an If-Match header)
 * are merged into the generated request, headers included.
 */
export function apiRequest<T>(config: AxiosRequestConfig, options?: AxiosRequestConfig): Promise<T> {
  return http
    .request<T>({
      ...config,
      ...options,
      headers: { ...config.headers, ...options?.headers },
    })
    .then((response) => response.data);
}

export type ErrorType<Error> = Error;
export type BodyType<Body> = Body;
