import { Check } from 'lucide-react';
import { cn } from '@/shared/lib/utils';

export interface StepDefinition {
  key: string;
  title: string;
  description?: string;
}

interface StepperProps {
  steps: readonly StepDefinition[];
  current: number;
  /** Steps before the current one can be revisited. */
  onSelect?: (index: number) => void;
  className?: string;
}

/** Progress through a multi-step form; the current step is marked with aria-current. */
export function Stepper({ steps, current, onSelect, className }: StepperProps) {
  return (
    <nav aria-label="Progress" className={className}>
      <ol className="grid gap-2 sm:grid-cols-[repeat(auto-fit,minmax(0,1fr))] sm:gap-4">
        {steps.map((step, index) => {
          const done = index < current;
          const active = index === current;
          const content = (
            <>
              <span
                className={cn(
                  'flex size-7 shrink-0 items-center justify-center rounded-full border text-xs font-semibold',
                  done && 'border-primary bg-primary text-primary-foreground',
                  active && 'border-primary text-primary',
                  !done && !active && 'text-muted-foreground',
                )}
                aria-hidden="true"
              >
                {done ? <Check className="size-4" /> : index + 1}
              </span>
              <span className="min-w-0 text-left">
                <span className={cn('block text-sm font-medium', !active && !done && 'text-muted-foreground')}>{step.title}</span>
                {step.description && <span className="hidden text-xs text-muted-foreground lg:block">{step.description}</span>}
                <span className="sr-only">{done ? ' (completed)' : active ? ' (current step)' : ''}</span>
              </span>
            </>
          );

          return (
            <li key={step.key} aria-current={active ? 'step' : undefined} className={cn('border-l-2 pl-3 sm:border-l-0 sm:border-t-2 sm:pl-0 sm:pt-3', done || active ? 'border-primary' : 'border-border')}>
              {done && onSelect ? (
                <button type="button" onClick={() => onSelect(index)} className="flex items-center gap-3 rounded-md hover:underline">
                  {content}
                </button>
              ) : (
                <div className="flex items-center gap-3">{content}</div>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
