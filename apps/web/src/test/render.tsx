import { QueryClient } from '@tanstack/react-query';
import { render, type RenderResult } from '@testing-library/react';
import userEvent, { type UserEvent } from '@testing-library/user-event';
import { createMemoryRouter, RouterProvider, type RouteObject } from 'react-router';
import { AppProviders } from '@/app/AppProviders';
import { routes } from '@/app/routes';
import { server } from './msw/server';
import { sessionHandlers } from './msw/handlers';

interface RenderAppOptions {
  /** Initial URL. */
  route?: string;
  /** Session for this test; defaults to a signed-in user with every permission. */
  session?: Parameters<typeof sessionHandlers>[0];
  routes?: RouteObject[];
}

export interface RenderAppResult extends RenderResult {
  user: UserEvent;
  router: ReturnType<typeof createMemoryRouter>;
}

/** Renders the real application (providers, guards, layouts, lazy pages) at a URL. */
export function renderApp({ route = '/', session, routes: routeTable = routes }: RenderAppOptions = {}): RenderAppResult {
  server.use(...sessionHandlers(session));

  const queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false, staleTime: Infinity }, mutations: { retry: false } },
  });
  const router = createMemoryRouter(routeTable, { initialEntries: [route] });

  const result = render(
    <AppProviders queryClient={queryClient}>
      <RouterProvider router={router} />
    </AppProviders>,
  );

  return { ...result, user: userEvent.setup(), router };
}
