import { useMutation } from '@tanstack/react-query';
import { toast } from 'sonner';
import { Button, type ButtonProps } from '@/components/ui/button';
import { AuthService } from '@/services/auth-service';
import { describeError } from '@/shared/lib/error-messages';

export function ResendVerificationButton(props: Omit<ButtonProps, 'onClick' | 'loading'>) {
  const resend = useMutation({
    mutationFn: () => AuthService.resendVerificationEmail(),
    onSuccess: () => toast.success('Verification e-mail sent. Check your inbox.'),
    onError: (error) => toast.error(describeError(error).title, { description: describeError(error).description }),
  });

  return (
    <Button {...props} onClick={() => resend.mutate()} loading={resend.isPending}>
      Resend verification e-mail
    </Button>
  );
}
