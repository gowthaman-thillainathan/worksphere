import type { UIMatch } from 'react-router';

/** Static metadata attached to routes and read by breadcrumbs. */
export interface RouteHandle {
  crumb?: string;
}

export function crumbOf(match: UIMatch): string | undefined {
  return (match.handle as RouteHandle | undefined)?.crumb;
}
