import { Suspense, useEffect, useRef, useState } from 'react';
import { Outlet, useLocation } from 'react-router';
import { LoadingScreen } from '@/components/LoadingScreen';
import { Drawer } from '@/components/ui/dialog';
import { VerifyEmailBanner } from '@/features/auth/components/VerifyEmailBanner';
import { useCurrentUser } from '@/shared/auth/use-auth';
import { Sidebar } from './Sidebar';
import { TopNav } from './TopNav';

/**
 * Authenticated shell: sidebar (a drawer below lg), top bar, breadcrumbs and
 * the routed page. After each navigation, focus moves to the new page title
 * so keyboard and screen-reader users start at the right place.
 */
export function AppLayout() {
  const { user } = useCurrentUser();
  const location = useLocation();
  const mainRef = useRef<HTMLElement>(null);
  const [navigationOpen, setNavigationOpen] = useState(false);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      const title = mainRef.current?.querySelector<HTMLElement>('[data-page-title]');
      (title ?? mainRef.current)?.focus({ preventScroll: true });
      window.scrollTo({ top: 0 });
    });

    return () => cancelAnimationFrame(frame);
  }, [location.pathname]);

  return (
    <div className="flex min-h-dvh">
      <a href="#main-content" className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-background focus:px-4 focus:py-2 focus:shadow">
        Skip to content
      </a>

      <aside className="hidden w-64 shrink-0 border-r bg-card px-4 py-5 lg:block">
        <div className="sticky top-5">
          <Sidebar />
        </div>
      </aside>

      <Drawer open={navigationOpen} onOpenChange={setNavigationOpen} title="Navigation" side="left" className="max-w-72">
        <Sidebar onNavigate={() => setNavigationOpen(false)} />
      </Drawer>

      <div className="flex min-w-0 flex-1 flex-col">
        <TopNav onOpenNavigation={() => setNavigationOpen(true)} />
        {!user.email_verified && <VerifyEmailBanner />}
        <main id="main-content" ref={mainRef} tabIndex={-1} className="mx-auto w-full max-w-7xl flex-1 space-y-6 p-4 outline-none sm:p-6 lg:p-8">
          <Suspense fallback={<LoadingScreen />}>
            <Outlet />
          </Suspense>
        </main>
      </div>
    </div>
  );
}
