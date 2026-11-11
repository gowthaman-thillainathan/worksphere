/**
 * Request URL patterns for MSW handlers. The application itself never builds
 * URLs (it uses the generated client); tests need patterns to intercept them.
 */
const BASE = '*/api/v1';

export const api = {
  login: `${BASE}/auth/login`,
  refresh: `${BASE}/auth/refresh`,
  logout: `${BASE}/auth/logout`,
  me: `${BASE}/me`,
  employees: `${BASE}/employees`,
  employee: `${BASE}/employees/:id`,
  headcount: `${BASE}/employees/headcount`,
  departments: `${BASE}/departments`,
  auditEvents: `${BASE}/audit-events`,
} as const;
