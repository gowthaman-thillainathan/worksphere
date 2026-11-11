import { Monitor, Moon, Sun } from 'lucide-react';
import { useState } from 'react';
import { useNavigate } from 'react-router';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/components/ConfirmDialog';
import { PageHeader } from '@/components/PageHeader';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ResendVerificationButton } from '@/features/auth/components/ResendVerificationButton';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import { useAuth, useCurrentUser } from '@/shared/auth/use-auth';
import { formatDateTime, humanize } from '@/shared/lib/format';
import { cn } from '@/shared/lib/utils';
import { useTheme, type ThemePreference } from '@/shared/theme/theme-context';

const THEMES: { value: ThemePreference; label: string; icon: typeof Sun }[] = [
  { value: 'light', label: 'Light', icon: Sun },
  { value: 'dark', label: 'Dark', icon: Moon },
  { value: 'system', label: 'System', icon: Monitor },
];

export default function SettingsPage() {
  useDocumentTitle('Settings');
  const { user, roles } = useCurrentUser();
  const { logoutEverywhere } = useAuth();
  const { preference, setPreference } = useTheme();
  const navigate = useNavigate();
  const [confirming, setConfirming] = useState(false);
  const [signingOut, setSigningOut] = useState(false);

  const signOutEverywhere = async () => {
    setSigningOut(true);
    await logoutEverywhere().catch(() => undefined);
    toast.success('You have been signed out on all devices.');
    void navigate(paths.login, { replace: true });
  };

  return (
    <>
      <PageHeader title="Settings" description="Your profile, appearance and security." />

      <Card>
        <CardHeader>
          <CardTitle>Profile</CardTitle>
        </CardHeader>
        <CardContent>
          <dl className="grid gap-4 sm:grid-cols-2">
            <div>
              <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Name</dt>
              <dd className="text-sm">{user.name}</dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">E-mail</dt>
              <dd className="flex flex-wrap items-center gap-2 text-sm">
                {user.email}
                <Badge variant={user.email_verified ? 'success' : 'warning'}>{user.email_verified ? 'Verified' : 'Not verified'}</Badge>
              </dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Roles</dt>
              <dd className="flex flex-wrap gap-1.5 pt-1">
                {roles.length === 0 ? <span className="text-sm">None</span> : roles.map((role) => <Badge key={role} variant="secondary">{humanize(role)}</Badge>)}
              </dd>
            </div>
            <div>
              <dt className="text-xs font-medium uppercase tracking-wide text-muted-foreground">Last sign-in</dt>
              <dd className="text-sm">{formatDateTime(user.last_login_at)}</dd>
            </div>
          </dl>
          {!user.email_verified && <ResendVerificationButton variant="outline" size="sm" className="mt-4" />}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Appearance</CardTitle>
          <CardDescription>System follows your device setting.</CardDescription>
        </CardHeader>
        <CardContent>
          <div role="radiogroup" aria-label="Theme" className="grid max-w-md grid-cols-3 gap-2">
            {THEMES.map(({ value, label, icon: Icon }) => (
              <button
                key={value}
                type="button"
                role="radio"
                aria-checked={preference === value}
                onClick={() => setPreference(value)}
                className={cn(
                  'flex flex-col items-center gap-2 rounded-lg border p-3 text-sm transition-colors hover:bg-accent',
                  preference === value && 'border-primary bg-primary/5 text-primary',
                )}
              >
                <Icon className="size-5" aria-hidden="true" />
                {label}
              </button>
            ))}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Security</CardTitle>
          <CardDescription>Signs you out on every device and browser, including this one.</CardDescription>
        </CardHeader>
        <CardContent>
          <Button variant="outline" onClick={() => setConfirming(true)}>
            Sign out everywhere
          </Button>
        </CardContent>
      </Card>

      <ConfirmDialog
        open={confirming}
        onOpenChange={setConfirming}
        title="Sign out on all devices?"
        description="Every active session ends immediately. You will need to sign in again."
        confirmLabel="Sign out everywhere"
        destructive
        pending={signingOut}
        onConfirm={signOutEverywhere}
      />
    </>
  );
}
