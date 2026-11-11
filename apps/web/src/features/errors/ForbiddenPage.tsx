import { Lock } from 'lucide-react';
import { Link } from 'react-router';
import { EmptyState } from '@/components/EmptyState';
import { Button } from '@/components/ui/button';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';

export function ForbiddenPage() {
  useDocumentTitle('Access denied');

  return (
    <EmptyState
      icon={Lock}
      title="You don't have access to this page"
      description="Ask an administrator if you need it."
      action={
        <Button asChild variant="outline">
          <Link to={paths.dashboard}>Back to dashboard</Link>
        </Button>
      }
    />
  );
}
