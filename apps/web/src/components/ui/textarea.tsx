import type { ComponentProps } from 'react';
import { cn } from '@/shared/lib/utils';
import { inputClassName } from './input';

export function Textarea({ className, ...props }: ComponentProps<'textarea'>) {
  return <textarea className={cn(inputClassName, 'min-h-20 py-2', className)} {...props} />;
}
