import { QueryClientProvider, type QueryClient } from '@tanstack/react-query';
import { useState, type ReactNode } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { AuthProvider } from '@/shared/auth/AuthProvider';
import { createQueryClient } from '@/shared/lib/query-client';
import { ThemeProvider } from '@/shared/theme/ThemeProvider';

/** Application-wide providers; tests render pages inside the same stack. */
export function AppProviders({ children, queryClient }: { children: ReactNode; queryClient?: QueryClient }) {
  const [client] = useState(() => queryClient ?? createQueryClient());

  return (
    <ThemeProvider>
      <QueryClientProvider client={client}>
        <AuthProvider>
          {children}
          <Toaster />
        </AuthProvider>
      </QueryClientProvider>
    </ThemeProvider>
  );
}
