import { CircleCheck, CircleX, MailCheck } from 'lucide-react';
import { Link, useSearchParams } from 'react-router';
import { EmptyState } from '@/components/EmptyState';
import { Button } from '@/components/ui/button';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';
import { useAuth } from '@/shared/auth/use-auth';
import { ResendVerificationButton } from '../components/ResendVerificationButton';

/**
 * Landing page for the e-mail verification link. The API verifies the signed
 * link and redirects here with ?status=verified or ?status=failed.
 */
export default function VerifyEmailPage() {
  useDocumentTitle('Verify e-mail');
  const [searchParams] = useSearchParams();
  const { state, reloadProfile } = useAuth();
  const signedIn = state.status === 'authenticated';
  const status = searchParams.get('status');

  const continueLink = (
    <Button asChild onClick={() => (signedIn ? reloadProfile() : undefined)}>
      <Link to={signedIn ? paths.dashboard : paths.login}>{signedIn ? 'Continue to Meridian' : 'Sign in'}</Link>
    </Button>
  );

  if (status === 'verified') {
    return <EmptyState icon={CircleCheck} title="Your e-mail address is verified" description="You now have full access to Meridian." action={continueLink} className="py-4" />;
  }

  if (status === 'failed') {
    return (
      <EmptyState
        icon={CircleX}
        title="This verification link is invalid or has expired"
        description={signedIn ? 'Request a new link below.' : 'Sign in to request a new verification link.'}
        action={signedIn ? <ResendVerificationButton /> : continueLink}
        className="py-4"
      />
    );
  }

  return (
    <EmptyState
      icon={MailCheck}
      title="Check your inbox"
      description="Open the link in the e-mail we sent you to verify your address."
      action={signedIn ? <ResendVerificationButton variant="outline" /> : continueLink}
      className="py-4"
    />
  );
}
