import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { Link, useNavigate, useSearchParams } from 'react-router';
import { toast } from 'sonner';
import { ErrorState } from '@/components/ErrorState';
import { FormField } from '@/components/FormField';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { useFormServerErrors } from '@/hooks/use-form-server-errors';
import { paths } from '@/routes/paths';
import { ApiError } from '@/services/api/api-error';
import { AuthService } from '@/services/auth-service';
import { PASSWORD_MIN_LENGTH, resetPasswordSchema, type ResetPasswordValues } from '../schemas/auth-schemas';

const FIELDS = ['password', 'password_confirmation'] as const;
const CODE_FIELDS = { invalid_password_reset_token: 'password' } as const;

export default function ResetPasswordPage() {
  useDocumentTitle('Reset password');
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const token = searchParams.get('token');
  const email = searchParams.get('email');

  const { register, handleSubmit, setError, formState } = useForm<ResetPasswordValues>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: { password: '', password_confirmation: '' },
  });
  const handleServerError = useFormServerErrors(setError, { fields: FIELDS, codeFields: CODE_FIELDS });

  if (!token || !email) {
    return (
      <ErrorState
        error={new ApiError({ status: 404, code: 'invalid_link', detail: 'This link is incomplete.' })}
        className="py-4"
      />
    );
  }

  const onSubmit = handleSubmit(async (values) => {
    try {
      await AuthService.resetPassword({ token, email, ...values });
      toast.success('Your password has been changed. Please sign in.');
      void navigate(paths.login, { replace: true });
    } catch (error) {
      handleServerError(error);
    }
  });

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold tracking-tight">Choose a new password</h1>
        <p className="text-sm text-muted-foreground">
          For <span className="font-medium text-foreground">{email}</span>. You will be signed out on all other devices.
        </p>
      </div>
      <form onSubmit={onSubmit} noValidate className="grid gap-4">
        <input type="email" autoComplete="username" value={email} readOnly hidden />
        <FormField label="New password" error={formState.errors.password?.message} hint={`At least ${PASSWORD_MIN_LENGTH} characters.`} required>
          <Input type="password" autoComplete="new-password" {...register('password')} />
        </FormField>
        <FormField label="Confirm new password" error={formState.errors.password_confirmation?.message} required>
          <Input type="password" autoComplete="new-password" {...register('password_confirmation')} />
        </FormField>
        <Button type="submit" loading={formState.isSubmitting} className="w-full">
          Change password
        </Button>
        <Link to={paths.forgotPassword} className="text-center text-sm text-primary underline-offset-4 hover:underline">
          Request a new link
        </Link>
      </form>
    </div>
  );
}
