import { KeyRound } from 'lucide-react';
import { useId, useState } from 'react';
import { toast } from 'sonner';
import { ErrorState } from '@/components/ErrorState';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { useRoles } from '@/features/roles/role-queries';
import { describeError } from '@/shared/lib/error-messages';
import { formatDateTime } from '@/shared/lib/format';
import type { Employee, Role } from '@/types/api';
import { useEmployeeAccount } from '../api/employee-queries';
import { useChangeEmployeeRole, useCreateEmployeeAccount } from '../api/employee-mutations';

function notifyError(error: unknown) {
  const { title, description } = describeError(error);
  toast.error(title, { description });
}

function RoleSelect({ roles, value, onChange, disabled }: { roles: Role[] | undefined; value: string; onChange: (role: string) => void; disabled?: boolean }) {
  const id = useId();

  return (
    <div className="grid gap-2">
      <Label htmlFor={id}>Role</Label>
      <Select id={id} value={value} onChange={(event) => onChange(event.target.value)} disabled={disabled || !roles}>
        <option value="" disabled>
          Choose a role
        </option>
        {roles?.map((role) => (
          <option key={role.key} value={role.key}>
            {role.name}
          </option>
        ))}
      </Select>
    </div>
  );
}

/**
 * Sign-in access for the employee (ADR-0007). Shown only to people who may
 * manage accounts and roles (meta.permissions contains manage_account).
 */
export function EmployeeAccountCard({ employee }: { employee: Employee }) {
  const roles = useRoles();
  const account = useEmployeeAccount(employee.id, employee.has_account);
  const create = useCreateEmployeeAccount(employee.id);
  const changeRole = useChangeEmployeeRole(employee.id);
  const [role, setRole] = useState('');
  const currentRole = account.data?.role ?? '';
  const roleName = (key: string) => roles.data?.find((item) => item.key === key)?.name ?? key;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <KeyRound className="size-4" aria-hidden="true" />
          Sign-in access
        </CardTitle>
        <CardDescription>
          {employee.has_account ? 'The employee signs in with their work e-mail address.' : `${employee.display_name} cannot sign in to Meridian yet.`}
        </CardDescription>
      </CardHeader>
      <CardContent className="grid gap-4">
        {!employee.has_account && (
          <>
            <RoleSelect roles={roles.data} value={role} onChange={setRole} />
            <Button
              disabled={role === ''}
              loading={create.isPending}
              onClick={() =>
                create.mutate(role, {
                  onSuccess: () => toast.success('Invitation sent.', { description: `${employee.work_email} can now set a password.` }),
                  onError: notifyError,
                })
              }
            >
              Give access and send invitation
            </Button>
          </>
        )}

        {employee.has_account && account.isPending && <Skeleton className="h-24" />}
        {employee.has_account && account.isError && <ErrorState error={account.error} onRetry={() => account.refetch()} className="py-4" />}
        {account.data && (
          <>
            <dl className="grid gap-2 text-sm">
              <div className="flex justify-between gap-2">
                <dt className="text-muted-foreground">E-mail</dt>
                <dd className="truncate">{account.data.email}</dd>
              </div>
              <div className="flex justify-between gap-2">
                <dt className="text-muted-foreground">Status</dt>
                <dd>{account.data.email_verified ? <Badge variant="success">Active</Badge> : <Badge variant="warning">Invitation pending</Badge>}</dd>
              </div>
              <div className="flex justify-between gap-2">
                <dt className="text-muted-foreground">Last sign-in</dt>
                <dd>{account.data.last_login_at ? formatDateTime(account.data.last_login_at) : 'Never'}</dd>
              </div>
            </dl>
            <RoleSelect
              roles={roles.data}
              value={currentRole}
              disabled={changeRole.isPending}
              onChange={(next) =>
                changeRole.mutate(next, {
                  onSuccess: () => toast.success(`Role changed to ${roleName(next)}.`),
                  onError: notifyError,
                })
              }
            />
          </>
        )}
      </CardContent>
    </Card>
  );
}
