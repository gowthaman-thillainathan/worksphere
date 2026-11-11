import { z } from 'zod';
import { DocumentCategory } from '@/types/api';

/**
 * Mirrors the API's document rules (config/documents.php). The API detects
 * the real type from the content, so this is for quick feedback only.
 */
export const DOCUMENT_RULES = {
  maxBytes: 10 * 1024 * 1024,
  accept: '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx',
  extensions: ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'],
  titleMaxLength: 150,
} as const;

export function checkDocumentFile(file: Pick<File, 'name' | 'size'>): string | null {
  const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

  if (!(DOCUMENT_RULES.extensions as readonly string[]).includes(extension)) return 'Choose a PDF, Word document or image.';
  if (file.size > DOCUMENT_RULES.maxBytes) return 'The file must be 10 MB or smaller.';

  return null;
}

function today(): string {
  const now = new Date();

  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

export const documentFormSchema = z.object({
  title: z.string().trim().min(1, 'Enter a title.').max(DOCUMENT_RULES.titleMaxLength),
  category: z.nativeEnum(DocumentCategory),
  expires_on: z.string().refine((value) => value === '' || value > today(), 'Choose a date in the future.'),
});

export type DocumentFormValues = z.infer<typeof documentFormSchema>;
