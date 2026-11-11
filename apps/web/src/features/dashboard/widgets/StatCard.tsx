import type { LucideIcon } from 'lucide-react';
import { Link } from 'react-router';
import { Card } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { describeError } from '@/shared/lib/error-messages';
import { formatNumber } from '@/shared/lib/format';

interface StatCardProps {
  label: string;
  icon: LucideIcon;
  value: number | undefined;
  isLoading: boolean;
  error: unknown;
  detail?: string;
  to: string;
}

export function StatCard({ label, icon: Icon, value, isLoading, error, detail, to }: StatCardProps) {
  return (
    <Card className="p-5">
      <div className="flex items-center justify-between">
        <h2 className="text-sm font-medium text-muted-foreground">{label}</h2>
        <Icon className="size-4 text-muted-foreground" aria-hidden="true" />
      </div>
      <div className="mt-3" aria-busy={isLoading || undefined}>
        {isLoading && <Skeleton className="h-8 w-20" />}
        {!isLoading && error !== null && <p className="text-sm text-destructive">{describeError(error).title}</p>}
        {!isLoading && error === null && value !== undefined && (
          <Link to={to} className="text-3xl font-semibold tracking-tight hover:underline">
            {formatNumber(value)}
          </Link>
        )}
      </div>
      {detail && <p className="mt-1 text-xs text-muted-foreground">{detail}</p>}
    </Card>
  );
}
