import { cn } from '@/shared/lib/utils';
import { initials } from '@/shared/lib/format';

const sizes = { sm: 'size-8 text-xs', md: 'size-10 text-sm', lg: 'size-20 text-xl' } as const;

interface AvatarProps {
  name: string;
  /** Image URL; falls back to initials when absent. */
  src?: string;
  size?: keyof typeof sizes;
  className?: string;
}

export function Avatar({ name, src, size = 'md', className }: AvatarProps) {
  return (
    <span className={cn('relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary/10 font-medium text-primary', sizes[size], className)}>
      {src ? <img src={src} alt={name} className="size-full object-cover" /> : <span aria-hidden="true">{initials(name)}</span>}
      {!src && <span className="sr-only">{name}</span>}
    </span>
  );
}
