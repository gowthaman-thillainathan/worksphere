import { NavLink } from 'react-router';
import { useAuth } from '@/shared/auth/use-auth';
import { cn } from '@/shared/lib/utils';
import { Logo } from './Logo';
import { navigation } from './navigation';

export function Sidebar({ onNavigate }: { onNavigate?: () => void }) {
  const { can } = useAuth();
  const items = navigation.filter((item) => !item.permission || can(item.permission));

  return (
    <div className="flex h-full flex-col gap-6">
      <Logo />
      <nav aria-label="Main">
        <ul className="grid gap-1">
          {items.map(({ label, to, icon: Icon }) => (
            <li key={to}>
              <NavLink
                to={to}
                onClick={onNavigate}
                className={({ isActive }) =>
                  cn(
                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground',
                    isActive ? 'bg-accent text-accent-foreground' : 'text-muted-foreground',
                  )
                }
              >
                <Icon className="size-4" aria-hidden="true" />
                {label}
              </NavLink>
            </li>
          ))}
        </ul>
      </nav>
    </div>
  );
}
