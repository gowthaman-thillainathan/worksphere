import { act, screen, waitFor } from '@testing-library/react';
import { http, HttpResponse } from 'msw';
import { describe, expect, it } from 'vitest';
import { api } from '@/test/msw/api';
import { problem, tokenResponse } from '@/test/msw/handlers';
import { server } from '@/test/msw/server';
import { renderApp } from '@/test/render';

describe('Login', () => {
  it('validates input before calling the API', async () => {
    const { user } = renderApp({ route: '/login', session: { signedIn: false } });

    await user.click(await screen.findByRole('button', { name: 'Sign in' }));

    expect(await screen.findByText('Enter your e-mail address.')).toBeInTheDocument();
    expect(screen.getByLabelText(/e-mail/i)).toHaveAttribute('aria-invalid', 'true');
  });

  it('shows the API message for wrong credentials', async () => {
    server.use(http.post(api.login, () => problem(401, 'invalid_credentials', 'The provided credentials are incorrect.')));
    const { user } = renderApp({ route: '/login', session: { signedIn: false } });

    await user.type(await screen.findByLabelText(/e-mail/i), 'ayesha@meridian.test');
    await user.type(screen.getByLabelText(/password/i), 'wrong-password');
    await user.click(screen.getByRole('button', { name: 'Sign in' }));

    expect(await screen.findByRole('alert')).toHaveTextContent('The provided credentials are incorrect.');
  });

  it('signs in and returns to the page the user wanted', async () => {
    server.use(
      http.post(api.login, () => tokenResponse()),
      http.get(api.employees, () => HttpResponse.json({ data: [], meta: { per_page: 25, next_cursor: null, prev_cursor: null }, links: {} })),
      http.get(api.departments, () => HttpResponse.json({ data: [], meta: { total: 0 } })),
    );
    const { user, router } = renderApp({ route: '/login?redirect=%2Femployees', session: { signedIn: false } });

    await user.type(await screen.findByLabelText(/e-mail/i), 'ayesha@meridian.test');
    await user.type(screen.getByLabelText(/password/i), 'correct-horse-battery');
    await user.click(screen.getByRole('button', { name: 'Sign in' }));

    await waitFor(() => expect(router.state.location.pathname).toBe('/employees'));
  });
});

describe('Route guards', () => {
  it('sends visitors to the login page and remembers where they were going', async () => {
    const { router } = renderApp({ route: '/employees?q=nim', session: { signedIn: false } });

    await waitFor(() => expect(router.state.location.pathname).toBe('/login'));
    expect(router.state.location.search).toBe('?redirect=%2Femployees%3Fq%3Dnim');
  });

  it('hides navigation and pages the user has no permission for', async () => {
    server.use(http.get(api.headcount, () => HttpResponse.json({ data: { total: 3, by_status: { active: 3, on_leave: 0 } } })));
    server.use(http.get(api.employees, () => HttpResponse.json({ data: [], meta: { per_page: 5, next_cursor: null, prev_cursor: null }, links: {} })));
    const { router } = renderApp({ route: '/dashboard', session: { permissions: ['employee.view'] } });

    const navigation = await screen.findByRole('navigation', { name: 'Main' });
    expect(navigation).toHaveTextContent('Employees');
    expect(navigation).not.toHaveTextContent('Departments');
    expect(navigation).not.toHaveTextContent('Roles');

    await act(() => router.navigate('/roles'));

    expect(await screen.findByText("You don't have access to this page")).toBeInTheDocument();
  });
});
