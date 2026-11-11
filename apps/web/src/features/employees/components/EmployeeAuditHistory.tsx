import { History } from 'lucide-react';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { formatDateTime, formatRelative, humanize } from '@/shared/lib/format';
import type { AuditEvent } from '@/types/api';
import { useEmployeeAuditTrail } from '../api/employee-queries';

const MASK = '***';

function describeValue(value: unknown): string {
  if (value === null || value === undefined || value === '') return 'empty';
  if (value === MASK) return 'hidden';

  if (typeof value === 'string') return value;
  if (typeof value === 'number' || typeof value === 'boolean') return String(value);

  return JSON.stringify(value);
}

function ChangeList({ event }: { event: AuditEvent }) {
  const entries = Object.entries(event.changes ?? {});

  if (entries.length === 0) return null;

  return (
    <ul className="mt-1 space-y-0.5 text-xs text-muted-foreground">
      {entries.map(([field, change]) => {
        const [before, after] = Array.isArray(change) ? change : [undefined, change];

        return (
          <li key={field}>
            <span className="font-medium text-foreground">{humanize(field)}</span>: {describeValue(before)} → {describeValue(after)}
          </li>
        );
      })}
    </ul>
  );
}

/** Who changed what and when, from the tamper-evident audit trail. */
export function EmployeeAuditHistory({ employeeId }: { employeeId: string }) {
  const trail = useEmployeeAuditTrail(employeeId, true);

  return (
    <Card>
      <CardHeader>
        <CardTitle>History</CardTitle>
        <CardDescription>Every change to this record, newest first.</CardDescription>
      </CardHeader>
      <CardContent>
        {trail.isPending && (
          <div className="space-y-3" aria-busy="true">
            {Array.from({ length: 3 }, (_, index) => (
              <Skeleton key={index} className="h-10" />
            ))}
          </div>
        )}
        {trail.isError && <ErrorState error={trail.error} onRetry={() => trail.refetch()} className="py-6" />}
        {trail.data?.data.length === 0 && <EmptyState icon={History} title="No history yet" className="py-6" />}
        {trail.data && trail.data.data.length > 0 && (
          <ol className="space-y-4 border-l pl-4">
            {trail.data.data.map((event) => (
              <li key={event.id} className="relative">
                <span className="absolute -left-[1.3rem] top-1.5 size-2 rounded-full bg-primary" aria-hidden="true" />
                <p className="text-sm font-medium">{humanize(event.action.replace(/^employee\./, ''))}</p>
                <p className="text-xs text-muted-foreground">
                  <time dateTime={event.occurred_at} title={formatDateTime(event.occurred_at)}>
                    {formatRelative(event.occurred_at)}
                  </time>
                  {' · '}
                  {event.actor.type === 'system' ? 'System' : 'User'}
                </p>
                <ChangeList event={event} />
              </li>
            ))}
          </ol>
        )}
      </CardContent>
    </Card>
  );
}
