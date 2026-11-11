import { Navigate, Outlet, useLocation } from 'react-router';
import { LoadingScreen } from '@/components/LoadingScreen';
import { useAuth } from '@/shared/auth/use-auth';
import { paths, REDIRECT_PARAM } from './paths';

/** Protected area: waits for session restore, then requires a signed-in user. */
export function RequireAuth() {
  const { state } = useAuth();
  const location = useLocation();

  if (state.status === 'loading') {
    return <LoadingScreen label="Restoring your session…" />;
  }

  if (state.status === 'unauthenticated') {
    const search = new URLSearchParams({ [REDIRECT_PARAM]: `${location.pathname}${location.search}` });

    if (state.reason === 'expired') {
      search.set('reason', 'expired');
    }

    return <Navigate to={`${paths.login}?${search.toString()}`} replace />;
  }

  return <Outlet />;
}
