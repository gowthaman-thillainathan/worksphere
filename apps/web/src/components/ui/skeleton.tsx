import type { ComponentProps } from 'react';
import { cn } from '@/shared/lib/utils';

/** Decorative placeholder; wrap groups in an element with aria-busy for screen readers. */
export function Skeleton({ className, ...props }: ComponentProps<'div'>) {
  return <div aria-hidden="true" className={cn('animate-pulse rounded-md bg-muted', className)} {...props} />;
}
