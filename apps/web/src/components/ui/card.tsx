import type { ComponentProps } from 'react';
import { cn } from '@/shared/lib/utils';

export function Card({ className, ...props }: ComponentProps<'section'>) {
  return <section className={cn('rounded-xl border bg-card text-card-foreground shadow-xs', className)} {...props} />;
}

export function CardHeader({ className, ...props }: ComponentProps<'div'>) {
  return <div className={cn('flex flex-col gap-1 p-5 pb-3', className)} {...props} />;
}

export function CardTitle({ className, children, ...props }: ComponentProps<'h2'>) {
  return (
    <h2 className={cn('text-base font-semibold leading-none', className)} {...props}>
      {children}
    </h2>
  );
}

export function CardDescription({ className, ...props }: ComponentProps<'p'>) {
  return <p className={cn('text-sm text-muted-foreground', className)} {...props} />;
}

export function CardContent({ className, ...props }: ComponentProps<'div'>) {
  return <div className={cn('p-5 pt-0', className)} {...props} />;
}
