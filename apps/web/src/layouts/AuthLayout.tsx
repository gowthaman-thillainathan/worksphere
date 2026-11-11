import { Suspense } from 'react';
import { Outlet } from 'react-router';
import { LoadingScreen } from '@/components/LoadingScreen';
import { Logo } from './Logo';
import { ThemeToggle } from './ThemeToggle';

/** Centred layout for the public pages (sign-in, password, verification). */
export function AuthLayout() {
  return (
    <div className="flex min-h-dvh flex-col bg-muted/40">
      <header className="flex items-center justify-between p-4">
        <Logo />
        <ThemeToggle />
      </header>
      <main className="flex flex-1 items-start justify-center px-4 pb-16 pt-8 sm:items-center sm:pt-0">
        <div className="w-full max-w-md rounded-xl border bg-card p-6 shadow-sm sm:p-8">
          <Suspense fallback={<LoadingScreen />}>
            <Outlet />
          </Suspense>
        </div>
      </main>
    </div>
  );
}
