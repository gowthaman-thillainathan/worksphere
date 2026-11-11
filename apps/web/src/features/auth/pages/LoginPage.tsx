import { zodResolver } from '@hookform/resolvers/zod';
import { AlertCircle } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link, useNavigate, useSearchParams } from 'react-router';
import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths, REDIRECT_PARAM, safeRedirect } from '@/routes/paths';
import { ApiError } from '@/services/api/api-error';
import { useAuth } from '@/shared/auth/use-auth';
import { describeError } from '@/shared/lib/error-messages';
import { loginSchema, type LoginValues } from '../schemas/auth-schemas';

export default function LoginPage() {
  useDocumentTitle('Sign in');
  const { login } = useAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [formError, setFormError] = useState<string | null>(null);
  const sessionExpired = searchParams.get('reason') === 'expired';

  const { register, handleSubmit, formState } = useForm<LoginValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: '', password: '' },
  });

  const onSubmit = handleSubmit(async ({ email, password }) => {
    setFormError(null);

    try {
      await login(email, password);
      void navigate(safeRedirect(searchParams.get(REDIRECT_PARAM)), { replace: true });
    } catch (error) {
      const apiError = ApiError.from(error);
      setFormError(apiError.is('invalid_credentials') ? apiError.detail : describeError(apiError).description);
    }
  });

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight">Sign in</h1>
        <p className="text-sm text-muted-foreground">Welcome back. Sign in to manage your organisation.</p>
      </div>

      {(formError || sessionExpired) && (
        <div role="alert" className="flex gap-2 rounded-md border border-destructive/40 bg-destructive/10 p-3 text-sm">
          <AlertCircle className="mt-0.5 size-4 shrink-0 text-destructive" aria-hidden="true" />
          <span>{formError ?? 'Your session has ended. Please sign in again.'}</span>
        </div>
      )}

      <form onSubmit={onSubmit} noValidate className="grid gap-4">
        <FormField label="E-mail" error={formState.errors.email?.message} required>
          <Input type="email" autoComplete="username" {...register('email')} />
        </FormField>
        <FormField label="Password" error={formState.errors.password?.message} required>
          <Input type="password" autoComplete="current-password" {...register('password')} />
        </FormField>
        <div className="flex justify-end">
          <Link to={paths.forgotPassword} className="text-sm text-primary underline-offset-4 hover:underline">
            Forgot your password?
          </Link>
        </div>
        <Button type="submit" loading={formState.isSubmitting} className="w-full">
          Sign in
        </Button>
      </form>
    </div>
  );
}
