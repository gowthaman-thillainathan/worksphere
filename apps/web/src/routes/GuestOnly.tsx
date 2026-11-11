import { Navigate, Outlet, useSearchParams } from 'react-router';
import { LoadingScreen } from '@/components/LoadingScreen';
import { useAuth } from '@/shared/auth/use-auth';
import { REDIRECT_PARAM, safeRedirect } from './paths';

/** Sign-in pages: signed-in users are sent on to where they were going. */
export function GuestOnly() {
  const { state } = useAuth();
  const [searchParams] = useSearchParams();

  if (state.status === 'loading') {
    return <LoadingScreen />;
  }

  if (state.status === 'authenticated') {
    return <Navigate to={safeRedirect(searchParams.get(REDIRECT_PARAM))} replace />;
  }

  return <Outlet />;
}
