import { Camera, Pencil, Trash2, X } from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { toast } from 'sonner';
import { ErrorState } from '@/components/ErrorState';
import { PageHeader } from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useDepartments } from '@/features/departments/department-queries';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import { useAuth } from '@/shared/auth/use-auth';
import { describeError } from '@/shared/lib/error-messages';
import { formatDate, formatDateTime } from '@/shared/lib/format';
import type { Employee } from '@/types/api';
import { Permission } from '@/types/permissions';
import { useEmployee, useRemoveEmployeePhoto } from '../api/employee-queries';
import { DeleteEmployeeDialog } from '../components/DeleteEmployeeDialog';
import { EmployeeAuditHistory } from '../components/EmployeeAuditHistory';
import { EmployeeAvatar } from '../components/EmployeeAvatar';
import { EmployeeStatusBadge } from '../components/EmployeeStatusBadge';
import { PhotoUploadDialog } from '../components/PhotoUploadDialog';
import { EMPLOYMENT_TYPE_LABELS } from '../lib/labels';

export default function EmployeeDetailPage() {
  const { employeeId = '' } = useParams();
  const employee = useEmployee(employeeId);
  useDocumentTitle(employee.data?.display_name ?? 'Employee');

  if (employee.isPending) {
    return <DetailSkeleton />;
  }

  if (employee.isError) {
    return <ErrorState error={employee.error} onRetry={() => employee.refetch()} />;
  }

  return <EmployeeDetail employee={employee.data} />;
}

function EmployeeDetail({ employee }: { employee: Employee }) {
  const { can } = useAuth();
  const navigate = useNavigate();
  const [photoOpen, setPhotoOpen] = useState(false);
  const [deleting, setDeleting] = useState<Employee | null>(null);
  const removePhoto = useRemoveEmployeePhoto(employee);
  const departments = useDepartments(can(Permission.OrganisationView));
  const departmentName = departments.data?.departments.find((department) => department.id === employee.department_id)?.name;
  const canUpdate = employee.meta.permissions.includes('update');

  const onRemovePhoto = () =>
    removePhoto.mutate(undefined, {
      onSuccess: () => toast.success('Photo removed.'),
      onError: (error) => toast.error(describeError(error).title, { description: describeError(error).description }),
    });

  return (
    <>
      <PageHeader
        title={employee.display_name}
        description={`${employee.job_title} · ${employee.employee_number}`}
        actions={
          <>
            {canUpdate && (
              <Button asChild variant="outline">
                <Link to={paths.editEmployee(employee.id)}>
                  <Pencil aria-hidden="true" />
                  Edit
                </Link>
              </Button>
            )}
            {employee.meta.permissions.includes('delete') && (
              <Button variant="outline" className="text-destructive" onClick={() => setDeleting(employee)}>
                <Trash2 aria-hidden="true" />
                Delete
              </Button>
            )}
          </>
        }
      />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-6 lg:col-span-2">
          <Card>
            <CardContent className="flex flex-col items-center gap-4 pt-5 sm:flex-row">
              <EmployeeAvatar employee={employee} size="lg" />
              <div className="flex-1 space-y-2 text-center sm:text-left">
                <EmployeeStatusBadge status={employee.status} />
                <p className="text-sm text-muted-foreground">{employee.work_email}</p>
              </div>
              {canUpdate && (
                <div className="flex gap-2">
                  <Button variant="outline" size="sm" onClick={() => setPhotoOpen(true)}>
                    <Camera aria-hidden="true" />
                    {employee.photo_url ? 'Change photo' : 'Add photo'}
                  </Button>
                  {employee.photo_url && (
                    <Button variant="ghost" size="sm" onClick={onRemovePhoto} loading={removePhoto.isPending}>
                      <X aria-hidden="true" />
                      Remove
                    </Button>
                  )}
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Employment</CardTitle>
            </CardHeader>
            <CardContent>
              <dl className="grid gap-4 sm:grid-cols-2">
                <Detail label="Employee number" value={<span className="font-mono">{employee.employee_number}</span>} />
                <Detail label="Employment type" value={EMPLOYMENT_TYPE_LABELS[employee.employment_type]} />
                <Detail label="Hire date" value={formatDate(employee.hire_date)} />
                <Detail label="Termination date" value={formatDate(employee.termination_date)} />
                <Detail
                  label="Manager"
                  value={employee.manager ? <Link to={paths.employee(employee.manager.id)} className="text-primary hover:underline">{employee.manager.display_name}</Link> : '—'}
                />
                {can(Permission.OrganisationView) && <Detail label="Department" value={departmentName ?? '—'} />}
              </dl>
            </CardContent>
          </Card>

          {can(Permission.EmployeeViewSensitive) && (
            <Card>
              <CardHeader>
                <CardTitle>Personal data</CardTitle>
              </CardHeader>
              <CardContent>
                <dl className="grid gap-4 sm:grid-cols-3">
                  <Detail label="Personal e-mail" value={employee.personal_email ?? '—'} />
                  <Detail label="Phone" value={employee.phone ?? '—'} />
                  <Detail label="Date of birth" value={formatDate(employee.date_of_birth)} />
                </dl>
              </CardContent>
            </Card>
          )}

          <p className="text-xs text-muted-foreground">
            Created {formatDateTime(employee.created_at)} · Last updated {formatDateTime(employee.updated_at)} · Version {employee.meta.version}
          </p>
        </div>

        {can(Permission.AuditView) && <EmployeeAuditHistory employeeId={employee.id} />}
      </div>

      <PhotoUploadDialog employee={employee} open={photoOpen} onOpenChange={setPhotoOpen} />
      <DeleteEmployeeDialog employee={deleting} onOpenChange={(open) => !open && setDeleting(null)} onDeleted={() => navigate(paths.employees)} />
    </>
  );
}

function Detail({ label, value }: { label: string; value: ReactNode }) {
  return (
    <div className="space-y-1">
      <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">{label}</dt>
      <dd className="text-sm">{value}</dd>
    </div>
  );
}

function DetailSkeleton() {
  return (
    <div className="space-y-6" aria-busy="true" aria-label="Loading employee">
      <Skeleton className="h-8 w-64" />
      <Skeleton className="h-32" />
      <Skeleton className="h-48" />
    </div>
  );
}
