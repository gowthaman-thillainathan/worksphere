import { ChevronRight } from 'lucide-react';
import { Link, useMatches } from 'react-router';
import { crumbOf } from '@/routes/route-handle';

export function Breadcrumbs() {
  const crumbs = useMatches()
    .map((match) => ({ label: crumbOf(match), to: match.pathname }))
    .filter((crumb): crumb is { label: string; to: string } => Boolean(crumb.label));

  if (crumbs.length === 0) {
    return null;
  }

  return (
    <nav aria-label="Breadcrumb" className="min-w-0">
      <ol className="flex items-center gap-1 text-sm text-muted-foreground">
        {crumbs.map((crumb, index) => {
          const isLast = index === crumbs.length - 1;

          return (
            <li key={crumb.to} className="flex min-w-0 items-center gap-1">
              {index > 0 && <ChevronRight className="size-3.5 shrink-0" aria-hidden="true" />}
              {isLast ? (
                <span aria-current="page" className="truncate font-medium text-foreground">
                  {crumb.label}
                </span>
              ) : (
                <Link to={crumb.to} className="truncate hover:text-foreground">
                  {crumb.label}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
