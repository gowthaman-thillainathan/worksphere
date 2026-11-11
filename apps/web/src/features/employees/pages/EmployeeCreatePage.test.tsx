import { screen, waitFor, within } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { describe, expect, it } from 'vitest';
import { api } from '@/test/msw/api';
import { department, employee, role } from '@/test/msw/fixtures';
import { problem } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderApp, type RenderAppResult } from '@/test/render';

const referenceData = () => [
  http.get(api.departments, () => HttpResponse.json({ data: [department()], meta: { total: 1 } })),
  http.get(api.roles, () => HttpResponse.json({ data: [role(), role({ id: 'r2', key: 'hr_admin', name: 'HR Administrator' })] })),
];

async function fillPersonalStep({ user }: RenderAppResult, email = 'ishara@meridian.test') {
  await user.type(await screen.findByLabelText(/first name/i), 'Ishara');
  await user.type(screen.getByLabelText(/last name/i), 'Fernando');
  await user.type(screen.getByLabelText(/work e-mail/i), email);
  await user.click(screen.getByRole('button', { name: /next/i }));
}

async function fillEmploymentStep({ user }: RenderAppResult) {
  await user.type(await screen.findByLabelText(/designation/i), 'Payroll Specialist');
  await user.selectOptions(screen.getByLabelText(/department/i), 'Engineering');
  await user.click(screen.getByRole('button', { name: /next/i }));
}

describe('Create employee wizard', () => {
  it('validates each step before moving on', async () => {
    server.use(...referenceData());
    const app = renderApp({ route: '/employees/new' });

    await app.user.click(await screen.findByRole('button', { name: /next/i }));

    expect(await screen.findByText('Enter a first name.')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Personal information' })).toBeInTheDocument();
    expect(screen.getByLabelText(/first name/i)).toHaveAttribute('aria-invalid', 'true');
  });

  it('creates the employee with a sign-in account after reviewing every step', async () => {
    let body: Record<string, unknown> | undefined;
    server.use(
      ...referenceData(),
      http.post(api.employees, async ({ request }) => {
        body = (await request.json()) as Record<string, unknown>;

        return HttpResponse.json({ data: employee({ id: 'new-id', display_name: 'Ishara Fernando', has_account: true }) }, { status: 201 });
      }),
      http.get(api.employee, () => HttpResponse.json({ data: employee({ id: 'new-id', display_name: 'Ishara Fernando' }) })),
    );
    const app = renderApp({ route: '/employees/new' });

    await fillPersonalStep(app);
    await fillEmploymentStep(app);

    await app.user.click(await screen.findByRole('checkbox', { name: 'Give sign-in access' }));
    await app.user.click(screen.getByRole('button', { name: /next/i }));
    expect(await screen.findByText('Choose a role.')).toBeInTheDocument();
    await app.user.selectOptions(screen.getByLabelText(/^role/i), 'Auditor');
    await app.user.click(screen.getByRole('button', { name: /next/i }));

    expect(await screen.findByRole('heading', { name: 'Review' })).toHaveFocus();
    const employment = screen.getByRole('region', { name: 'Employment' });
    expect(within(employment).getByText('Payroll Specialist')).toBeInTheDocument();
    expect(within(employment).getByText('Engineering')).toBeInTheDocument();
    expect(within(screen.getByRole('region', { name: 'Account' })).getByText('Yes, as Auditor')).toBeInTheDocument();

    await app.user.click(screen.getByRole('button', { name: 'Create employee' }));

    await waitFor(() => expect(app.router.state.location.pathname).toBe('/employees/new-id'));
    expect(body).toMatchObject({
      first_name: 'Ishara',
      last_name: 'Fernando',
      work_email: 'ishara@meridian.test',
      job_title: 'Payroll Specialist',
      department_id: department().id,
      account: { role: 'auditor' },
    });
    expect(await screen.findByText(/An invitation was sent/)).toBeInTheDocument();
  });

  it('takes the user back to the step of a field the API rejected', async () => {
    server.use(...referenceData(), http.post(api.employees, () => problem(409, 'work_email_in_use', 'Another employee already uses this work e-mail address.')));
    const app = renderApp({ route: '/employees/new' });

    await fillPersonalStep(app, 'taken@meridian.test');
    await fillEmploymentStep(app);
    await app.user.click(await screen.findByRole('button', { name: /next/i }));
    await app.user.click(await screen.findByRole('button', { name: 'Create employee' }));

    expect(await screen.findByText('Another employee already uses this work e-mail address.')).toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'Personal information' })).toBeInTheDocument();
    await waitFor(() => expect(screen.getByLabelText(/work e-mail/i)).toHaveFocus());
  });

  it('leaves sign-in access to administrators who manage users and roles', async () => {
    server.use(...referenceData());
    const app = renderApp({ route: '/employees/new', session: { permissions: ['employee.view', 'employee.create', 'organisation.view'] } });

    await fillPersonalStep(app);
    await fillEmploymentStep(app);

    expect(await screen.findByText(/given by an administrator/)).toBeInTheDocument();
    expect(screen.queryByRole('checkbox', { name: 'Give sign-in access' })).not.toBeInTheDocument();
    expect(screen.queryByLabelText(/phone/i)).not.toBeInTheDocument();
  });
});
