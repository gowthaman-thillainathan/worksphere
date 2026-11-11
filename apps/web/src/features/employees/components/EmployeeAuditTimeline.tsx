import { Briefcase, Building2, Camera, FileText, History, KeyRound, Pencil, RotateCcw, Trash2, UserPlus, type LucideIcon } from 'lucide-react';
import { useCallback, useMemo } from 'react';
import { EmptyState } from '@/components/EmptyState';
import { ErrorState } from '@/components/ErrorState';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useDepartments } from '@/features/departments/department-queries';
import { useRoles } from '@/features/roles/role-queries';
import { useAuth } from '@/shared/auth/use-auth';
import { formatDate, formatDateTime } from '@/shared/lib/format';
import { cn } from '@/shared/lib/utils';
import type { AuditEvent } from '@/types/api';
import { Permission } from '@/types/permissions';
import { useEmployeeHistory, useRecentActivity } from '../api/employee-queries';
import { groupByDay, presentEvent, type TimelineEntry, type TimelineKind, type TimelineLookups } from '../lib/audit-timeline';

const KIND_ICONS: Record<TimelineKind, LucideIcon> = {
  created: UserPlus,
  updated: Pencil,
  department: Building2,
  deleted: Trash2,
  restored: RotateCcw,
  photo: Camera,
  account: KeyRound,
  role: Briefcase,
  document: FileText,
};

const KIND_COLOURS: Partial<Record<TimelineKind, string>> = {
  created: 'bg-success/15 text-success',
  deleted: 'bg-destructive/15 text-destructive',
  restored: 'bg-success/15 text-success',
};

const timeFormatter = new Intl.DateTimeFormat(undefined, { timeStyle: 'short' });

/** Department and role names for the presenter, from the cached reference data. */
function useTimelineLookups(): TimelineLookups {
  const { can } = useAuth();
  const departments = useDepartments(can(Permission.OrganisationView));
  const roles = useRoles(can(Permission.AccessManage));

  const departmentName = useCallback((id: string) => departments.data?.departments.find((department) => department.id === id)?.name, [departments.data]);
  const roleName = useCallback((key: string) => roles.data?.find((role) => role.key === key)?.name, [roles.data]);

  return useMemo(() => ({ departmentName, roleName }), [departmentName, roleName]);
}

function usePresented(events: AuditEvent[] | undefined): TimelineEntry[] {
  const lookups = useTimelineLookups();

  return useMemo(() => (events ?? []).map((event) => presentEvent(event, lookups)), [events, lookups]);
}

function TimelineItem({ entry, compact = false }: { entry: TimelineEntry; compact?: boolean }) {
  const Icon = KIND_ICONS[entry.kind];

  return (
    <li className="relative flex gap-3 pb-5 last:pb-0">
      <span className={cn('relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary', KIND_COLOURS[entry.kind])} aria-hidden="true">
        <Icon className="size-4" />
      </span>
      <div className="min-w-0 flex-1 pt-1">
        <p className="text-sm font-medium">{entry.title}</p>
        <p className="text-xs text-muted-foreground">
          {entry.actor} · <time dateTime={entry.occurredAt} title={formatDateTime(entry.occurredAt)}>{compact ? formatDateTime(entry.occurredAt) : timeFormatter.format(new Date(entry.occurredAt))}</time>
        </p>
        {entry.description && <p className="mt-1 text-sm">{entry.description}</p>}
        {!compact && entry.changes.length > 0 && (
          <dl className="mt-2 grid gap-1 rounded-md bg-muted/50 p-2 text-xs">
            {entry.changes.map((change) => (
              <div key={change.field} className="flex flex-wrap gap-x-1">
                <dt className="font-medium">{change.field}:</dt>
                <dd>
                  <span className="text-muted-foreground line-through decoration-1">{change.from}</span> → {change.to}
                </dd>
              </div>
            ))}
          </dl>
        )}
      </div>
    </li>
  );
}

function TimelineSkeleton({ rows = 3 }: { rows?: number }) {
  return (
    <div className="space-y-4" aria-busy="true" aria-label="Loading history">
      {Array.from({ length: rows }, (_, index) => (
        <div key={index} className="flex gap-3">
          <Skeleton className="size-8 rounded-full" />
          <Skeleton className="h-10 flex-1" />
        </div>
      ))}
    </div>
  );
}

/** Every change to the employee, grouped by day, newest first, loaded page by page. */
export function EmployeeAuditTimeline({ employeeId }: { employeeId: string }) {
  const history = useEmployeeHistory(employeeId, true);
  const events = useMemo(() => history.data?.pages.flatMap((page) => page.data), [history.data]);
  const entries = usePresented(events);

  if (history.isPending) return <TimelineSkeleton />;
  if (history.isError) return <ErrorState error={history.error} onRetry={() => history.refetch()} />;
  if (entries.length === 0) return <EmptyState icon={History} title="No history yet" />;

  return (
    <div className="space-y-6">
      {groupByDay(entries).map((day) => (
        <section key={day.day} aria-label={formatDate(day.day)}>
          <h3 className="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{formatDate(day.day)}</h3>
          <ol className="relative before:absolute before:bottom-2 before:left-4 before:top-2 before:w-px before:bg-border">
            {day.entries.map((entry) => (
              <TimelineItem key={entry.id} entry={entry} />
            ))}
          </ol>
        </section>
      ))}
      {history.hasNextPage && (
        <Button variant="outline" className="w-full" onClick={() => void history.fetchNextPage()} loading={history.isFetchingNextPage}>
          Load older changes
        </Button>
      )}
    </div>
  );
}

/** The latest few changes, for the profile overview. */
export function EmployeeRecentActivity({ employeeId, onShowAll }: { employeeId: string; onShowAll: () => void }) {
  const recent = useRecentActivity(employeeId, true);
  const entries = usePresented(recent.data?.data);

  if (recent.isPending) return <TimelineSkeleton rows={2} />;
  if (recent.isError) return <ErrorState error={recent.error} onRetry={() => recent.refetch()} className="py-4" />;
  if (entries.length === 0) return <p className="text-sm text-muted-foreground">No activity yet.</p>;

  return (
    <div className="space-y-3">
      <ol className="relative before:absolute before:bottom-2 before:left-4 before:top-2 before:w-px before:bg-border">
        {entries.map((entry) => (
          <TimelineItem key={entry.id} entry={entry} compact />
        ))}
      </ol>
      <Button variant="link" size="sm" className="h-auto p-0" onClick={onShowAll}>
        View full history
      </Button>
    </div>
  );
}
