import { Camera, FileText, History, LayoutGrid, Pencil, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router';
import { toast } from 'sonner';
import { ErrorState } from '@/components/ErrorState';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, type TabItem } from '@/components/ui/tabs';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import { useAuth } from '@/shared/auth/use-auth';
import { describeError } from '@/shared/lib/error-messages';
import { formatDate, formatDateTime } from '@/shared/lib/format';
import type { Employee } from '@/types/api';
import { Permission } from '@/types/permissions';
import { useEmployee } from '../api/employee-queries';
import { useRemoveEmployeePhoto } from '../api/employee-mutations';
import { EmployeeAccountCard } from '../components/EmployeeAccountCard';
import { EmployeeAuditTimeline, EmployeeRecentActivity } from '../components/EmployeeAuditTimeline';
import { EmployeeAvatar } from '../components/EmployeeAvatar';
import { EmployeeDeleteDialog } from '../components/EmployeeDeleteDialog';
import { EmployeeDetailsPanel } from '../components/EmployeeDetailsPanel';
import { EmployeeDocuments } from '../components/EmployeeDocuments';
import { EmployeeStatusBadge } from '../components/EmployeeStatusBadge';
import { PhotoUploadDialog } from '../components/PhotoUploadDialog';

type ProfileTab = 'overview' | 'documents' | 'history';

const TAB_PARAM = 'tab';
const PROFILE_TABS: readonly ProfileTab[] = ['overview', 'documents', 'history'];
const isProfileTab = (value: string | null): value is ProfileTab => (PROFILE_TABS as readonly (string | null)[]).includes(value);

export default function EmployeeDetailPage() {
  const { employeeId = '' } = useParams();
  const employee = useEmployee(employeeId);
  useDocumentTitle(employee.data?.display_name ?? 'Employee');

  if (employee.isPending) return <ProfileSkeleton />;
  if (employee.isError) return <ErrorState error={employee.error} onRetry={() => employee.refetch()} />;

  return <EmployeeProfile employee={employee.data} />;
}

function EmployeeProfile({ employee }: { employee: Employee }) {
  const { can } = useAuth();
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const [photoOpen, setPhotoOpen] = useState(false);
  const [deleting, setDeleting] = useState<Employee[]>([]);
  const removePhoto = useRemoveEmployeePhoto(employee);
  const allowed = employee.meta.permissions;
  const tabParam = searchParams.get(TAB_PARAM);
  const tab: ProfileTab = isProfileTab(tabParam) ? tabParam : 'overview';
  const canSeeHistory = can(Permission.AuditView);

  const showTab = (next: ProfileTab) =>
    setSearchParams(
      (current) => {
        if (next === 'overview') current.delete(TAB_PARAM);
        else current.set(TAB_PARAM, next);

        return current;
      },
      { replace: true },
    );

  const onRemovePhoto = () =>
    removePhoto.mutate(undefined, {
      onSuccess: () => toast.success('Photo removed.'),
      onError: (error) => toast.error(describeError(error).title, { description: describeError(error).description }),
    });

  const tabs: TabItem<ProfileTab>[] = [
    {
      key: 'overview',
      label: (
        <>
          <LayoutGrid className="size-4" aria-hidden="true" />
          Overview
        </>
      ),
      content: (
        <div className="grid gap-6 lg:grid-cols-3">
          <div className="lg:col-span-2">
            <EmployeeDetailsPanel employee={employee} showPersonalData={allowed.includes('view_sensitive')} />
          </div>
          <div className="grid content-start gap-6">
            {allowed.includes('manage_account') && <EmployeeAccountCard employee={employee} />}
            {canSeeHistory && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-base">Recent activity</CardTitle>
                </CardHeader>
                <CardContent>
                  <EmployeeRecentActivity employeeId={employee.id} onShowAll={() => showTab('history')} />
                </CardContent>
              </Card>
            )}
          </div>
        </div>
      ),
    },
    ...(can(Permission.DocumentView)
      ? [
          {
            key: 'documents' as const,
            label: (
              <>
                <FileText className="size-4" aria-hidden="true" />
                Documents
              </>
            ),
            content: <EmployeeDocuments employeeId={employee.id} />,
          },
        ]
      : []),
    ...(canSeeHistory
      ? [
          {
            key: 'history' as const,
            label: (
              <>
                <History className="size-4" aria-hidden="true" />
                Audit history
              </>
            ),
            content: <EmployeeAuditTimeline employeeId={employee.id} />,
          },
        ]
      : []),
  ];

  return (
    <>
      <Card>
        <CardContent className="flex flex-col gap-5 pt-5 md:flex-row md:items-center">
          <div className="flex flex-col items-center gap-3 sm:flex-row sm:items-center">
            <EmployeeAvatar employee={employee} size="lg" />
            <div className="space-y-1 text-center sm:text-left">
              <h1 className="text-2xl font-semibold tracking-tight outline-none" tabIndex={-1} data-page-title>
                {employee.display_name}
              </h1>
              <p className="text-sm text-muted-foreground">
                {employee.job_title}
                {employee.department_name && ` · ${employee.department_name}`}
              </p>
              <div className="flex flex-wrap items-center justify-center gap-2 text-xs text-muted-foreground sm:justify-start">
                <EmployeeStatusBadge status={employee.status} />
                <span className="font-mono">{employee.employee_number}</span>
                <span>Joined {formatDate(employee.hire_date)}</span>
              </div>
            </div>
          </div>
          <div className="flex flex-wrap justify-center gap-2 md:ml-auto md:justify-end">
            {allowed.includes('update') && (
              <>
                <Button variant="outline" size="sm" onClick={() => setPhotoOpen(true)}>
                  <Camera aria-hidden="true" />
                  {employee.photo_url ? 'Change photo' : 'Add photo'}
                </Button>
                {employee.photo_url && (
                  <Button variant="ghost" size="sm" onClick={onRemovePhoto} loading={removePhoto.isPending}>
                    <X aria-hidden="true" />
                    Remove photo
                  </Button>
                )}
                <Button asChild size="sm">
                  <Link to={paths.editEmployee(employee.id)}>
                    <Pencil aria-hidden="true" />
                    Edit
                  </Link>
                </Button>
              </>
            )}
            {allowed.includes('delete') && (
              <Button variant="outline" size="sm" className="text-destructive" onClick={() => setDeleting([employee])}>
                <Trash2 aria-hidden="true" />
                Delete
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      <Tabs label="Employee profile" tabs={tabs} value={tab} onValueChange={showTab} />

      <p className="text-xs text-muted-foreground">
        Created {formatDateTime(employee.created_at)} · Last updated {formatDateTime(employee.updated_at)} · Version {employee.meta.version}
      </p>

      <PhotoUploadDialog employee={employee} open={photoOpen} onOpenChange={setPhotoOpen} />
      <EmployeeDeleteDialog employees={deleting} onOpenChange={(open) => !open && setDeleting([])} onDeleted={() => void navigate(paths.employees)} />
    </>
  );
}

function ProfileSkeleton() {
  return (
    <div className="space-y-6" aria-busy="true" aria-label="Loading employee">
      <Skeleton className="h-32" />
      <Skeleton className="h-10 w-80" />
      <div className="grid gap-6 lg:grid-cols-3">
        <Skeleton className="h-64 lg:col-span-2" />
        <Skeleton className="h-64" />
      </div>
    </div>
  );
}
