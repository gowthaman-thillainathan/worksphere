import { screen, waitFor, within } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { describe, expect, it } from 'vitest';
import { api } from '@/test/msw/api';
import { department, employee } from '@/test/msw/fixtures';
import { problem } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderApp } from '@/test/render';
import type { Employee } from '@/types/api';

function page(data: Employee[], nextCursor: string | null = null) {
  return HttpResponse.json({ data, meta: { per_page: 25, next_cursor: nextCursor, prev_cursor: null }, links: { next: null, prev: null } });
}

describe('Employee directory', () => {
  it('lists employees and forwards search, filters and sort to the API', async () => {
    const requests: URLSearchParams[] = [];
    server.use(
      http.get(api.departments, () => HttpResponse.json({ data: [department()], meta: { total: 1 } })),
      http.get(api.employees, ({ request }) => {
        requests.push(new URL(request.url).searchParams);

        return page([employee({ display_name: 'Nimal Perera' })], 'next-cursor');
      }),
    );
    const { user, router } = renderApp({ route: '/employees' });

    expect(await screen.findByRole('link', { name: 'Nimal Perera' })).toBeInTheDocument();

    await user.type(screen.getByRole('searchbox', { name: 'Search employees' }), 'nim');
    await waitFor(() => expect(router.state.location.search).toContain('q=nim'));
    await user.click(screen.getByRole('button', { name: /hired/i }));
    await user.click(screen.getByRole('button', { name: 'Next' }));

    await waitFor(() => {
      const last = requests.at(-1);
      expect(last?.get('q')).toBe('nim');
      expect(last?.get('sort')).toBe('hire_date');
      expect(last?.get('cursor')).toBe('next-cursor');
    });
  });

  it('removes a deleted employee immediately and rolls back if the API refuses', async () => {
    const nimal = employee({ display_name: 'Nimal Perera' });
    server.use(
      http.get(api.departments, () => HttpResponse.json({ data: [], meta: { total: 0 } })),
      http.get(api.employees, () => page([nimal])),
      http.delete(api.employee, () => problem(409, 'employee_has_direct_reports', "Reassign this employee's direct reports before deleting them.")),
    );
    const { user } = renderApp({ route: '/employees' });

    await user.click(await screen.findByRole('button', { name: 'Actions for Nimal Perera' }));
    await user.click(await screen.findByRole('menuitem', { name: 'Delete' }));
    await user.click(within(await screen.findByRole('dialog')).getByRole('button', { name: 'Delete employee' }));

    expect(await screen.findByText("Reassign this employee's direct reports before deleting them.")).toBeInTheDocument();
    expect(await screen.findByRole('link', { name: 'Nimal Perera' })).toBeInTheDocument();
  });

  it('shows a clear error when the directory cannot be loaded', async () => {
    server.use(
      http.get(api.departments, () => HttpResponse.json({ data: [], meta: { total: 0 } })),
      http.get(api.employees, () => problem(500, 'internal_error', 'Oops')),
    );
    renderApp({ route: '/employees' });

    expect(await screen.findByRole('alert')).toHaveTextContent('Something went wrong');
  });
});
