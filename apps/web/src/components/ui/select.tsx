import type { ComponentProps } from 'react';
import { cn } from '@/shared/lib/utils';
import { inputClassName } from './input';

/**
 * A styled native <select>: fully keyboard- and screen-reader-accessible,
 * and the best experience on mobile.
 */
export function Select({ className, ...props }: ComponentProps<'select'>) {
  return <select className={cn(inputClassName, 'pr-8', className)} {...props} />;
}
