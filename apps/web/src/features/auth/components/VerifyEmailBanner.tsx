import { MailWarning } from 'lucide-react';
import { ResendVerificationButton } from './ResendVerificationButton';

/** Shown across the app until the user confirms their e-mail address. */
export function VerifyEmailBanner() {
  return (
    <div role="status" className="flex flex-col gap-3 border-b bg-warning/15 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
      <p className="flex items-center gap-2">
        <MailWarning className="size-4 shrink-0" aria-hidden="true" />
        Verify your e-mail address to unlock Meridian. Check your inbox for the link.
      </p>
      <ResendVerificationButton size="sm" variant="outline" />
    </div>
  );
}
