import { screen, within } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { describe, expect, it } from 'vitest';
import { api } from '@/test/msw/api';
import { auditEvent, department, document, employee, role } from '@/test/msw/fixtures';
import { server } from '@/test/msw/server';
import { renderApp } from '@/test/render';

const nimal = employee({
  id: 'e1',
  display_name: 'Nimal Perera',
  department_id: department().id,
  department_name: 'Engineering',
  has_account: true,
  meta: { version: 4, permissions: ['update', 'delete', 'view_sensitive', 'update_sensitive', 'manage_account'] },
});

function profileHandlers() {
  return [
    http.get(api.employee, () => HttpResponse.json({ data: nimal })),
    http.get(api.departments, () => HttpResponse.json({ data: [department(), department({ id: 'd2', name: 'Finance', code: 'FIN' })], meta: { total: 2 } })),
    http.get(api.roles, () => HttpResponse.json({ data: [role(), role({ id: 'r2', key: 'hr_admin', name: 'HR Administrator' })] })),
    http.get(api.employeeAccount, () =>
      HttpResponse.json({
        data: { user_id: 'u9', type: 'employee_account', email: nimal.work_email, status: 'active', email_verified: false, role: 'auditor', last_login_at: null, created_at: '2026-11-10T08:00:00Z' },
      }),
    ),
    http.get(api.employeeDocuments, () => HttpResponse.json({ data: [document(), document({ id: 'doc2', title: 'Passport', category: 'id_proof', confidential: true })] })),
    http.get(api.auditEvents, ({ request }) => {
      const perPage = new URL(request.url).searchParams.get('per_page');
      const events = [
        auditEvent({ id: 3, action: 'employee.role_changed', changes: { role: ['auditor', 'hr_admin'] } }),
        auditEvent({ id: 2, action: 'employee.updated', changes: { department_id: [department().id, 'd2'] } }),
        auditEvent({ id: 1, action: 'employee.created', actor: { type: 'system', id: null, name: null } }),
      ];

      return HttpResponse.json({ data: events.slice(0, Number(perPage)), meta: { per_page: Number(perPage), next_cursor: null, prev_cursor: null }, links: { next: null, prev: null } });
    }),
  ];
}

describe('Employee profile', () => {
  it('shows the overview with account and recent activity', async () => {
    server.use(...profileHandlers());
    renderApp({ route: '/employees/e1' });

    expect(await screen.findByRole('heading', { name: 'Nimal Perera', level: 1 })).toBeInTheDocument();
    expect(screen.getByRole('tab', { name: /overview/i })).toHaveAttribute('aria-selected', 'true');
    expect(screen.getAllByText('Engineering').length).toBeGreaterThan(0);
    expect(await screen.findByText('Invitation pending')).toBeInTheDocument();
    expect(await screen.findByText('Role changed')).toBeInTheDocument();
    expect(screen.getByText('Auditor → HR Administrator')).toBeInTheDocument();
  });

  it('switches tabs with the keyboard and shows the full audit history', async () => {
    server.use(...profileHandlers());
    const { user, router } = renderApp({ route: '/employees/e1' });

    (await screen.findByRole('tab', { name: /overview/i })).focus();
    await user.keyboard('{End}');

    expect(screen.getByRole('tab', { name: /audit history/i })).toHaveFocus();
    expect(router.state.location.search).toBe('?tab=history');

    const history = await screen.findByRole('tabpanel');
    expect(await within(history).findByText('Department changed')).toBeInTheDocument();
    expect(await within(history).findByText((_, element) => element?.tagName === 'DD' && /Engineering\s*→\s*Finance/.test(element.textContent ?? ''))).toBeInTheDocument();
    expect(within(history).getByText('Employee created')).toBeInTheDocument();
    expect(within(history).getAllByText(/Ayesha Fernando/).length).toBeGreaterThan(0);
    expect(within(history).getByText(/System/)).toBeInTheDocument();
  });

  it('lists documents and marks confidential ones', async () => {
    server.use(...profileHandlers());
    renderApp({ route: '/employees/e1?tab=documents' });

    const list = await screen.findByRole('list', { name: 'Documents' });
    expect(within(list).getAllByRole('listitem')).toHaveLength(2);
    expect(within(list).getByRole('button', { name: /download employment contract/i })).toBeInTheDocument();
    expect(within(list).getByRole('button', { name: 'Delete Passport' })).toBeInTheDocument();
    expect(within(list).getByText('Confidential')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Upload document' })).toBeInTheDocument();
  });

  it('hides sections the user may not see', async () => {
    server.use(http.get(api.employee, () => HttpResponse.json({ data: { ...nimal, meta: { version: 4, permissions: [] } } })));
    renderApp({ route: '/employees/e1', session: { permissions: ['employee.view'] } });

    expect(await screen.findByRole('heading', { name: 'Nimal Perera', level: 1 })).toBeInTheDocument();
    expect(screen.queryByRole('tab', { name: /documents/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('tab', { name: /audit history/i })).not.toBeInTheDocument();
    expect(screen.queryByText('Sign-in access')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /delete/i })).not.toBeInTheDocument();
    expect(screen.getByText(/Visible to people allowed to see personal data/)).toBeInTheDocument();
  });
});
