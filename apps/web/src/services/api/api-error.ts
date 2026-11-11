import { isAxiosError } from 'axios';

/** Field name → human-readable messages, as returned in a 422 problem document. */
export type FieldErrors = Record<string, string[]>;

interface ProblemDocument {
  status?: number;
  code?: string;
  detail?: string;
  request_id?: string | null;
  errors?: Record<string, { code: string; message: string }[]>;
}

const NETWORK_ERROR = 'network_error';
const UNKNOWN_ERROR = 'unknown_error';

/**
 * Every failed API call rejects with an ApiError: a normalised RFC 9457
 * problem (status, stable code, safe detail, field errors, request id).
 */
export class ApiError extends Error {
  readonly status: number;
  readonly code: string;
  readonly detail: string;
  readonly fieldErrors: FieldErrors;
  readonly requestId: string | null;
  readonly retryAfterSeconds: number | null;

  constructor(init: {
    status: number;
    code: string;
    detail: string;
    fieldErrors?: FieldErrors;
    requestId?: string | null;
    retryAfterSeconds?: number | null;
  }) {
    super(init.detail);
    this.name = 'ApiError';
    this.status = init.status;
    this.code = init.code;
    this.detail = init.detail;
    this.fieldErrors = init.fieldErrors ?? {};
    this.requestId = init.requestId ?? null;
    this.retryAfterSeconds = init.retryAfterSeconds ?? null;
  }

  static from(error: unknown): ApiError {
    if (error instanceof ApiError) {
      return error;
    }

    if (isAxiosError(error)) {
      if (!error.response) {
        return new ApiError({ status: 0, code: NETWORK_ERROR, detail: 'The server could not be reached.' });
      }

      const problem = (isProblem(error.response.data) ? error.response.data : {}) satisfies ProblemDocument;
      const retryAfter = Number(error.response.headers['retry-after']);

      return new ApiError({
        status: error.response.status,
        code: problem.code ?? UNKNOWN_ERROR,
        detail: problem.detail ?? error.message,
        fieldErrors: toFieldErrors(problem.errors),
        requestId: problem.request_id ?? null,
        retryAfterSeconds: Number.isFinite(retryAfter) ? retryAfter : null,
      });
    }

    return new ApiError({
      status: 0,
      code: UNKNOWN_ERROR,
      detail: error instanceof Error ? error.message : 'Unexpected error.',
    });
  }

  is(code: string): boolean {
    return this.code === code;
  }

  get hasFieldErrors(): boolean {
    return Object.keys(this.fieldErrors).length > 0;
  }
}

function isProblem(data: unknown): data is ProblemDocument {
  return typeof data === 'object' && data !== null && 'code' in data;
}

function toFieldErrors(errors: ProblemDocument['errors']): FieldErrors {
  if (!errors) {
    return {};
  }

  return Object.fromEntries(
    Object.entries(errors).map(([field, entries]) => [field, entries.map((entry) => entry.message)]),
  );
}
