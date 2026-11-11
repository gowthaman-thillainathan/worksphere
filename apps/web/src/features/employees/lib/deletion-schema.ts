import { z } from 'zod';

/** Mirrors the API rule for DELETE /employees/{id} (DeleteEmployeeRequest). */
export const DELETION_REASON = { min: 5, max: 500 } as const;

export const deletionSchema = z.object({
  reason: z
    .string()
    .trim()
    .min(DELETION_REASON.min, `Give a reason of at least ${DELETION_REASON.min} characters.`)
    .max(DELETION_REASON.max, `Keep the reason under ${DELETION_REASON.max} characters.`),
});

export type DeletionValues = z.infer<typeof deletionSchema>;
