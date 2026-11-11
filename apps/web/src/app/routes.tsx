import { lazy } from 'react';
import { Navigate, type RouteObject } from 'react-router';
import { RouteErrorBoundary } from '@/features/errors/RouteErrorBoundary';
import { AppLayout } from '@/layouts/AppLayout';
import { AuthLayout } from '@/layouts/AuthLayout';
import { GuestOnly } from '@/routes/GuestOnly';
import { paths } from '@/routes/paths';
import { RequireAuth } from '@/routes/RequireAuth';
import { RequirePermission } from '@/routes/RequirePermission';
import type { RouteHandle } from '@/routes/route-handle';
import { Permission } from '@/types/permissions';

/*
 * Every page is its own chunk (code splitting); layouts render them inside
 * <Suspense> while the chunk loads.
 */
const LoginPage = lazy(() => import('@/features/auth/pages/LoginPage'));
const ForgotPasswordPage = lazy(() => import('@/features/auth/pages/ForgotPasswordPage'));
const ResetPasswordPage = lazy(() => import('@/features/auth/pages/ResetPasswordPage'));
const VerifyEmailPage = lazy(() => import('@/features/auth/pages/VerifyEmailPage'));
const DashboardPage = lazy(() => import('@/features/dashboard/DashboardPage'));
const EmployeeListPage = lazy(() => import('@/features/employees/pages/EmployeeListPage'));
const EmployeeDetailPage = lazy(() => import('@/features/employees/pages/EmployeeDetailPage'));
const EmployeeCreatePage = lazy(() => import('@/features/employees/pages/EmployeeCreatePage'));
const EmployeeEditPage = lazy(() => import('@/features/employees/pages/EmployeeEditPage'));
const DepartmentsPage = lazy(() => import('@/features/departments/DepartmentsPage'));
const RolesPage = lazy(() => import('@/features/roles/RolesPage'));
const SettingsPage = lazy(() => import('@/features/settings/SettingsPage'));
const NotFoundPage = lazy(() => import('@/features/errors/NotFoundPage'));

const crumb = (label: string): RouteHandle => ({ crumb: label });

export const routes: RouteObject[] = [
  {
    errorElement: <RouteErrorBoundary />,
    children: [
      {
        element: <AuthLayout />,
        children: [
          {
            element: <GuestOnly />,
            children: [
              { path: paths.login, element: <LoginPage /> },
              { path: paths.forgotPassword, element: <ForgotPasswordPage /> },
              { path: paths.resetPassword, element: <ResetPasswordPage /> },
            ],
          },
          { path: paths.verifyEmail, element: <VerifyEmailPage /> },
        ],
      },
      {
        element: <RequireAuth />,
        children: [
          {
            element: <AppLayout />,
            children: [
              { index: true, element: <Navigate to={paths.dashboard} replace /> },
              { path: paths.dashboard, element: <DashboardPage />, handle: crumb('Dashboard') },
              {
                path: paths.employees,
                handle: crumb('Employees'),
                element: <RequirePermission permission={Permission.EmployeeView} />,
                children: [
                  { index: true, element: <EmployeeListPage /> },
                  {
                    path: 'new',
                    handle: crumb('New employee'),
                    element: <RequirePermission permission={Permission.EmployeeCreate} />,
                    children: [{ index: true, element: <EmployeeCreatePage /> }],
                  },
                  {
                    path: ':employeeId',
                    handle: crumb('Details'),
                    children: [
                      { index: true, element: <EmployeeDetailPage /> },
                      {
                        path: 'edit',
                        handle: crumb('Edit'),
                        element: <RequirePermission permission={Permission.EmployeeUpdate} />,
                        children: [{ index: true, element: <EmployeeEditPage /> }],
                      },
                    ],
                  },
                ],
              },
              {
                path: paths.departments,
                handle: crumb('Departments'),
                element: <RequirePermission permission={Permission.OrganisationView} />,
                children: [{ index: true, element: <DepartmentsPage /> }],
              },
              {
                path: paths.roles,
                handle: crumb('Roles'),
                element: <RequirePermission permission={Permission.AccessManage} />,
                children: [{ index: true, element: <RolesPage /> }],
              },
              { path: paths.settings, element: <SettingsPage />, handle: crumb('Settings') },
              { path: '*', element: <NotFoundPage /> },
            ],
          },
        ],
      },
    ],
  },
];
