import { Lock } from 'lucide-react';
import { useMemo } from 'react';
import { ErrorState } from '@/components/ErrorState';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { humanize } from '@/shared/lib/format';
import type { PermissionDefinition } from '@/types/api';
import { usePermissionCatalogue, useRoles } from './role-queries';

export default function RolesPage() {
  useDocumentTitle('Roles');
  const roles = useRoles();
  const permissions = usePermissionCatalogue();

  const byModule = useMemo(() => {
    const groups = new Map<string, PermissionDefinition[]>();
    permissions.data?.forEach((permission) => groups.set(permission.module, [...(groups.get(permission.module) ?? []), permission]));

    return [...groups.entries()];
  }, [permissions.data]);

  return (
    <>
      <PageHeader title="Roles" description="Roles bundle permissions. Users receive roles; the API checks permissions on every request." />

      <section aria-labelledby="roles-heading" className="space-y-3">
        <h2 id="roles-heading" className="text-lg font-semibold">
          Roles
        </h2>
        {roles.isError && <ErrorState error={roles.error} onRetry={() => roles.refetch()} />}
        <div className="grid gap-4 md:grid-cols-2" aria-busy={roles.isPending || undefined}>
          {roles.isPending && Array.from({ length: 4 }, (_, index) => <Skeleton key={index} className="h-40" />)}
          {roles.data?.map((role) => (
            <Card key={role.id}>
              <CardHeader>
                <div className="flex items-center justify-between gap-2">
                  <CardTitle>{role.name}</CardTitle>
                  {role.is_system && <Badge variant="secondary">System</Badge>}
                </div>
                {role.description && <CardDescription>{role.description}</CardDescription>}
              </CardHeader>
              <CardContent>
                <ul className="flex flex-wrap gap-1.5" aria-label={`Permissions of ${role.name}`}>
                  {role.permissions.map((permission) => (
                    <li key={permission}>
                      <Badge variant="outline" className="font-mono">
                        {permission}
                      </Badge>
                    </li>
                  ))}
                </ul>
              </CardContent>
            </Card>
          ))}
        </div>
      </section>

      <section aria-labelledby="permissions-heading" className="space-y-3">
        <h2 id="permissions-heading" className="text-lg font-semibold">
          Permission catalogue
        </h2>
        {permissions.isError && <ErrorState error={permissions.error} onRetry={() => permissions.refetch()} />}
        {permissions.isPending && <Skeleton className="h-64" />}
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          {byModule.map(([module, entries]) => (
            <Card key={module}>
              <CardHeader>
                <CardTitle>{humanize(module)}</CardTitle>
              </CardHeader>
              <CardContent>
                <dl className="space-y-3">
                  {entries.map((permission) => (
                    <div key={permission.key}>
                      <dt className="flex items-center gap-1.5 font-mono text-xs font-medium">
                        {permission.key}
                        {permission.is_sensitive && (
                          <Badge variant="warning" className="font-sans">
                            <Lock className="size-3" aria-hidden="true" />
                            Sensitive
                          </Badge>
                        )}
                      </dt>
                      <dd className="text-sm text-muted-foreground">{permission.description}</dd>
                    </div>
                  ))}
                </dl>
              </CardContent>
            </Card>
          ))}
        </div>
      </section>
    </>
  );
}
