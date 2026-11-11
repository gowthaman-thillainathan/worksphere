import { act, render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { useTheme } from './theme-context';
import { THEME_STORAGE_KEY, ThemeProvider } from './ThemeProvider';

function Probe() {
  const { preference, resolved, setPreference } = useTheme();

  return (
    <button type="button" onClick={() => setPreference('dark')}>
      {preference}/{resolved}
    </button>
  );
}

describe('ThemeProvider', () => {
  it('follows the system by default and persists an explicit choice', () => {
    render(
      <ThemeProvider>
        <Probe />
      </ThemeProvider>,
    );

    expect(screen.getByRole('button')).toHaveTextContent('system/light');

    act(() => screen.getByRole('button').click());

    expect(screen.getByRole('button')).toHaveTextContent('dark/dark');
    expect(document.documentElement).toHaveClass('dark');
    expect(localStorage.getItem(THEME_STORAGE_KEY)).toBe('dark');
  });
});
