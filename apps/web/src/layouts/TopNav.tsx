import { Menu } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Breadcrumbs } from './Breadcrumbs';
import { NotificationsMenu } from './NotificationsMenu';
import { ThemeToggle } from './ThemeToggle';
import { UserMenu } from './UserMenu';

export function TopNav({ onOpenNavigation }: { onOpenNavigation: () => void }) {
  return (
    <header className="sticky top-0 z-30 flex h-14 items-center gap-3 border-b bg-background/95 px-4 backdrop-blur sm:px-6">
      <Button variant="ghost" size="icon" className="lg:hidden" onClick={onOpenNavigation} aria-label="Open navigation">
        <Menu aria-hidden="true" />
      </Button>
      <div className="min-w-0 flex-1">
        <Breadcrumbs />
      </div>
      <div className="flex items-center gap-1">
        <ThemeToggle />
        <NotificationsMenu />
        <UserMenu />
      </div>
    </header>
  );
}
