import { useQuery } from '@tanstack/react-query';
import { Activity } from 'lucide-react';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { AuditService } from '@/services/audit-service';
import { formatDateTime, formatRelative, humanize } from '@/shared/lib/format';
import { queryKeys } from '@/shared/lib/query-keys';

const QUERY = { perPage: 8 };

export function RecentActivityWidget() {
  const activity = useQuery({
    queryKey: queryKeys.audit.list(QUERY),
    queryFn: ({ signal }) => AuditService.list(QUERY, signal),
  });

  return (
    <Card>
      <CardHeader>
        <CardTitle>Recent activity</CardTitle>
      </CardHeader>
      <CardContent>
        {activity.isPending && (
          <div className="space-y-3" aria-busy="true">
            {Array.from({ length: 5 }, (_, index) => (
              <Skeleton key={index} className="h-8" />
            ))}
          </div>
        )}
        {activity.isError && <ErrorState error={activity.error} onRetry={() => activity.refetch()} className="py-6" />}
        {activity.data?.data.length === 0 && <EmptyState icon={Activity} title="No activity yet" className="py-6" />}
        {activity.data && activity.data.data.length > 0 && (
          <ul className="space-y-3">
            {activity.data.data.map((event) => (
              <li key={event.id} className="flex items-start justify-between gap-3 text-sm">
                <div className="min-w-0">
                  <p className="font-medium">{humanize(event.action)}</p>
                  <p className="truncate text-xs text-muted-foreground">
                    {humanize(event.subject.type)} · {Object.keys(event.changes ?? {}).length} field(s)
                  </p>
                </div>
                <time dateTime={event.occurred_at} title={formatDateTime(event.occurred_at)} className="shrink-0 text-xs text-muted-foreground">
                  {formatRelative(event.occurred_at)}
                </time>
              </li>
            ))}
          </ul>
        )}
      </CardContent>
    </Card>
  );
}
