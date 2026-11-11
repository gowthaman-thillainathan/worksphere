import { toast } from 'sonner';
import { describeError } from '@/shared/lib/error-messages';
import type { Settled } from '@/shared/lib/settle-all';
import type { Employee } from '@/types/api';

const MAX_LISTED_FAILURES = 3;

function plural(count: number): string {
  return count === 1 ? '1 employee' : `${count} employees`;
}

/** One toast summarising a bulk action, naming the employees that could not be changed and why. */
export function announceBulkOutcome(outcome: Settled<Employee, unknown>, verb: string): void {
  const { succeeded, failed } = outcome;

  if (failed.length === 0) {
    toast.success(`${plural(succeeded.length)} ${verb}.`);

    return;
  }

  const reasons = failed
    .slice(0, MAX_LISTED_FAILURES)
    .map(({ item, error }) => `${item.display_name}: ${describeError(error).description}`)
    .join(' ');
  const more = failed.length > MAX_LISTED_FAILURES ? ` and ${failed.length - MAX_LISTED_FAILURES} more.` : '';
  const title = succeeded.length > 0 ? `${plural(succeeded.length)} ${verb}; ${failed.length} could not be.` : `No employees were ${verb}.`;

  toast.error(title, { description: `${reasons}${more}` });
}
