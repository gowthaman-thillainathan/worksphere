import { AlertTriangle } from 'lucide-react';
import { isRouteErrorResponse, useRouteError } from 'react-router';
import { EmptyState } from '@/components/EmptyState';
import { Button } from '@/components/ui/button';

/**
 * Last line of defence for errors thrown while rendering. Data errors are
 * handled where they occur (ErrorState); this catches programming errors
 * and failed chunk loads after a deploy.
 */
export function RouteErrorBoundary() {
  const error = useRouteError();
  const description = isRouteErrorResponse(error)
    ? `${error.status} ${error.statusText}`
    : 'An unexpected error occurred. Reloading usually helps; if it keeps happening, contact support.';

  return (
    <div className="flex min-h-dvh items-center justify-center p-6">
      <EmptyState
        icon={AlertTriangle}
        title="Something went wrong"
        description={description}
        action={<Button onClick={() => window.location.reload()}>Reload the page</Button>}
      />
    </div>
  );
}
