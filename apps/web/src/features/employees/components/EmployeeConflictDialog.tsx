import { AlertTriangle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Modal } from '@/components/ui/dialog';
import type { FieldDifference } from '../lib/edit-conflict';
import { formatFormValue } from '../lib/form-values';
import { FIELD_LABELS } from '../lib/labels';

interface EmployeeConflictDialogProps {
  open: boolean;
  /** Who saved the newer version, if known, and what they changed. */
  differences: FieldDifference[];
  departmentName: (id: string) => string | undefined;
  onDiscardMine: () => void;
  onKeepMine: () => void;
  onCancel: () => void;
}

/**
 * Optimistic concurrency, made visible: the record changed after you opened
 * it (412). Nothing is overwritten silently — you see what changed and
 * choose to take the latest version or re-apply your edits on top of it.
 */
export function EmployeeConflictDialog({ open, differences, departmentName, onDiscardMine, onKeepMine, onCancel }: EmployeeConflictDialogProps) {
  const overlapping = differences.filter((difference) => difference.yours !== undefined).length;

  return (
    <Modal
      open={open}
      onOpenChange={(next) => !next && onCancel()}
      title="This employee was changed by someone else"
      description="Your changes have not been saved. Review what changed since you opened the form."
      className="max-w-2xl"
      footer={
        <>
          <Button variant="outline" onClick={onDiscardMine}>
            Discard my changes
          </Button>
          <Button onClick={onKeepMine}>Reload and keep my changes</Button>
        </>
      }
    >
      {overlapping > 0 && (
        <p className="flex items-start gap-2 rounded-md bg-warning/15 p-3 text-sm" role="note">
          <AlertTriangle className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
          {overlapping === 1 ? 'One field was' : `${overlapping} fields were`} changed by both of you. Keeping your changes will replace their value after you save again.
        </p>
      )}
      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-sm">
          <caption className="sr-only">Changes made by someone else</caption>
          <thead className="bg-muted/40 text-left text-muted-foreground">
            <tr>
              <th scope="col" className="px-3 py-2 font-medium">
                Field
              </th>
              <th scope="col" className="px-3 py-2 font-medium">
                Latest version
              </th>
              <th scope="col" className="px-3 py-2 font-medium">
                Your change
              </th>
            </tr>
          </thead>
          <tbody>
            {differences.map((difference) => (
              <tr key={difference.field} className="border-t">
                <th scope="row" className="px-3 py-2 text-left font-medium">
                  {FIELD_LABELS[difference.field] ?? difference.field}
                </th>
                <td className="px-3 py-2">{formatFormValue(difference.field, difference.theirs, departmentName)}</td>
                <td className="px-3 py-2">
                  {difference.yours === undefined ? <span className="text-muted-foreground">—</span> : <strong>{formatFormValue(difference.field, difference.yours, departmentName)}</strong>}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {differences.length === 0 && <p className="text-sm text-muted-foreground">Only fields you cannot see here were changed.</p>}
    </Modal>
  );
}
