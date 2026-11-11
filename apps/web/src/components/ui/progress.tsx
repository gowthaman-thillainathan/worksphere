import { cn } from '@/shared/lib/utils';

interface ProgressProps {
  /** 0–1 */
  value: number;
  label: string;
  className?: string;
}

/** Determinate progress bar, announced to assistive technology. */
export function Progress({ value, label, className }: ProgressProps) {
  const percent = Math.round(Math.min(Math.max(value, 0), 1) * 100);

  return (
    <div
      role="progressbar"
      aria-label={label}
      aria-valuemin={0}
      aria-valuemax={100}
      aria-valuenow={percent}
      className={cn('h-2 w-full overflow-hidden rounded-full bg-muted', className)}
    >
      <div className="h-full bg-primary transition-[width] duration-200 motion-reduce:transition-none" style={{ width: `${percent}%` }} />
    </div>
  );
}
