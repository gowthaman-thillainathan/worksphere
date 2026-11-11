import { Bell, BellOff } from 'lucide-react';
import { EmptyState } from '@/components/EmptyState';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

/**
 * Entry point for in-app notifications. The API does not expose
 * notifications yet (docs/01 Notification module), so the menu shows an
 * honest empty state instead of fake items.
 */
export function NotificationsMenu() {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" aria-label="Notifications">
          <Bell aria-hidden="true" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent className="w-72">
        <DropdownMenuLabel>Notifications</DropdownMenuLabel>
        <DropdownMenuSeparator />
        <EmptyState icon={BellOff} title="You're all caught up" description="Notifications will appear here." className="py-6" />
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
