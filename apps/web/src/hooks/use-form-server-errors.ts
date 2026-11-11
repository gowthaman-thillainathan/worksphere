import { useCallback } from 'react';
import type { FieldValues, Path, UseFormSetError } from 'react-hook-form';
import { toast } from 'sonner';
import { ApiError } from '@/services/api/api-error';
import { describeError } from '@/shared/lib/error-messages';

interface Options<Values extends FieldValues> {
  /** Form fields that can receive server messages (422 errors.{field}). */
  fields: readonly Path<Values>[];
  /** Business error codes (409/422) that belong to a specific field. */
  codeFields?: Partial<Record<string, Path<Values>>>;
  /** API field names that map to a differently named form field (e.g. manager_id → manager). */
  aliases?: Partial<Record<string, Path<Values>>>;
}

/**
 * Shows server-side validation where it belongs: field errors next to the
 * field (with focus on the first one), business errors on their field, and
 * anything else as a toast.
 */
export function useFormServerErrors<Values extends FieldValues>(
  setError: UseFormSetError<Values>,
  { fields, codeFields = {}, aliases = {} }: Options<Values>,
) {
  return useCallback(
    (error: unknown) => {
      const apiError = ApiError.from(error);
      const known = new Set<string>(fields);
      let focused = false;

      for (const [apiField, messages] of Object.entries(apiError.fieldErrors)) {
        const field = aliases[apiField] ?? apiField;

        if (known.has(field) && messages[0]) {
          setError(field as Path<Values>, { type: 'server', message: messages[0] }, { shouldFocus: !focused });
          focused = true;
        }
      }

      const codeField = codeFields[apiError.code];

      if (codeField) {
        setError(codeField, { type: 'server', message: apiError.detail }, { shouldFocus: !focused });
        focused = true;
      }

      if (!focused) {
        const { title, description } = describeError(apiError);
        toast.error(title, { description });
      }
    },
    [setError, fields, codeFields, aliases],
  );
}
