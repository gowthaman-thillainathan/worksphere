import { zodResolver } from '@hookform/resolvers/zod';
import { MailCheck } from 'lucide-react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Link } from 'react-router';
import { EmptyState } from '@/components/EmptyState';
import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { useFormServerErrors } from '@/hooks/use-form-server-errors';
import { paths } from '@/routes/paths';
import { AuthService } from '@/services/auth-service';
import { forgotPasswordSchema, type ForgotPasswordValues } from '../schemas/auth-schemas';

const FIELDS = ['email'] as const;

export default function ForgotPasswordPage() {
  useDocumentTitle('Forgot password');
  const [sentTo, setSentTo] = useState<string | null>(null);
  const { register, handleSubmit, setError, formState } = useForm<ForgotPasswordValues>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: { email: '' },
  });
  const handleServerError = useFormServerErrors(setError, { fields: FIELDS });

  const onSubmit = handleSubmit(async ({ email }) => {
    try {
      await AuthService.requestPasswordReset(email);
      setSentTo(email);
    } catch (error) {
      handleServerError(error);
    }
  });

  if (sentTo) {
    return (
      <EmptyState
        icon={MailCheck}
        title="Check your inbox"
        description={`If an account exists for ${sentTo}, we have sent a link to reset the password. The link is valid for 30 minutes.`}
        action={
          <Button asChild variant="outline">
            <Link to={paths.login}>Back to sign in</Link>
          </Button>
        }
        className="py-4"
      />
    );
  }

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight">Forgot your password?</h1>
        <p className="text-sm text-muted-foreground">Enter your e-mail and we will send you a reset link.</p>
      </div>
      <form onSubmit={onSubmit} noValidate className="grid gap-4">
        <FormField label="E-mail" error={formState.errors.email?.message} required>
          <Input type="email" autoComplete="email" {...register('email')} />
        </FormField>
        <Button type="submit" loading={formState.isSubmitting} className="w-full">
          Send reset link
        </Button>
        <Link to={paths.login} className="text-center text-sm text-primary underline-offset-4 hover:underline">
          Back to sign in
        </Link>
      </form>
    </div>
  );
}
