import { SearchX } from 'lucide-react';
import { Link } from 'react-router';
import { EmptyState } from '@/components/EmptyState';
import { Button } from '@/components/ui/button';
import { useDocumentTitle } from '@/hooks/use-document-title';
import { paths } from '@/routes/paths';

export default function NotFoundPage() {
  useDocumentTitle('Page not found');

  return (
    <EmptyState
      icon={SearchX}
      title="Page not found"
      description="The page you are looking for does not exist or has moved."
      action={
        <Button asChild variant="outline">
          <Link to={paths.dashboard}>Back to dashboard</Link>
        </Button>
      }
    />
  );
}
