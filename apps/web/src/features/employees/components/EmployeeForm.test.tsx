import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { http, HttpResponse } from 'msw';
import { describe, expect, it } from 'vitest';
import { renderApp } from '@/test/render';
import { api } from '@/test/msw/api';
import { problem } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';

describe('Create employee', () => {
  it('shows server-side business errors on the right field', async () => {
    server.use(
      http.get(api.departments, () => HttpResponse.json({ data: [], meta: { total: 0 } })),
      http.post(api.employees, () => problem(409, 'work_email_in_use', 'Another employee already uses this work e-mail address.')),
    );
    renderApp({ route: '/employees/new' });
    const user = userEvent.setup();

    await user.type(await screen.findByLabelText(/first name/i), 'Nimal');
    await user.type(screen.getByLabelText(/last name/i), 'Perera');
    await user.type(screen.getByLabelText(/work e-mail/i), 'taken@meridian.test');
    await user.type(screen.getByLabelText(/job title/i), 'Engineer');
    await user.click(screen.getByRole('button', { name: 'Create employee' }));

    expect(await screen.findByText('Another employee already uses this work e-mail address.')).toBeInTheDocument();
    expect(screen.getByLabelText(/work e-mail/i)).toHaveAttribute('aria-invalid', 'true');
    expect(screen.getByLabelText(/work e-mail/i)).toHaveFocus();
  });

  it('maps 422 field errors from the API onto the form', async () => {
    server.use(
      http.get(api.departments, () => HttpResponse.json({ data: [], meta: { total: 0 } })),
      http.post(api.employees, () =>
        problem(422, 'validation_failed', 'The given data was invalid.', { job_title: [{ code: 'max', message: 'The job title field must not be greater than 150 characters.' }] }),
      ),
    );
    renderApp({ route: '/employees/new' });
    const user = userEvent.setup();

    await user.type(await screen.findByLabelText(/first name/i), 'Nimal');
    await user.type(screen.getByLabelText(/last name/i), 'Perera');
    await user.type(screen.getByLabelText(/work e-mail/i), 'nimal@meridian.test');
    await user.type(screen.getByLabelText(/job title/i), 'Engineer');
    await user.click(screen.getByRole('button', { name: 'Create employee' }));

    expect(await screen.findByText('The job title field must not be greater than 150 characters.')).toBeInTheDocument();
  });
});
