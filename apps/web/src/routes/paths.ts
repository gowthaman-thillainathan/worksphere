/** Every route of the application, in one place. */
export const paths = {
  login: '/login',
  forgotPassword: '/forgot-password',
  resetPassword: '/reset-password',
  verifyEmail: '/verify-email',
  dashboard: '/dashboard',
  employees: '/employees',
  newEmployee: '/employees/new',
  employee: (id: string) => `/employees/${id}`,
  editEmployee: (id: string) => `/employees/${id}/edit`,
  departments: '/departments',
  roles: '/roles',
  settings: '/settings',
} as const;

/** Query parameter carrying the page to return to after signing in. */
export const REDIRECT_PARAM = 'redirect';

/** Only same-app paths are accepted as redirect targets (no open redirects). */
export function safeRedirect(target: string | null): string {
  return target && target.startsWith('/') && !target.startsWith('//') ? target : paths.dashboard;
}
