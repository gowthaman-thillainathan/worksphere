import { screen, waitFor, within } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { beforeEach, describe, expect, it } from 'vitest';
import { api } from '@/test/msw/api';
import { department, employee } from '@/test/msw/fixtures';
import { problem } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderApp } from '@/test/render';
import { emulateDesktopViewport, resetViewport } from '@/test/viewport';
import type { Employee } from '@/types/api';
import { COLUMN_PREFERENCES_KEY } from '../lib/table-columns';

function page(data: Employee[], nextCursor: string | null = null) {
  return HttpResponse.json({ data, meta: { per_page: 25, next_cursor: nextCursor, prev_cursor: null }, links: { next: null, prev: null } });
}

const departments = () => http.get(api.departments, () => HttpResponse.json({ data: [department()], meta: { total: 1 } }));

describe('Employee directory', () => {
  beforeEach(() => emulateDesktopViewport());

  it('forwards search, advanced filters, sort and paging to the API', async () => {
    const requests: URLSearchParams[] = [];
    server.use(
      departments(),
      http.get(api.employees, ({ request }) => {
        requests.push(new URL(request.url).searchParams);

        return page([employee({ display_name: 'Nimal Perera' })], 'next-cursor');
      }),
    );
    const { user, router } = renderApp({ route: '/employees' });

    expect(await screen.findByRole('link', { name: 'Nimal Perera' })).toBeInTheDocument();

    await user.type(screen.getByRole('searchbox', { name: 'Search employees' }), 'nim');
    await waitFor(() => expect(router.state.location.search).toContain('q=nim'));

    await user.click(screen.getByRole('button', { name: /filters/i }));
    const panel = await screen.findByRole('dialog', { name: 'Filter employees' });
    await user.type(within(panel).getByLabelText('Designation'), 'engineer');
    await user.click(within(panel).getByRole('checkbox', { name: 'On leave' }));
    await user.click(within(panel).getByRole('button', { name: 'Apply filters' }));

    expect(await screen.findByRole('list', { name: 'Applied filters' })).toHaveTextContent('Designation: engineer');

    await user.click(screen.getByRole('button', { name: /join date/i }));
    await user.click(screen.getByRole('button', { name: 'Next' }));

    await waitFor(() => {
      const last = requests.at(-1);
      expect(last?.get('q')).toBe('nim');
      expect(last?.get('filter[job_title]')).toBe('engineer');
      expect(last?.get('filter[status]')).toBe('on_leave');
      expect(last?.get('sort')).toBe('hire_date');
      expect(last?.get('cursor')).toBe('next-cursor');
    });
  });

  it('remembers which columns are shown', async () => {
    server.use(departments(), http.get(api.employees, () => page([employee({ department_name: 'Engineering' })])));
    const { user } = renderApp({ route: '/employees' });

    expect(await screen.findByRole('columnheader', { name: /department/i })).toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'Columns' }));
    await user.click(await screen.findByRole('menuitemcheckbox', { name: 'Department' }));
    await user.click(await screen.findByRole('menuitemcheckbox', { name: 'Manager' }));
    await user.keyboard('{Escape}');

    expect(screen.queryByRole('columnheader', { name: /department/i })).not.toBeInTheDocument();
    expect(screen.getByRole('columnheader', { name: 'Manager' })).toBeInTheDocument();
    expect(JSON.parse(localStorage.getItem(COLUMN_PREFERENCES_KEY) ?? '[]')).toEqual(['number', 'designation', 'status', 'manager', 'joined']);
  });

  it('requires a reason to delete and rolls back if the API refuses', async () => {
    const nimal = employee({ display_name: 'Nimal Perera' });
    let body: unknown;
    server.use(
      departments(),
      http.get(api.employees, () => page([nimal])),
      http.delete(api.employee, async ({ request }) => {
        body = await request.json();

        return problem(409, 'employee_has_direct_reports', "Reassign this employee's direct reports before deleting them.");
      }),
    );
    const { user } = renderApp({ route: '/employees' });

    await user.click(await screen.findByRole('button', { name: 'Actions for Nimal Perera' }));
    await user.click(await screen.findByRole('menuitem', { name: 'Delete' }));
    const dialog = await screen.findByRole('dialog', { name: 'Delete Nimal Perera?' });

    await user.click(within(dialog).getByRole('button', { name: 'Delete employee' }));
    expect(await within(dialog).findByText(/at least 5 characters/)).toBeInTheDocument();
    expect(body).toBeUndefined();

    await user.type(within(dialog).getByLabelText(/reason/i), 'Duplicate record');
    await user.click(within(dialog).getByRole('button', { name: 'Delete employee' }));

    expect(await screen.findByText("Reassign this employee's direct reports before deleting them.")).toBeInTheDocument();
    expect(body).toEqual({ reason: 'Duplicate record' });
    expect(await screen.findByRole('link', { name: 'Nimal Perera' })).toBeInTheDocument();
  });

  it('deletes selected employees in bulk with one reason and reports refusals', async () => {
    const ana = employee({ display_name: 'Ana Silva' });
    const ben = employee({ display_name: 'Ben Fernando' });
    const deleted: string[] = [];
    server.use(
      departments(),
      http.get(api.employees, () => page([ana, ben])),
      http.delete(api.employee, ({ params }) => {
        if (params.id === ben.id) return problem(409, 'employee_has_direct_reports', 'Has direct reports.');
        deleted.push(String(params.id));

        return new HttpResponse(null, { status: 204 });
      }),
    );
    const { user } = renderApp({ route: '/employees' });

    await user.click(await screen.findByRole('checkbox', { name: 'Select all rows on this page' }));
    const bar = screen.getByRole('region', { name: 'Bulk actions' });
    expect(bar).toHaveTextContent('2 selected');

    await user.click(within(bar).getByRole('button', { name: 'Delete 2' }));
    const dialog = await screen.findByRole('dialog', { name: 'Delete 2 employees?' });
    await user.type(within(dialog).getByLabelText(/reason/i), 'Left during probation');
    await user.click(within(dialog).getByRole('button', { name: 'Delete 2 employees' }));

    expect(await screen.findByText('1 employee deleted; 1 could not be.')).toBeInTheDocument();
    expect(screen.getByText(/Ben Fernando: Has direct reports./)).toBeInTheDocument();
    expect(deleted).toEqual([ana.id]);
  });

  it('restores a deleted employee from the deleted records', async () => {
    const gone = employee({ display_name: 'Kasun Jayasuriya', deleted_at: '2026-11-09T10:00:00Z', deletion_reason: 'Duplicate record' });
    const requests: URLSearchParams[] = [];
    let restored = false;
    server.use(
      departments(),
      http.get(api.employees, ({ request }) => {
        requests.push(new URL(request.url).searchParams);

        return page(restored ? [] : [gone]);
      }),
      http.post(api.employeeRestore, () => {
        restored = true;

        return HttpResponse.json({ data: { ...gone, deleted_at: null, deletion_reason: null } });
      }),
    );
    const { user } = renderApp({ route: '/employees?state=deleted' });

    await user.click(await screen.findByRole('button', { name: 'Actions for Kasun Jayasuriya' }));
    await user.click(await screen.findByRole('menuitem', { name: 'Restore' }));
    const dialog = await screen.findByRole('dialog', { name: 'Restore Kasun Jayasuriya?' });
    expect(dialog).toHaveTextContent('Duplicate record');
    await user.click(within(dialog).getByRole('button', { name: 'Restore employee' }));

    expect(await screen.findByText('Kasun Jayasuriya was restored.')).toBeInTheDocument();
    expect(requests[0]?.get('filter[trashed]')).toBe('only');
    await waitFor(() => expect(screen.queryByText('Kasun Jayasuriya')).not.toBeInTheDocument());
  });

  it('only offers what the user may do', async () => {
    server.use(http.get(api.employees, () => page([employee({ display_name: 'Nimal Perera', meta: { version: 1, permissions: [] } })])));
    const { user } = renderApp({ route: '/employees', session: { permissions: ['employee.view'] } });

    expect(await screen.findByRole('link', { name: 'Nimal Perera' })).toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'Add employee' })).not.toBeInTheDocument();
    expect(screen.queryByRole('checkbox', { name: /select/i })).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', { name: 'Actions for Nimal Perera' }));
    expect(await screen.findByRole('menuitem', { name: 'View profile' })).toBeInTheDocument();
    expect(screen.queryByRole('menuitem', { name: 'Edit' })).not.toBeInTheDocument();
    expect(screen.queryByRole('menuitem', { name: 'Delete' })).not.toBeInTheDocument();
  });

  it('shows cards on phones', async () => {
    resetViewport(); // jsdom matches no media query: the phone layout
    server.use(departments(), http.get(api.employees, () => page([employee({ display_name: 'Nimal Perera', job_title: 'Platform Engineer' })])));
    renderApp({ route: '/employees' });

    expect(await screen.findByText('Platform Engineer')).toBeInTheDocument();
    expect(screen.getByRole('checkbox', { name: 'Select Nimal Perera' })).toBeInTheDocument();
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });

  it('shows a clear error with a retry when the directory cannot be loaded', async () => {
    let attempts = 0;
    server.use(
      departments(),
      http.get(api.employees, () => {
        attempts += 1;

        return attempts === 1 ? problem(500, 'internal_error', 'Oops') : page([employee({ display_name: 'Nimal Perera' })]);
      }),
    );
    const { user } = renderApp({ route: '/employees' });

    expect(await screen.findByRole('alert')).toHaveTextContent('Something went wrong');
    await user.click(screen.getByRole('button', { name: /try again/i }));
    expect(await screen.findByRole('link', { name: 'Nimal Perera' })).toBeInTheDocument();
  });
});
