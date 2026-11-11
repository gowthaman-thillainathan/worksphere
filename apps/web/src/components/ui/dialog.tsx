import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/shared/lib/utils';

/**
 * Accessible dialogs on Radix: focus is trapped inside and returned to the
 * trigger on close, Escape closes, and the title/description are announced.
 */
interface OverlayPanelProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: ReactNode;
  description?: ReactNode;
  children?: ReactNode;
  footer?: ReactNode;
  className?: string;
}

function Overlay() {
  return <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-black/50" />;
}

function CloseButton() {
  return (
    <DialogPrimitive.Close className="absolute right-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100" aria-label="Close">
      <X className="size-4" aria-hidden="true" />
    </DialogPrimitive.Close>
  );
}

function Header({ title, description }: Pick<OverlayPanelProps, 'title' | 'description'>) {
  return (
    <div className="flex flex-col gap-1.5 pr-6">
      <DialogPrimitive.Title className="text-lg font-semibold leading-none">{title}</DialogPrimitive.Title>
      {description ? (
        <DialogPrimitive.Description className="text-sm text-muted-foreground">{description}</DialogPrimitive.Description>
      ) : (
        <DialogPrimitive.Description className="sr-only">{title}</DialogPrimitive.Description>
      )}
    </div>
  );
}

/** Centred modal dialog. */
export function Modal({ open, onOpenChange, title, description, children, footer, className }: OverlayPanelProps) {
  return (
    <DialogPrimitive.Root open={open} onOpenChange={onOpenChange}>
      <DialogPrimitive.Portal>
        <Overlay />
        <DialogPrimitive.Content
          className={cn(
            'fixed left-1/2 top-1/2 z-50 grid max-h-[90dvh] w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 -translate-y-1/2 gap-4 overflow-y-auto rounded-xl border bg-background p-6 shadow-lg',
            className,
          )}
        >
          <Header title={title} description={description} />
          {children}
          {footer && <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">{footer}</div>}
          <CloseButton />
        </DialogPrimitive.Content>
      </DialogPrimitive.Portal>
    </DialogPrimitive.Root>
  );
}

/** Panel sliding in from the side; used for forms and the mobile navigation. */
export function Drawer({ open, onOpenChange, title, description, children, footer, className, side = 'right' }: OverlayPanelProps & { side?: 'left' | 'right' }) {
  return (
    <DialogPrimitive.Root open={open} onOpenChange={onOpenChange}>
      <DialogPrimitive.Portal>
        <Overlay />
        <DialogPrimitive.Content
          className={cn(
            'fixed inset-y-0 z-50 flex w-full max-w-md flex-col gap-4 border bg-background p-6 shadow-lg',
            side === 'right' ? 'right-0' : 'left-0',
            className,
          )}
        >
          <Header title={title} description={description} />
          <div className="-mx-6 flex-1 overflow-y-auto px-6">{children}</div>
          {footer && <div className="flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">{footer}</div>}
          <CloseButton />
        </DialogPrimitive.Content>
      </DialogPrimitive.Portal>
    </DialogPrimitive.Root>
  );
}

