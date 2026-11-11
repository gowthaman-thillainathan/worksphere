import { AlertTriangle, Lock, SearchX, WifiOff } from 'lucide-react';
import { ApiError } from '@/services/api/api-error';
import { describeError } from '@/shared/lib/error-messages';
import { cn } from '@/shared/lib/utils';
import { Button } from './ui/button';

interface ErrorStateProps {
  error: unknown;
  /** Offer a retry when the failure may be transient. */
  onRetry?: () => void;
  className?: string;
}

function StatusIcon({ status }: { status: number }) {
  const className = 'size-6 text-destructive';

  if (status === 0) return <WifiOff className={className} aria-hidden="true" />;
  if (status === 403) return <Lock className={className} aria-hidden="true" />;
  if (status === 404) return <SearchX className={className} aria-hidden="true" />;

  return <AlertTriangle className={className} aria-hidden="true" />;
}

/** Turns a failed query into a clear, actionable message (role="alert"). */
export function ErrorState({ error, onRetry, className }: ErrorStateProps) {
  const apiError = ApiError.from(error);
  const { title, description } = describeError(apiError);
  const retryable = onRetry && ![401, 403, 404].includes(apiError.status);

  return (
    <div role="alert" className={cn('flex flex-col items-center justify-center gap-3 px-6 py-12 text-center', className)}>
      <div className="rounded-full bg-destructive/10 p-3">
        <StatusIcon status={apiError.status} />
      </div>
      <div className="space-y-1">
        <p className="font-medium">{title}</p>
        <p className="max-w-md text-sm text-muted-foreground">{description}</p>
      </div>
      {retryable && (
        <Button variant="outline" size="sm" onClick={onRetry}>
          Try again
        </Button>
      )}
    </div>
  );
}
