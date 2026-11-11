import type { ComponentProps } from 'react';
import { cn } from '@/shared/lib/utils';

export const inputClassName =
  'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition-colors placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive file:border-0 file:bg-transparent file:text-sm file:font-medium';

export function Input({ className, type = 'text', ...props }: ComponentProps<'input'>) {
  return <input type={type} className={cn(inputClassName, className)} {...props} />;
}
