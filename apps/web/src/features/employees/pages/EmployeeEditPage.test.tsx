import { screen, waitFor, within } from '@testing-library/react';
import { delay, http, HttpResponse } from 'msw';
import { describe, expect, it } from 'vitest';
import { api } from '@/test/msw/api';
import { department, employee } from '@/test/msw/fixtures';
import { problem } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderApp } from '@/test/render';

const PERMISSIONS = { version: 1, permissions: ['update', 'delete', 'view_sensitive', 'update_sensitive'] } as const;

describe('Edit employee', () => {
  it('shows what someone else changed and re-applies your edits on the latest version', async () => {
    const original = employee({ id: 'e1', job_title: 'Engineer', status: 'active', meta: { ...PERMISSIONS, permissions: [...PERMISSIONS.permissions] } });
    const latest = { ...original, job_title: 'Lead Engineer', status: 'on_leave' as const, meta: { ...original.meta, version: 2 } };
    const patches: { ifMatch: string | null; body: unknown }[] = [];
    let current = original;
    server.use(
      http.get(api.departments, () => HttpResponse.json({ data: [department()], meta: { total: 1 } })),
      http.get(api.employee, () => HttpResponse.json({ data: current })),
      http.patch(api.employee, async ({ request }) => {
        patches.push({ ifMatch: request.headers.get('If-Match'), body: await request.json() });

        if (patches.length === 1) {
          current = latest; // someone else saved in the meantime

          return problem(412, 'version_mismatch', 'The resource has changed.');
        }

        return HttpResponse.json({ data: { ...latest, last_name: 'Perera-Silva', meta: { ...latest.meta, version: 3 } } });
      }),
    );
    const { user, router } = renderApp({ route: '/employees/e1/edit' });

    const lastName = await screen.findByLabelText(/last name/i);
    await user.clear(lastName);
    await user.type(lastName, 'Perera-Silva');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));

    const dialog = await screen.findByRole('dialog', { name: 'This employee was changed by someone else' });
    const table = within(dialog).getByRole('table');
    expect(within(table).getByRole('row', { name: /designation lead engineer/i })).toBeInTheDocument();
    expect(within(table).getByRole('row', { name: /status on leave/i })).toBeInTheDocument();

    await user.click(within(dialog).getByRole('button', { name: 'Reload and keep my changes' }));

    expect(screen.getByLabelText(/designation/i)).toHaveValue('Lead Engineer');
    expect(screen.getByLabelText(/last name/i)).toHaveValue('Perera-Silva');
    expect(screen.getByRole('heading', { name: /edit/i, level: 1 }).parentElement).toHaveTextContent('version 2');

    await user.click(screen.getByRole('button', { name: 'Save changes' }));

    await waitFor(() => expect(router.state.location.pathname).toBe('/employees/e1'));
    expect(patches).toEqual([
      { ifMatch: '"v1"', body: { last_name: 'Perera-Silva' } },
      { ifMatch: '"v2"', body: { last_name: 'Perera-Silva' } },
    ]);
  });

  it('can discard your edits in favour of the latest version', async () => {
    const original = employee({ id: 'e1', job_title: 'Engineer' });
    let current = original;
    server.use(
      http.get(api.departments, () => HttpResponse.json({ data: [], meta: { total: 0 } })),
      http.get(api.employee, () => HttpResponse.json({ data: current })),
      http.patch(api.employee, () => {
        current = { ...original, job_title: 'Lead Engineer', meta: { ...original.meta, version: 2 } };

        return problem(412, 'version_mismatch', 'The resource has changed.');
      }),
    );
    const { user } = renderApp({ route: '/employees/e1/edit' });

    const title = await screen.findByLabelText(/designation/i);
    await user.clear(title);
    await user.type(title, 'Principal Engineer');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));

    const dialog = await screen.findByRole('dialog');
    expect(within(dialog).getByText(/changed by both of you/)).toBeInTheDocument();
    await user.click(within(dialog).getByRole('button', { name: 'Discard my changes' }));

    expect(screen.getByLabelText(/designation/i)).toHaveValue('Lead Engineer');
  });

  it('keeps the department when the department list loads after the form', async () => {
    const original = employee({ id: 'e1', department_id: department().id, department_name: 'Engineering' });
    let body: unknown;
    server.use(
      http.get(api.departments, async () => {
        await delay(100);

        return HttpResponse.json({ data: [department()], meta: { total: 1 } });
      }),
      http.get(api.employee, () => HttpResponse.json({ data: original })),
      http.patch(api.employee, async ({ request }) => {
        body = await request.json();

        return HttpResponse.json({ data: original });
      }),
    );
    const { user } = renderApp({ route: '/employees/e1/edit' });

    await waitFor(() => expect(screen.getByLabelText(/department/i)).toHaveDisplayValue('Engineering'));
    await user.type(screen.getByLabelText(/designation/i), ' II');
    await user.click(screen.getByRole('button', { name: 'Save changes' }));

    await waitFor(() => expect(body).toEqual({ job_title: 'Engineer II' }));
  });
});
